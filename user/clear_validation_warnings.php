<?php

// Name: Kkhong Chee Leong
// Position: Project Leader
// TP Number: TP077404
// Intake: UCDF2308ICT(SE)
// Project Name: TrafAnalyz - Complementary Web Analytics Dashboard
// Program Name: clear_validation_warnings.php
// Description: AJAX endpoint for clearing persistent validation error messages and warnings
//              from user sessions with JSON response handling.
// First Written On: 14 April 2025
// Edited On: 1 July 2025

require_once '../auth/user_auth.php';
require_once '../functions.php';

// Only handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    clearPersistentValidationErrors();
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
exit;
?>