<?php
// filepath: c:\xampp\htdocs\trafanalyz\about_us.php
session_start();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us - TrafAnalyz</title>
    <link rel="stylesheet" href="styles.css">
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

    <main>
        <section class="about-us-content">
            <h2>About Us</h2>
            <p>
                Welcome to TrafAnalyz, a web-based system designed to complement existing analytics platforms by providing a user-friendly Web Traffic Analysis Dashboard. Our mission is to empower individuals, businesses, and organizations in Malaysia to effectively understand and utilize their website traffic data for strategic decision-making.
            </p>
            <p>
                In today's digital age, a strong online presence is crucial. Websites serve as vital tools for communication, commerce, and information sharing. Understanding how users interact with these platforms is essential for maximizing online visibility, enhancing user experience, and achieving strategic goals.
            </p>
            <p>
                TrafAnalyz aims to address the challenges faced by website owners and administrators in effectively utilizing their web traffic data. We understand that many organizations struggle with a lack of specialized resources and expertise, steep learning curves associated with current analytics tools, limited visualization options, and difficulties in contextual interpretation.
            </p>
            <p>
                Our solution is to provide a user-friendly dashboard that simplifies web traffic analysis. TrafAnalyz offers secure account access, seamless import of GA4 CSV data, interactive visualizations, comparative analysis features, annotation tools, and customizable views. For administrators, we provide tools to manage CSV data import formats and user accounts.
            </p>
            <p>
                We are committed to providing a powerful, yet accessible tool that helps our users make data-driven decisions, optimize their online presence, and ultimately achieve their goals.
            </p>
        </section>
    </main>

    <?php include 'footer.php'; ?>
</body>
</html>