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
}

// The signed-in customer for the current bearer token, or null.
function currentCustomer($pdo) {
    $token = bearerToken();
    if ($token === '' || strpos($token, 'cust_') !== 0) return null;
    ensurePatientTables($pdo);
    $stmt = $pdo->prepare("SELECT c.id, c.fullName, c.email, c.contactNumber, c.createdAt
                           FROM customer_tokens t JOIN customers c ON c.id = t.customerId
                           WHERE t.tokenHash = ? AND t.expiresAt > NOW()");
    $stmt->execute([hash('sha256', $token)]);
    return $stmt->fetch() ?: null;
}

function requireCustomer($pdo) {
    $customer = currentCustomer($pdo);
    if (!$customer) fail(401, "Please sign in to continue.");
    return $customer;
}
