<?php
session_start();
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
