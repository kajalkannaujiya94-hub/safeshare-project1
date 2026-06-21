<?php
require_once __DIR__ . '/../config/jwt.php';

function authenticate() {
    $headers = getallheaders();

    $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? null;

    if (!$authHeader) {
        http_response_code(401);
        echo json_encode(["error" => "Missing token"]);
        exit;
    }

    $token = str_replace("Bearer ", "", $authHeader);

    // Fixed: was calling verifyJWT() but function is named verify_jwt()
    $payload = verify_jwt($token);

    if (!$payload) {
        http_response_code(401);
        echo json_encode(["error" => "Invalid or expired token"]);
        exit;
    }

    return $payload;
}