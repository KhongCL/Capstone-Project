<?php
/**
 * Common header for admin pages
 * @param string $title - Page title
 * @param string $active_page - Current active page for navigation
 */

// Set defaults if not provided
$title = isset($title) ? $title . ' - TrafAnalyz Admin' : 'TrafAnalyz Admin Dashboard';
$active_page = isset($active_page) ? $active_page : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title; ?></title>
    <link rel="stylesheet" href="../styles.css">
    <?php if (isset($additional_styles)): ?>
        <?php echo $additional_styles; ?>
    <?php endif; ?>
		
</head>
<body>
    <header>
        <h1>TrafAnalyz Admin Dashboard</h1>
        <nav>
            <ul>
                <li><a href="index.php" class="active">Dashboard</a></li>
                <li><a href="admin_users.php">User Management</a></li>
                <li><a href="admin_mappings.php">CSV Mappings</a></li>
                <li><a href="export_users_pdf.php" target="_blank">Generate Report</a></li>
                <li><a href="../user/index.php" target="_blank">End-User View</a></li>
                <li><a href="../logout.php">Logout</a></li>
            </ul>
        </nav>
    </header>
<body>