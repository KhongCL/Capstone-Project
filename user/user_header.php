<?php
/**
 * Common header for user pages
 * @param string $title - Page title
 * @param string $active_page - Current active page for navigation
 */

// Set defaults if not provided
$title = isset($title) ? $title . ' - TrafAnalyz' : 'Web Traffic Analysis Dashboard';
$active_page = isset($active_page) ? $active_page : 'home';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($title); ?></title>
    <link rel="stylesheet" href="../styles.css">
    <link rel="stylesheet" href="user_style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <?php if (isset($additional_scripts)): ?>
        <?php echo $additional_scripts; ?>
    <?php endif; ?>
    
    <?php if (isset($additional_styles)): ?>
        <?php echo $additional_styles; ?>
    <?php endif; ?>
</head>
<body>
    <div class="container">
        <header>
            <a href="../index.php" class="logo">
                <div class="logo-icon">T</div>
                TrafAnalyz
            </a>
            <h1>Web Traffic Analysis Dashboard</h1>
            <nav>
                <ul>
                    <li><a href="index.php" <?php echo ($active_page == 'home') ? 'class="active"' : ''; ?>>Home</a></li>
                    <li><a href="overview.php" <?php echo ($active_page == 'overview') ? 'class="active"' : ''; ?>>Overview</a></li>
                    <li><a href="traffic_sources.php" <?php echo ($active_page == 'traffic_sources') ? 'class="active"' : ''; ?>>Traffic Sources</a></li>
                    <li><a href="pages.php" <?php echo ($active_page == 'pages') ? 'class="active"' : ''; ?>>Pages</a></li>
                    <li><a href="compare.php" <?php echo ($active_page == 'compare') ? 'class="active"' : ''; ?>>Compare</a></li>
                    <li><a href="../logout.php" <?php echo ($active_page == 'logout') ? 'class="active"' : ''; ?>>Logout</a></li>
                </ul>
            </nav>
        </header>

        <main>