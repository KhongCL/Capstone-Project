<?php
session_start();

function displayAdminLoginMessage() {
    echo '<script>
        if (confirm("Admin access required. Would you like to verify admin access?")) {
            let key = prompt("Please enter admin key:");
            if (key === "trafanalyz") {
                window.location.href = "/Capstone-Project/admin_login.php?key=" + key;
            } else {
                alert("Invalid admin key!");
                window.location.href = "/Capstone-Project/index.php";
            }
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