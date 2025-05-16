<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

// Check if user is admin
if ($_SESSION['role'] !== 'Admin') {
    header("Location: ../user/");
    exit;
}

require_once '../config.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TrafAnalyz Admin Dashboard</title>
    <link rel="stylesheet" href="../styles.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>TrafAnalyz Admin Dashboard</h1>
            <nav>
                <ul>
                    <li><a href="../" class="active">Home</a></li>
                    <li><a href="user_management.php">User Management</a></li>
                    <li><a href="admin_mappings.php">CSV Mappings</a></li>
                    <li><a href="../user/">End-User Dashboard</a></li>
                    <li><a href="../logout.php">Logout</a></li>
                </ul>
            </nav>
        </header>
        
        <main>
            <section class="welcome-section">
                <h2>Welcome to Admin Dashboard</h2>
                <p>Select an option from the menu to manage users or CSV mapping configurations.</p>
            </section>
            
            <section class="dashboard-links">
                <h2>Quick Links</h2>
                <div class="dashboard-cards">
                    <div class="card">
                        <h3>User Management</h3>
                        <p>View, suspend, restore, or delete user accounts.</p>
                        <a href="users.php" class="btn">Manage Users</a>
                    </div>
                    <div class="card">
                        <h3>CSV Mappings</h3>
                        <p>Configure CSV format mappings for data import.</p>
                        <a href="mappings.php" class="btn">Manage Mappings</a>
                    </div>
                    <div class="card">
                        <h3>Export Users</h3>
                        <p>Export user data to PDF format.</p>
                        <a href="export_users_pdf.php" class="btn">Export Users</a>
                    </div>
                </div>
            </section>
        </main>
        
        <footer>
            <p>&copy; <?php echo date('Y'); ?> TrafAnalyz - Admin Dashboard</p>
        </footer>
    </div>
</body>
</html>