<?php

// Name: Khong Chee Leong
// Position: Developer
// TP Number: TP075846
// Intake: UCDF2308ICT(SE)
// Project Name: TrafAnalyz - Complementary Web Analytics Dashboard
// Program Name: user_auth.php
// Description: End-user authentication middleware that validates user sessions, manages
//              access control for end-users, and provides secure redirection handling.
// First Written On: 20 April 2025
// Edited On: 19 June 2025

// CRITICAL FIX: Only start session if not already started AND no output has been sent
if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    session_start();
}

function redirectToIndex() {
    // Display a brief message and redirect to index.php
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
            <a href="../index.php">Return to Landing Page</a>
        </div>
    </body>
    </html>';
    exit();
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    redirectToIndex();
}

// Check if user has the correct role
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'End-User') {
    redirectToIndex();
}

// Additional check for UserID existence
if (empty($_SESSION['user_id'])) {
    redirectToIndex();
}

// CRITICAL FIX: Only close session if it was started successfully
if (session_status() === PHP_SESSION_ACTIVE) {
    session_write_close();
}
?>