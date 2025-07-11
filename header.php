<?php

/**
 * Common header for user pages
 * @param string $title - Page title
 * @param string $active_page - Current active page for navigation
 */

// Set defaults if not provided
$title = isset($title) ? $title . ' - TrafAnalyz' : 'Web Traffic Analysis Dashboard';
$active_page = isset($active_page) ? $active_page : 'home';

// Determine the correct path to images based on current directory
$imagePath = (basename(dirname($_SERVER['SCRIPT_NAME'])) === 'user') ? '../images/' : 'images/';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($title); ?></title>
    <link rel="stylesheet" href="styles.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <?php if (isset($additional_scripts)): ?>
        <?php echo $additional_scripts; ?>
    <?php endif; ?>
    
    <?php if (isset($additional_styles)): ?>
        <?php echo $additional_styles; ?>
    <?php endif; ?>
</head>
<body>
        <header>
            <a href="index.php" class="logo">
                <img src="<?php echo $imagePath; ?>logo2.png" alt="TrafAnalyz Logo" class="logo-image">
            </a>
            <nav>
            <ul>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <?php if ($_SESSION['role'] === 'Admin'): ?>
                        <li><a href="admin/index.php">Admin Dashboard</a></li>
                    <?php else: ?>
                        <li><a href="user/index.php">Dashboard</a></li>
                    <?php endif; ?>
                    <li><a href="logout.php">Logout</a></li>
                <?php else: ?>
                    <li><a href="login.php">Login</a></li>
                    <li><a href="register.php">Register</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </header>