<?php
session_start();

function displayAdminLoginMessage() {
    echo '<script>
        if (confirm("Admin access required. Go to admin login?")) {
            window.location.href = "/Capstone-Project/admin_login.php?key=trafanalyz";
        } else {
            window.location.href = "/Capstone-Project/index.php";
        }
    </script>';
    exit();
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    displayAdminLoginMessage();
}

// Check if user has Admin role
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    displayAdminLoginMessage();
}

// Additional check for UserID existence
if (empty($_SESSION['user_id'])) {
    displayAdminLoginMessage();
}

session_write_close();
?>