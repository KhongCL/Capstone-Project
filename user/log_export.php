<?php

// Name: Kum Yong Jun
// Position: Developer
// TP Number: TP077408
// Intake: UCDF2308ICT(SE)
// Project Name: TrafAnalyz - Complementary Web Analytics Dashboard
// Program Name: log_export.php
// Description: AJAX endpoint for logging user export activities with authentication validation,
//              tracking export types and descriptions for audit trail purposes.
// First Written On: 19 June 2025
// Edited On: 20 June 2025

require_once '../config.php';
require_once '../auth/user_auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    session_start();
    $userId = $_SESSION['user_id'] ?? null;

    if (!$userId) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'User not authenticated']);
        exit;
    }

    $exportType = $_POST['exportType'] ?? 'CSV';
    $description = $_POST['description'] ?? '';

    $stmt = $conn->prepare("INSERT INTO export_history (UserID, ExportType, ExportedDataDescription) VALUES (?, ?, ?)");
    $stmt->bind_param("iss", $userId, $exportType, $description);
    $success = $stmt->execute();

    echo json_encode(['success' => $success]);
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
