<?php

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

function displayLoginMessage() {
    echo '<script>
        if (confirm("You need to log in to access this page. Go to Login Page? Click cancel to go to home page.")) {
            window.location.href = "../login.php";
        } else {
            window.location.href = "../index.php";
        }
    </script>';
    exit();
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    displayLoginMessage();
}

// Check if user has the correct role
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'End-User') {
    displayLoginMessage();
}

// Additional check for UserID existence
if (empty($_SESSION['user_id'])) {
    displayLoginMessage();
}

session_write_close();
?>