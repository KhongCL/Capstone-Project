<?php

// Name: Mervin Ooi Zhian Yang
// Position: Developer
// TP Number: TP076578
// Intake: UCDF2308ICT(SE)
// Project Name: TrafAnalyz - Complementary Web Analytics Dashboard
// Program Name: user_header.php
// Description: Common header component for user dashboard pages with navigation menu,
//              page title management, and responsive design for consistent layout across all user pages.
// First Written On: 14 April 2025
// Edited On: 12 July 2025

/**
 * Common header for admin pages
 * @param string $title - Page title
 * @param string $active_page - Current active page for navigation
 */

// Set defaults if not provided
$title = isset($title) ? $title . ' - TrafAnalyz End-User' : 'TrafAnalyz End-User Dashboard';
$active_page = isset($active_page) ? $active_page : 'dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title; ?></title>
    <link rel="stylesheet" href="user_style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <?php if (isset($additional_styles)): ?>
        <?php echo $additional_styles; ?>
    <?php endif; ?>
</head>
<body>
    <header>
        <h1>TrafAnalyz End-User Dashboard</h1>
        <nav>
            <ul>
                <li><a href="index.php" <?php echo ($active_page == 'home') ? 'class="active"' : ''; ?>>Home</a></li>
                <li><a href="overview.php" <?php echo ($active_page == 'overview') ? 'class="active"' : ''; ?>>Overview</a></li>
                <li><a href="traffic_sources.php" <?php echo ($active_page == 'traffic_sources') ? 'class="active"' : ''; ?>>Traffic Sources</a></li>
                <li><a href="pages.php" <?php echo ($active_page == 'pages') ? 'class="active"' : ''; ?>>Pages</a></li>
                <li><a href="compare.php" <?php echo ($active_page == 'compare') ? 'class="active"' : ''; ?>>Compare</a></li>
                <li><a href="../logout.php" class="logout" <?php echo ($active_page == 'logout') ? 'class="active"' : ''; ?>>Logout</a></li>
            </ul>
        </nav>
    </header>
