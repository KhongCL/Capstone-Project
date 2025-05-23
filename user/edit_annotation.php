<?php
require_once '../auth/user_auth.php';
require_once '../config.php';

$response = ['success' => false, 'message' => ''];

if (!isset($_POST['annotationId']) || !isset($_POST['uploadId']) || !isset($_POST['dataDate']) || !isset($_POST['note'])) {
    $response['message'] = 'Missing required parameters';
    echo json_encode($response);
    exit;
}

$annotationId = intval($_POST['annotationId']);
$uploadId = intval($_POST['uploadId']);
$userId = $_SESSION['user_id'];
$dataDate = $_POST['dataDate'];
$note = $_POST['note'];

try {
    $stmt = $conn->prepare("UPDATE annotation SET DataDate = ?, AnnotationText = ? WHERE AnnotationID = ? AND UserID = ? AND UploadID = ?");
    $stmt->bind_param("ssiii", $dataDate, $note, $annotationId, $userId, $uploadId);
    
    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            $response['success'] = true;
            $response['message'] = 'Annotation updated successfully';
        } else {
            $response['message'] = 'No changes made or annotation not found';
        }
    } else {
        $response['message'] = 'Error updating annotation';
    }
} catch (Exception $e) {
    $response['message'] = 'Database error: ' . $e->getMessage();
}

header('Content-Type: application/json');
echo json_encode($response);
?>