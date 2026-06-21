<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

include(__DIR__ . '/../../config/db.php');
require __DIR__ . '/../../config/jwt.php';
require __DIR__ . '/../../vendor/autoload.php';

use Aws\S3\S3Client;

$headers = getallheaders();
$authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? null;
if (!$authHeader) { http_response_code(401); echo json_encode(["status"=>"error","error"=>"Missing token"]); exit; }
$token = str_replace("Bearer ", "", $authHeader);
$payload = verify_jwt($token);
if (!$payload) { http_response_code(401); echo json_encode(["status"=>"error","error"=>"Invalid token"]); exit; }

$s3 = new S3Client([
    'version' => 'latest',
    'region'  => getenv('AWS_REGION') ?: 'us-east-1',
]);
$bucket = getenv('S3_UPLOADS_BUCKET');

// Added u.role to query for admin dashboard role badges
$result = $conn->query("
    SELECT f.id, f.filename, f.uploaded_at, u.name, u.id AS user_id, u.role
    FROM files f
    JOIN users u ON f.uploaded_by = u.id
    ORDER BY f.id DESC
");

$files = [];
while ($row = $result->fetch_assoc()) {
    $cmd = $s3->getCommand('GetObject', ['Bucket' => $bucket, 'Key' => $row['filename']]);
    $presigned = $s3->createPresignedRequest($cmd, '+1 hour');
    $row['url'] = (string) $presigned->getUri();
    $files[] = $row;
}

echo json_encode(["status" => "success", "files" => $files]);