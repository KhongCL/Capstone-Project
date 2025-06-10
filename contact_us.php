<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us - TrafAnalyz</title>
    <link rel="stylesheet" href="styles.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <header>
        <a href="index.php" class="logo">
            <div class="logo-icon">T</div>
            TrafAnalyz
        </a>
        <nav>
            <ul>
                <li><a href="login.php">Login</a></li>
                <li><a href="register.php">Register</a></li>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <?php if ($_SESSION['role'] === 'Admin'): ?>
                        <li><a href="admin/index.php">Admin Dashboard</a></li>
                    <?php else: ?>
                        <li><a href="user/index.php">Dashboard</a></li>
                    <?php endif; ?>
                    <li><a href="logout.php">Logout</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </header>

    <section id="contact-us">
        <div class="container">
            <h2>Connect with the TrafAnalyz Team</h2>
            <div id="contact-block">
                <div class="contact-box">
                    <h3><i class="fas fa-phone-alt icon fa-flip-horizontal"></i> Project Inquiries</h3>
                    <p>+60 3-8996 1234 (Project Lead)</p>
                    <p>+60 3-8996 5678 (Technical Support)</p>
                    <p>+60 3-8996 9012 (General Assistance)</p>
                </div>
                <div class="contact-box">
                    <h3><i class="fas fa-envelope icon"></i> Email Us</h3>
                    <p><a href="mailto:info@trafanalyz.com">info@trafanalyz.com</a></p>
                    <p><a href="mailto:support@trafanalyz.com">support@trafanalyz.com</a></p>
                    <p><a href="mailto:feedback@trafanalyz.com">feedback@trafanalyz.com</a></p>
                </div>
                <div class="contact-box">
                    <h3><i class="fas fa-map-marker-alt icon"></i> Project Base</h3>
                    <p>Asia Pacific University of Technology & Innovation (APU)</p>
                    <p>Jalan Teknologi 5, Taman Teknologi Malaysia,</p>
                    <p>57000 Kuala Lumpur, Wilayah Persekutuan Kuala Lumpur, Malaysia</p>
                </div>
            </div>
        </div>
        <div style="height: 75px;"></div>
    </section>

    <?php include 'footer.php'; ?>
</body>
</html>