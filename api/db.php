<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Connection settings, in priority order:
//   1. environment variables (Vercel / any host): DB_HOST, DB_PORT, DB_NAME, DB_USER, DB_PASS, DB_SSL, DB_SSL_CA
//   2. api/config.php (local, git-ignored; see config.example.php)
//   3. XAMPP defaults
$env = fn($k) => ($v = getenv($k)) !== false && $v !== '' ? $v : null;
$cfg = array_filter([
    'host' => $env('DB_HOST'), 'port' => $env('DB_PORT'), 'db' => $env('DB_NAME'),
    'user' => $env('DB_USER'), 'pass' => $env('DB_PASS'),
    'ssl' => $env('DB_SSL') !== null ? filter_var($env('DB_SSL'), FILTER_VALIDATE_BOOLEAN) : null,
    'ssl_ca' => $env('DB_SSL_CA'),
], fn($v) => $v !== null);
$cfg += file_exists(__DIR__ . '/config.php') ? require __DIR__ . '/config.php' : [];
$cfg += ['host' => 'localhost', 'port' => 3306, 'db' => 'one_optics', 'user' => 'root', 'pass' => '', 'ssl' => false, 'ssl_ca' => null];
// Bundled Let's Encrypt root (api/ca.pem) verifies TiDB Cloud when no other CA bundle is configured.
if ($cfg['ssl'] && empty($cfg['ssl_ca']) && file_exists(__DIR__ . '/ca.pem')) $cfg['ssl_ca'] = __DIR__ . '/ca.pem';

$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];
if ($cfg['ssl']) {
    // TiDB Cloud requires TLS. Verify the server cert against the CA bundle when one is configured.
    if (!empty($cfg['ssl_ca'])) $options[PDO::MYSQL_ATTR_SSL_CA] = $cfg['ssl_ca'];
    $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = !empty($cfg['ssl_ca']);
}

try {
    $dsn = "mysql:host={$cfg['host']};port={$cfg['port']};dbname={$cfg['db']};charset=utf8mb4";
    $pdo = new PDO($dsn, $cfg['user'], $cfg['pass'], $options);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => "Database connection failed: " . $e->getMessage()]);
    exit();
}

// Extra path after the script name, e.g. "/9/status" for api/orders.php/9/status.
// Apache exposes it as PATH_INFO; on Vercel a rewrite in vercel.json passes it as ?_path=.
function requestPath() {
    if (!empty($_SERVER['PATH_INFO'])) return $_SERVER['PATH_INFO'];
    if (!empty($_GET['_path'])) return $_GET['_path'];
    $uri = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '';
    return preg_match('/\.php(\/.*)$/', $uri, $m) ? $m[1] : '';
}

const ADMIN_TOKEN = 'php_admin_token_123';

// Raw bearer token from the Authorization header, or '' when absent.
function bearerToken() {
    // Header names are case-insensitive and some hosts (e.g. Vercel) lowercase them; also fall back to $_SERVER.
    $headers = array_change_key_case(function_exists('getallheaders') ? getallheaders() : [], CASE_LOWER);
    $authHeader = $headers['authorization'] ?? $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
    return preg_match('/Bearer\s+(\S+)/i', $authHeader, $m) ? $m[1] : '';
}

function isAdmin() {
    return hash_equals(ADMIN_TOKEN, bearerToken());
}

function checkAuth() {
    if (!isAdmin()) {
        http_response_code(401);
        echo json_encode(["error" => "Unauthorized"]);
        exit();
    }
}

function jsonBody() {
    return json_decode(file_get_contents("php://input"), true) ?: [];
}

function fail($code, $message, $extra = []) {
    http_response_code($code);
    echo json_encode(["error" => $message] + $extra);
    exit();
}

// Phone numbers are digits only (7-15). Returns the cleaned string, null when blank and
// not required, or exits with 422 when invalid.
function cleanPhone($value, $field = 'contactNumber', $required = false) {
    $raw = trim((string)($value ?? ''));
    if ($raw === '') {
        if ($required) fail(422, "Contact number is required.", ['field' => $field]);
        return null;
    }
    if (!preg_match('/^\d{7,15}$/', $raw)) fail(422, "Phone number must be 7 to 15 digits (numbers only).", ['field' => $field]);
    return $raw;
}

// Records are never hard-deleted: admins archive them instead. These tables carry
// isActive (1 = active, 0 = archived) and archivedAt. Added on demand for older databases.
const ARCHIVABLE = ['appointments', 'orders', 'doctors', 'customers'];

function ensureArchiveColumns($pdo) {
    static $done = false;
    if ($done) return;
    $done = true;
    $in = implode(',', array_fill(0, count(ARCHIVABLE), '?'));
    $stmt = $pdo->prepare("SELECT TABLE_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND COLUMN_NAME = 'isActive' AND TABLE_NAME IN ($in)");
    $stmt->execute(ARCHIVABLE);
    $have = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $exists = $pdo->query("SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE()")->fetchAll(PDO::FETCH_COLUMN);
    foreach (ARCHIVABLE as $t) {
        if (in_array($t, $have) || !in_array($t, $exists)) continue;
        $pdo->exec("ALTER TABLE `$t` ADD COLUMN isActive tinyint(1) NOT NULL DEFAULT 1, ADD COLUMN archivedAt datetime DEFAULT NULL");
    }
}

// Flip isActive for one row and answer JSON. $action is 'archive' or 'restore'.
function setActive($pdo, $table, $id, $action, $label) {
    $active = $action === 'restore' ? 1 : 0;
    $stmt = $pdo->prepare("UPDATE `$table` SET isActive = ?, archivedAt = ? WHERE id = ?");
    $stmt->execute([$active, $active ? null : date('Y-m-d H:i:s'), $id]);
    $check = $pdo->prepare("SELECT id FROM `$table` WHERE id = ?");
    $check->execute([$id]);
    if (!$check->fetch()) fail(404, "$label not found");
    echo json_encode(["message" => "$label " . ($active ? "restored" : "archived"), "isActive" => $active]);
}

// Customer accounts + patient records live in tables that older deployments don't have yet,
// so create them on demand (mirrors database.sql).
function ensurePatientTables($pdo) {
    static $done = false;
    if ($done) return;
    $done = true;
    $pdo->exec("CREATE TABLE IF NOT EXISTS customers (
        id int(11) NOT NULL AUTO_INCREMENT,
        fullName varchar(255) NOT NULL,
        email varchar(255) NOT NULL,
        password varchar(255) NOT NULL,
        contactNumber varchar(100) DEFAULT NULL,
        createdAt datetime NOT NULL,
        PRIMARY KEY (id),
        UNIQUE KEY email (email)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $pdo->exec("CREATE TABLE IF NOT EXISTS customer_tokens (
        id int(11) NOT NULL AUTO_INCREMENT,
        customerId int(11) NOT NULL,
        tokenHash char(64) NOT NULL,
        expiresAt datetime NOT NULL,
        PRIMARY KEY (id),
        UNIQUE KEY tokenHash (tokenHash),
        KEY customerId (customerId)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $pdo->exec("CREATE TABLE IF NOT EXISTS patient_records (
        id int(11) NOT NULL AUTO_INCREMENT,
        customerId int(11) NOT NULL,
        dateOfBirth date DEFAULT NULL,
        sex varchar(20) DEFAULT NULL,
        address varchar(500) DEFAULT NULL,
        bloodType varchar(5) DEFAULT NULL,
        allergies text DEFAULT NULL,
        medications text DEFAULT NULL,
        conditions text DEFAULT NULL,
        eyeConditions text DEFAULT NULL,
        wearsCorrection varchar(30) DEFAULT NULL,
        lastEyeExam date DEFAULT NULL,
        prescription text DEFAULT NULL,
        emergencyContactName varchar(255) DEFAULT NULL,
        emergencyContactPhone varchar(100) DEFAULT NULL,
        notes text DEFAULT NULL,
        clinicalNotes text DEFAULT NULL,
        createdAt datetime NOT NULL,
        updatedAt datetime NOT NULL,
        PRIMARY KEY (id),
        UNIQUE KEY customerId (customerId)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    ensureArchiveColumns($pdo);
}

// Inactive (archived) customers cannot use the site until an admin restores them.
function currentCustomer($pdo) {
    $token = bearerToken();
    if ($token === '' || strpos($token, 'cust_') !== 0) return null;
    ensurePatientTables($pdo);
    $stmt = $pdo->prepare("SELECT c.id, c.fullName, c.email, c.contactNumber, c.createdAt
                           FROM customer_tokens t JOIN customers c ON c.id = t.customerId
                           WHERE t.tokenHash = ? AND t.expiresAt > NOW() AND c.isActive = 1");
    $stmt->execute([hash('sha256', $token)]);
    return $stmt->fetch() ?: null;
}

function requireCustomer($pdo) {
    $customer = currentCustomer($pdo);
    if (!$customer) fail(401, "Please sign in to continue.");
    return $customer;
}
