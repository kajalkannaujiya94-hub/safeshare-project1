<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS, DELETE");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

require_once __DIR__ . '/../../middleware/auth.php';
require_once __DIR__ . '/../../config/db.php';
require __DIR__ . '/../../vendor/autoload.php';

use Aws\S3\S3Client;

$user = authenticate();

if ($user['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(["error" => "Admin only"]);
    exit;
}

if (!isset($_GET['id'])) {
    http_response_code(400);
    echo json_encode(["error" => "User ID required"]);
    exit;
}

$userId = intval($_GET['id']);

// Prevent admin from deleting themselves
if ($userId === $user['id']) {
    http_response_code(400);
    echo json_encode(["error" => "Cannot delete your own account"]);
    exit;
}

// Get all files uploaded by this user and delete from S3
$s3 = new S3Client([
    'version' => 'latest',
    'region'  => getenv('AWS_REGION') ?: 'us-east-1',
]);
$bucket = getenv('S3_UPLOADS_BUCKET');

$stmt = $conn->prepare("SELECT filename FROM files WHERE uploaded_by = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    try {
        $s3->deleteObject(['Bucket' => $bucket, 'Key' => $row['filename']]);
    } catch (Exception $e) {
        // Continue even if S3 delete fails
    }
}
$stmt->close();

// Delete user (cascade deletes their files from DB too)
$delete = $conn->prepare("DELETE FROM users WHERE id = ?");
$delete->bind_param("i", $userId);
$delete->execute();

if ($delete->affected_rows > 0) {
    echo json_encode(["status" => "success", "message" => "User and their files deleted"]);
} else {
    http_response_code(404);
    echo json_encode(["error" => "User not found"]);
}