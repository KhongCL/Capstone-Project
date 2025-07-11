<?php
session_start();

// Redirect logged-in users to their appropriate dashboard
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'Admin') {
        header("Location: admin/index.php");
    } else {
        header("Location: user/index.php");
    }
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TrafAnalyz - Web Traffic Analysis Dashboard</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="spotlight-cards.css">
    <link rel="stylesheet" href="animated-squares.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="page-with-squares">
    <!-- Overall page background squares -->
    <div class="squares-background">
        <canvas id="page-squares" class="squares-canvas"></canvas>
    </div>

    <?php include 'header.php'; ?>

    <section class="hero hero-with-squares">
        <div class="squares-background">
            <canvas id="hero-squares" class="squares-canvas"></canvas>
        </div>
        <div class="hero-content">
            <h1>Analyze Your Web Traffic with Ease</h1>
            <p>TrafAnalyz provides powerful web analytics tools to help you understand your visitors, their behavior, and optimize your website performance.</p>
            <div class="cta-buttons">
                <a href="login.php" class="cta-button login-btn">Login</a>
                <a href="register.php" class="cta-button register-btn">Create Account</a>
            </div>
        </div>
    </section>

    <section class="features">
        <div class="container">
            <h2>Why Choose TrafAnalyz?</h2>
            <div class="features-grid">
                <div class="feature-card feature-card-spotlight">
                    <i class="fas fa-chart-line"></i>
                    <h3>Real-time Analytics</h3>
                    <p>Get instant insights into your website traffic with real-time data processing and visualization.</p>
                </div>
                <div class="feature-card feature-card-spotlight">
                    <i class="fas fa-users"></i>
                    <h3>User Behavior Tracking</h3>
                    <p>Understand how visitors interact with your site through detailed user journey analysis.</p>
                </div>
                <div class="feature-card feature-card-spotlight">
                    <i class="fas fa-mobile-alt"></i>
                    <h3>Mobile Responsive</h3>
                    <p>Access your analytics dashboard from any device with our fully responsive design.</p>
                </div>
                <div class="feature-card feature-card-spotlight">
                    <i class="fas fa-shield-alt"></i>
                    <h3>Secure & Private</h3>
                    <p>Your data is protected with enterprise-grade security and privacy measures.</p>
                </div>
                <div class="feature-card feature-card-spotlight">
                    <i class="fas fa-download"></i>
                    <h3>Export Reports</h3>
                    <p>Generate and export detailed reports in various formats for presentations and analysis.</p>
                </div>
                <div class="feature-card feature-card-spotlight">
                    <i class="fas fa-cog"></i>
                    <h3>Easy Setup</h3>
                    <p>Get started in minutes with our simple setup process and intuitive interface.</p>
                </div>
            </div>
        </div>
    </section>

    <?php include 'footer.php'; ?>

    <script src="spotlight-cards.js"></script>
    <script src="animated-squares.js"></script>
</body>
</html>
