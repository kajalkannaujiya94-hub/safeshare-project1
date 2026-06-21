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
$uploadSuccess = false;

error_reporting(0);
ini_set('display_errors', 0);

include(__DIR__ . '/../../config/db.php');
require __DIR__ . '/../../config/jwt.php';
require __DIR__ . '/../../vendor/autoload.php';

use Aws\S3\S3Client;
use Aws\Exception\AwsException;

$s3 = new S3Client([
    'version' => 'latest',
    'region'  => getenv('AWS_REGION') ?: 'us-east-1',
]);

$bucket = getenv('S3_UPLOADS_BUCKET');

$headers = getallheaders();
$authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? null;

if (!$authHeader) {
    $duration = microtime(true) - $start;
    AppMetrics::recordUpload('failure');
    AppMetrics::recordRequest('POST', '/upload', 401);
    AppMetrics::recordDuration('/upload', $duration);
    echo json_encode(["status" => "error", "error" => "Missing token"]);
    exit;
}

$token = str_replace("Bearer ", "", $authHeader);
$payload = verify_jwt($token);

if (!$payload) {
    $duration = microtime(true) - $start;
    AppMetrics::recordUpload('failure');
    AppMetrics::recordRequest('POST', '/upload', 401);
    AppMetrics::recordDuration('/upload', $duration);
    echo json_encode(["status" => "error", "error" => "Invalid token"]);
    exit;
}

$userId = $payload['id'] ?? null;

if (!$userId) {
    $duration = microtime(true) - $start;
    AppMetrics::recordUpload('failure');
    AppMetrics::recordRequest('POST', '/upload', 401);
    AppMetrics::recordDuration('/upload', $duration);
    echo json_encode(["status" => "error", "error" => "Invalid user"]);
    exit;
}

if (!isset($_FILES['file'])) {
    $duration = microtime(true) - $start;
    AppMetrics::recordUpload('failure');
    AppMetrics::recordRequest('POST', '/upload', 400);
    AppMetrics::recordDuration('/upload', $duration);
    echo json_encode(["status" => "error", "error" => "No file uploaded"]);
    exit;
}

$originalName = basename($_FILES['file']['name']);
$ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
$allowedTypes = ['pdf','docx','pptx','txt','jpg','jpeg','png'];

if (!in_array($ext, $allowedTypes)) {
    $duration = microtime(true) - $start;
    AppMetrics::recordUpload('failure');
    AppMetrics::recordRequest('POST', '/upload', 400);
    AppMetrics::recordDuration('/upload', $duration);
    echo json_encode(["status" => "error", "error" => "Invalid file type"]);
    exit;
}

$filename = uniqid() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $originalName);

try {

    $s3->putObject([
        'Bucket'     => $bucket,
        'Key'        => $filename,
        'SourceFile' => $_FILES['file']['tmp_name'],
    ]);

    $stmt = $conn->prepare("INSERT INTO files (filename, uploaded_by) VALUES (?, ?)");
    $stmt->bind_param("si", $filename, $userId);
    $stmt->execute();
    $uploadSuccess = true;

    // ── Fetch user email first (needed by both sharelink and notification) ──
    $user_email = '';
    $stmt = $conn->prepare("SELECT email FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $user_email = $row['email'];
    }

    // ── Call sharelink service ──────────────────────────────────────────────
    $shareUrl = null;
    $ch = curl_init('http://sharelink.safeshare.local:3000/links');
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        'filename'      => $filename,
        'userId'        => $userId,
        'uploaderEmail' => $user_email
    ]));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 2);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
    $share_response = json_decode(@curl_exec($ch), true);
    curl_close($ch);
    $shareUrl = $share_response['shareUrl'] ?? null;

    // ── Call notification service ───────────────────────────────────────────
    if ($user_email) {
        $ch = curl_init('http://notification-service.safeshare.local:4000/notify/upload');
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
            'email'    => $user_email,
            'filename' => $filename,
            'shareUrl' => $shareUrl
        ]));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_exec($ch);
        curl_close($ch);
    }

    $duration = microtime(true) - $start;
    AppMetrics::recordUpload('success');
    AppMetrics::recordRequest('POST', '/upload', 200);
    AppMetrics::recordDuration('/upload', $duration);

    echo json_encode([
        "status"   => "success",
        "file"     => $filename,
        "shareUrl" => $shareUrl
    ]);

} catch (AwsException $e) {

    $duration = microtime(true) - $start;
    AppMetrics::recordUpload('failure');
    AppMetrics::recordRequest('POST', '/upload', 500);
    AppMetrics::recordDuration('/upload', $duration);

    echo json_encode([
        "status" => "error",
        "error"  => $e->getMessage()
    ]);
}