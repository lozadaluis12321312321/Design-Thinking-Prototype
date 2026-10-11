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

function checkAuth() {
    $headers = getallheaders();
    $authHeader = isset($headers['Authorization']) ? $headers['Authorization'] : '';
    if (strpos($authHeader, 'Bearer php_admin_token_123') === false) {
        http_response_code(401);
        echo json_encode(["error" => "Unauthorized"]);
        exit();
    }
}
