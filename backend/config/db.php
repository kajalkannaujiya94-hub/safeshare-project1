<?php
require_once __DIR__ . '/../includes/metrics.php';
$start = microtime(true);

$host = getenv("DB_HOST");
$user = getenv("DB_USER");
$pass = getenv("DB_PASS");
$db   = getenv("DB_NAME");

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    http_response_code(500);
    header("Content-Type: application/json");
    echo json_encode([
        "status" => "error",
        "error"  => "DB connection failed: " . $conn->connect_error
    ]);
    exit;
}
?>