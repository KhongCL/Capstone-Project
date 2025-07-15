<?php

// Name: Mervin Ooi Zhian Yang
// Position: Developer
// TP Number: TP076578
// Intake: UCDF2308ICT(SE)
// Project Name: TrafAnalyz - Complementary Web Analytics Dashboard
// Program Name: admin_header.php
// Description: Common header component included across all admin pages with navigation,
//              title management, and authentication elements for admin dashboard.
// First Written On: 20 April 2025
// Edited On: 14 July 2025

// Set defaults if not provided
$title = isset($title) ? $title . ' - TrafAnalyz Admin' : 'TrafAnalyz Admin Dashboard';
$active_page = isset($active_page) ? $active_page : 'dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title; ?></title>
    <link rel="stylesheet" href="admin_style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <?php if (isset($additional_styles)): ?>
        <?php echo $additional_styles; ?>
    <?php endif; ?>
</head>
<body>
        <header>
            <h1>TrafAnalyz Admin Dashboard</h1>
            <nav>
                <ul>
                    <li><a href="index.php" <?php echo ($active_page == 'dashboard') ? 'class="active"' : ''; ?>>Dashboard</a></li>
                    <li><a href="admin_users.php" <?php echo ($active_page == 'users') ? 'class="active"' : ''; ?>>User Management</a></li>
                    <li><a href="admin_mappings.php" <?php echo ($active_page == 'mappings') ? 'class="active"' : ''; ?>>CSV Mappings</a></li>
                    <li><a href="upload_sample_data.php" <?php echo ($active_page == 'sample_data') ? 'class="active"' : ''; ?>>Upload Sample Data</a></li>
                    <li><a href="export_users_pdf.php" target="_blank" <?php echo ($active_page == 'report') ? 'class="active"' : ''; ?>>Generate Report</a></li>
                    <li><a href="admin_logout.php" class="logout">Logout</a></li>
                </ul>
            </nav>
        </header>