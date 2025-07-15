<?php

// Name: Kum Yong Jun
// Position: Developer
// TP Number: TP077408
// Intake: UCDF2308ICT(SE)
// Project Name: TrafAnalyz - Complementary Web Analytics Dashboard
// Program Name: get_annotations.php
// Description: AJAX endpoint for retrieving user annotations with secure validation,
//              user ownership verification, and JSON response for annotation display.
// First Written On: 14 April 2025
// Edited On: 7 July 2025

require_once '../auth/user_auth.php';
require_once '../config.php';

$userId = $_SESSION['user_id'];
$uploadId = $_GET['uploadId'];

$stmt = $conn->prepare("SELECT AnnotationID, DataDate, AnnotationText FROM annotation WHERE UserID = ? AND UploadID = ?");
$stmt->bind_param("ii", $userId, $uploadId);
$stmt->execute();
$result = $stmt->get_result();

$annotations = [];
while ($row = $result->fetch_assoc()) {
    $annotations[] = [
        "id" => $row['AnnotationID'],
        "date" => $row['DataDate'],
        "note" => $row['AnnotationText']
    ];
}
echo json_encode($annotations);
?>
