<?php

header("Content-Type: application/json");

$checks = [];
$healthy = true;


// Env vars
$host = getenv("DB_HOST");
$user = getenv("DB_USER");
$pass = getenv("DB_PASS");
$db   = getenv("DB_NAME");

// Validate env
if (!$host || !$user || !$db) {
    $checks["database"] = "missing env vars";
    $healthy = false;
} else {
    mysqli_report(MYSQLI_REPORT_OFF);

    $conn = @new mysqli();
    $conn->options(MYSQLI_OPT_CONNECT_TIMEOUT, 2);
    $conn->real_connect($host, $user, $pass, $db);

    if ($conn->connect_error) {
        $checks["database"] = "unhealthy: " . $conn->connect_error;
        $healthy = false;
    } else {
        $checks["database"] = "healthy";
        $conn->close();
    }
}

// S3 check
$checks["s3_bucket"] = getenv("S3_UPLOADS_BUCKET") ? "configured" : "missing";

http_response_code($healthy ? 200 : 503);

echo json_encode([
    "status" => $healthy ? "ok" : "unhealthy",
    "checks" => $checks,
    "timestamp" => date("c")
]);
