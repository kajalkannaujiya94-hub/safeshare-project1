<?php

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

include(__DIR__ . '/../../config/db.php');

$email = 'admin@safeshare.com';

$stmt = $conn->prepare("SELECT id, name, email, role, password FROM users WHERE email=?");
$stmt->bind_param("s", $email);
$stmt->execute();
$res = $stmt->get_result();
$user = $res->fetch_assoc();

if (!$user) {
    echo json_encode(["error" => "User not found"]);
    exit;
}

// Test password verify
$testPassword = 'Admin123';
$verified = password_verify($testPassword, $user['password']);

echo json_encode([
    "user_found" => true,
    "role" => $user['role'],
    "password_hash_preview" => substr($user['password'], 0, 20),
    "password_verify_result" => $verified
]);