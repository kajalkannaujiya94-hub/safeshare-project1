<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

require_once __DIR__ . '/../../middleware/auth.php';
include(__DIR__ . '/../../config/db.php');

$user = authenticate();

if ($user['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(["error" => "Admin only"]);
    exit;
}

$result = $conn->query("SELECT id, name, email, role, created_at FROM users ORDER BY id DESC");
$users = [];
while ($row = $result->fetch_assoc()) {
    $users[] = $row;
}

echo json_encode(["status" => "success", "users" => $users]);