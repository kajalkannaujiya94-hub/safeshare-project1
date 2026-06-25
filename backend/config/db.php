<?php
require_once __DIR__ . '/../includes/metrics.php';
$start = microtime(true);

$host = getenv("DB_HOST");
$user = getenv("DB_USER");
$pass = getenv("DB_PASS");
$db   = getenv("DB_NAME");
$port = getenv("DB_PORT") ?: 3306;

mysqli_report(MYSQLI_REPORT_OFF);

$conn = mysqli_init();
$conn->options(MYSQLI_OPT_CONNECT_TIMEOUT, 5);
$conn->real_connect($host, $user, $pass, $db, (int)$port);

if ($conn->connect_error) {
    http_response_code(500);
    header("Content-Type: application/json");
    echo json_encode([
        "status" => "error",
        "error" => "DB connection failed: " . $conn->connect_error
    ]);
    exit;
}
?>
EOF 
