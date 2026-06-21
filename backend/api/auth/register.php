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
$registerSuccess = false;

error_reporting(0);
ini_set('display_errors', 0);

include(__DIR__ . '/../../config/db.php');

$data = json_decode(file_get_contents("php://input"), true);

if (!$data) {
    $duration = microtime(true) - $start;
    AppMetrics::recordRegister('failure');
    AppMetrics::recordRequest('POST', '/register', 400);
    AppMetrics::recordDuration('/register', $duration);
    echo json_encode(["status"=>"error","error"=>"Invalid JSON"]);
    exit;
}

$name = trim($data['name'] ?? '');
$email = trim($data['email'] ?? '');
$password = $data['password'] ?? '';
$role = $data['role'] ?? '';

if (!$name || !$email || !$password || !$role) {
    $duration = microtime(true) - $start;
    AppMetrics::recordRegister('failure');
    AppMetrics::recordRequest('POST', '/register', 400);
    AppMetrics::recordDuration('/register', $duration);
    echo json_encode(["status"=>"error","error"=>"Missing fields"]);
    exit;
}

$hash = password_hash($password, PASSWORD_DEFAULT);

$stmt = $conn->prepare("INSERT INTO users (name,email,password,role) VALUES (?,?,?,?)");

if(!$stmt){
    $duration = microtime(true) - $start;
    AppMetrics::recordRegister('failure');
    AppMetrics::recordRequest('POST', '/register', 500);
    AppMetrics::recordDuration('/register', $duration);
    echo json_encode(["status"=>"error","error"=>$conn->error]);
    exit;
}

$stmt->bind_param("ssss",$name,$email,$hash,$role);

if($stmt->execute()){
    $registerSuccess = true;
    $duration = microtime(true) - $start;
    AppMetrics::recordRegister('success');
    AppMetrics::recordRequest('POST', '/register', 200);
    AppMetrics::recordDuration('/register', $duration);
    echo json_encode(["status"=>"success"]);
}else{
    $duration = microtime(true) - $start;
    AppMetrics::recordRegister('failure');
    AppMetrics::recordRequest('POST', '/register', 500);
    AppMetrics::recordDuration('/register', $duration);
    echo json_encode(["status"=>"error","error"=>$stmt->error]);
}