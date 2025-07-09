<?php

require_once '../auth/user_auth.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

// Set page variables for header
$title = "Supported CSV Formats";
$active_page = "supported_formats";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Supported CSV Formats - TrafAnalyz</title>
    <link rel="stylesheet" href="../styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <?php include '../header.php'; ?>

    <main class="content-page">
        <div class="formats-content">
            <h1>Supported CSV Formats</h1>
            
            <!-- Content will go here -->
            <div class="formats-placeholder">
                <p>Content coming soon...</p>
            </div>
            
        </div>
    </main>

    <?php include '../footer.php'; ?>
</body>
</html>