<?php

// Name: Kum Yong Jun
// Position: Developer
// TP Number: TP077408
// Intake: UCDF2308ICT(SE)
// Project Name: TrafAnalyz - Complementary Web Analytics Dashboard
// Program Name: save_annotation.php
// Description: AJAX endpoint for saving user annotations with support for both creating new
//              and updating existing annotations with secure validation and JSON response handling.
// First Written On: 25 June 2025
// Edited On: 26 June 2025

require_once '../auth/user_auth.php';
require_once '../config.php';

$response = ['success' => false, 'message' => ''];

if (!isset($_SESSION['user_id'])) {
    $response['message'] = 'User not authenticated';
    echo json_encode($response);
    exit;
}

// Get POST data
$userId = $_SESSION['user_id'];
$uploadId = isset($_POST['uploadId']) ? $_POST['uploadId'] : null;
$dataDate = isset($_POST['dataDate']) ? $_POST['dataDate'] : null;
$note = isset($_POST['note']) ? $_POST['note'] : null;
$annotationId = isset($_POST['annotationId']) ? $_POST['annotationId'] : null;

try {
    if ($annotationId && $annotationId !== 'null' && $annotationId !== '') {
        // Update existing annotation
        $stmt = $conn->prepare("UPDATE annotation SET DataDate = ?, AnnotationText = ? WHERE AnnotationID = ? AND UserID = ? AND UploadID = ?");
        $stmt->bind_param("ssiii", $dataDate, $note, $annotationId, $userId, $uploadId);
    } else {
        // Insert new annotation
        $stmt = $conn->prepare("INSERT INTO annotation (UserID, UploadID, DataDate, AnnotationText) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("iiss", $userId, $uploadId, $dataDate, $note);
    }
    
    if ($stmt->execute()) {
        $response['success'] = true;
        $response['message'] = $annotationId ? 'Annotation updated successfully' : 'Annotation added successfully';
    } else {
        $response['message'] = 'Error: ' . $stmt->error;
    }
} catch (Exception $e) {
    $response['message'] = 'Database error: ' . $e->getMessage();
}

header('Content-Type: application/json');
echo json_encode($response);
?>