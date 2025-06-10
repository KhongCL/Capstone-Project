<?php
/**
 * Common header for admin pages
 * @param string $title - Page title
 * @param string $active_page - Current active page for navigation
 */

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
    <link rel="stylesheet" href="../styles.css">
		<style>
			h1 {
				color: darkgray
			}
		</style>
    <?php if (isset($additional_styles)): ?>
        <?php echo $additional_styles; ?>
    <?php endif; ?>
</head>
<body>
    <div class="container">
        <header>
            <h1>TrafAnalyz Admin Dashboard</h1>
            <nav>
                <ul>
                    <li><a href="index.php" <?php echo ($active_page == 'dashboard') ? 'class="active"' : ''; ?>>Dashboard</a></li>
                    <li><a href="admin_users.php" <?php echo ($active_page == 'users') ? 'class="active"' : ''; ?>>User Management</a></li>
                    <li><a href="admin_mappings.php" <?php echo ($active_page == 'mappings') ? 'class="active"' : ''; ?>>CSV Mappings</a></li>
										<li><a href="upload_sample_data.php" <?php echo ($active_page == 'sample_data') ? 'class="active"' : ''; ?>>Upload Sample Data</a></li>
                    <li><a href="export_users_pdf.php" target="_blank" <?php echo ($active_page == 'report') ? 'class="active"' : ''; ?>>Generate Report</a></li>
                    <li><a href="../user/index.php" target="_blank" <?php echo ($active_page == 'user-view') ? 'class="active"' : ''; ?>>End-User View</a></li>
                    <li><a href="admin_logout.php">Logout</a></li>
                </ul>
            </nav>
        </header>