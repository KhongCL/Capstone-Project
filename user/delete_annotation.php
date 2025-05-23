<?php
require_once '../auth/user_auth.php';
require_once '../config.php';

$response = ['success' => false, 'message' => ''];

if (!isset($_POST['annotationId']) || !isset($_POST['uploadId'])) {
    $response['message'] = 'Missing required parameters';
    echo json_encode($response);
    exit;
}

$annotationId = $_POST['annotationId'];
$uploadId = $_POST['uploadId'];
$userId = $_SESSION['user_id'];

try {
    $stmt = $conn->prepare("DELETE FROM annotation WHERE AnnotationID = ? AND UserID = ? AND UploadID = ?");
    $stmt->bind_param("iii", $annotationId, $userId, $uploadId);
    
    if ($stmt->execute()) {
        $response['success'] = true;
        $response['message'] = 'Annotation deleted successfully';
    } else {
        $response['message'] = 'Error deleting annotation';
    }
} catch (Exception $e) {
    $response['message'] = 'Database error: ' . $e->getMessage();
}

echo json_encode($response);
?>