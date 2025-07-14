<?php

// Name: Khong Chee Leong
// Position: Project Leader
// TP Number: TP075846
// Intake: UCDF2308ICT(SE)
// Project Name: TrafAnalyz - Complementary Web Analytics Dashboard
// Program Name: flexible_auth.php
// Description: Flexible authentication middleware that validates user access for both
//              end-users and admin roles with appropriate login redirection handling.
// First Written On: 20 April 2025
// Edited On: 19 June 2025

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

function redirectToAppropriateLogin() {
    // Display a brief message and redirect based on current path
    $isAdminArea = strpos($_SERVER['REQUEST_URI'], '/admin/') !== false;
    $loginUrl = $isAdminArea ? '../admin_login.php?key=trafanalyz' : '../login.php';
    $homeUrl = $isAdminArea ? '../admin/index.php' : '../index.php';
    
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
            <p>Please log in to access this page.</p>
            <a href="' . $loginUrl . '">Login</a> | 
            <a href="' . $homeUrl . '">Return to Homepage</a>
        </div>
    </body>
    </html>';
    exit();
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    redirectToAppropriateLogin();
}

// Allow both End-User and Admin roles
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['End-User', 'Admin'])) {
    redirectToAppropriateLogin();
}

// Additional check for UserID existence
if (empty($_SESSION['user_id'])) {
    redirectToAppropriateLogin();
}

session_write_close();
?>