<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../middleware/auth.php';
require __DIR__ . '/../../vendor/autoload.php';

use Aws\S3\S3Client;
use Aws\Exception\AwsException;

$user = authenticate();
if ($user['role'] !== 'admin') { http_response_code(403); echo json_encode(["error"=>"Admin only"]); exit; }
if (!isset($_GET['id'])) { http_response_code(400); echo json_encode(["error"=>"File ID required"]); exit; }

$fileId = intval($_GET['id']);
$stmt = $conn->prepare("SELECT filename FROM files WHERE id = ?");
$stmt->bind_param("i", $fileId);
$stmt->execute();
$stmt->bind_result($filename);
if (!$stmt->fetch()) { http_response_code(404); echo json_encode(["error"=>"File not found"]); exit; }
$stmt->close();

// Fixed: delete from S3 instead of local filesystem
$s3 = new S3Client([
    'version' => 'latest',
    'region'  => getenv('AWS_REGION') ?: 'us-east-1',
]);
try {
    $s3->deleteObject(['Bucket' => getenv('S3_UPLOADS_BUCKET'), 'Key' => $filename]);
} catch (AwsException $e) {
    echo json_encode(["status"=>"error","error"=>"S3 delete failed: " . $e->getMessage()]); exit;
}

$delete = $conn->prepare("DELETE FROM files WHERE id = ?");
$delete->bind_param("i", $fileId);
$delete->execute();

echo json_encode(["status"=>"success","deleted_file"=>$filename]);