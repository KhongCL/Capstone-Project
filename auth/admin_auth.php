<?php

// Name: Khong Chee Leong
// Position: Project Leader
// TP Number: TP075846
// Intake: UCDF2308ICT(SE)
// Project Name: TrafAnalyz - Complementary Web Analytics Dashboard
// Program Name: admin_auth.php
// Description: Admin authentication middleware that validates admin access, manages sessions,
//              and provides secure authorization for administrative functions.
// First Written On: 20 April 2025
// Edited On: 19 June 2025

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

function displayAccessDeniedMessage() {
    // Display a professional access denied message in the top left
    echo '<!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Access Denied</title>
    </head>
    <body>
        <div class="access-denied">
            <h2>Access Denied</h2>
            <p>Access denied. Admin area requires proper authorization.</p>
            <a href="../index.php">Return to Homepage</a>
        </div>
    </body>
    </html>';
    exit();
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    displayAccessDeniedMessage();
}

// Check if user has Admin role
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    displayAccessDeniedMessage();
}

// Additional check for UserID existence
if (empty($_SESSION['user_id'])) {
    displayAccessDeniedMessage();
}

session_write_close();
?>