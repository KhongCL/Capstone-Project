<?php
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
        <script>
            // Redirect to homepage after 5 seconds
            setTimeout(function() {
                window.location.href = "../index.php";
            }, 5000);
        </script>
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