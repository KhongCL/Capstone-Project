<?php

// Name: Kum Yong Jun
// Position: Developer
// TP Number: TP077408
// Intake: UCDF2308ICT(SE)
// Project Name: TrafAnalyz - Complementary Web Analytics Dashboard
// Program Name: delete_annotation.php
// Description: AJAX endpoint for deleting user annotations with secure validation,
//              user ownership verification, and JSON response handling.
// First Written On: 14 April 2025
// Edited On: 14 July 2025

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