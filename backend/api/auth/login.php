<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../../includes/metrics.php';
$start = microtime(true);
$loginSuccess = false;

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

include(__DIR__ . '/../../config/db.php');
require __DIR__ . '/../../config/jwt.php';

$data = json_decode(file_get_contents("php://input"), true);

$email = trim($data['email'] ?? '');
$password = $data['password'] ?? '';

if(!$email || !$password){
    $duration = microtime(true) - $start;
    AppMetrics::recordLogin('failure');
    AppMetrics::recordRequest('POST', '/login', 400);
    AppMetrics::recordDuration('/login', $duration);
    echo json_encode(["status"=>"error","error"=>"Missing credentials"]);
    exit;
}

$stmt = $conn->prepare("SELECT * FROM users WHERE email=?");
$stmt->bind_param("s",$email);
$stmt->execute();

$res = $stmt->get_result();
$user = $res->fetch_assoc();

if($user && password_verify($password,$user['password'])){
    $loginSuccess = true;
    $token = generate_jwt([
        "id"=>$user['id'],
        "role"=>$user['role']
    ]);

    $duration = microtime(true) - $start;
    AppMetrics::recordLogin('success');
    AppMetrics::recordRequest('POST', '/login', 200);
    AppMetrics::recordDuration('/login', $duration);
    echo json_encode([
        "status"=>"success",
        "token"=>$token,
        "user"=>[
            "id"=>$user['id'],
            "name"=>$user['name'],
            "role"=>$user['role']
        ]
    ]);
}else{
    $duration = microtime(true) - $start;
    AppMetrics::recordLogin('failure');
    AppMetrics::recordRequest('POST', '/login', 401);
    AppMetrics::recordDuration('/login', $duration);
    echo json_encode(["status"=>"error","error"=>"Invalid credentials"]);
}