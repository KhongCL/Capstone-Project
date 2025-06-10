<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FAQ - TrafAnalyz</title>
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
                        <li><a href="user/index.php">User Dashboard</a></li>
                    <?php endif; ?>
                    <li><a href="logout.php">Logout</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </header>

    <main class="content-page">
        <div class="content-section">
            <h1>Frequently Asked Questions</h1>
            
            <div class="faq-item">
                <h3 class="faq-question">What is TrafAnalyz?</h3>
                <div class="faq-answer">
                    <p>TrafAnalyz is a comprehensive web traffic analysis dashboard that helps you understand your website visitors, their behavior patterns, and optimize your site's performance.</p>
                </div>
            </div>

            <div class="faq-item">
                <h3 class="faq-question">How do I get started?</h3>
                <div class="faq-answer">
                    <p>Simply create an account, upload your analytics data (CSV format), and start exploring your traffic insights through our intuitive dashboard.</p>
                </div>
            </div>

            <div class="faq-item">
                <h3 class="faq-question">What file formats are supported?</h3>
                <div class="faq-answer">
                    <p>Currently, we support CSV files from various analytics platforms including Google Analytics, Adobe Analytics, and other standard web analytics tools.</p>
                </div>
            </div>

            <div class="faq-item">
                <h3 class="faq-question">Is my data secure?</h3>
                <div class="faq-answer">
                    <p>Yes, we take data security seriously. All data is encrypted in transit and at rest, and we follow industry best practices for data protection.</p>
                </div>
            </div>

            <div class="faq-item">
                <h3 class="faq-question">Can I export my reports?</h3>
                <div class="faq-answer">
                    <p>Absolutely! You can export your analytics reports in various formats including PDF and CSV for presentations and further analysis.</p>
                </div>
            </div>

            <div class="faq-item">
                <h3 class="faq-question">How often can I upload new data?</h3>
                <div class="faq-answer">
                    <p>You can upload new data as frequently as needed. There are no restrictions on upload frequency for regular users.</p>
                </div>
            </div>

            <div class="faq-item">
                <h3 class="faq-question">Do you offer customer support?</h3>
                <div class="faq-answer">
                    <p>Yes, we provide customer support through our contact form. Our team typically responds within 24 hours during business days.</p>
                </div>
            </div>
        </div>
    </main>

    <?php include 'footer.php'; ?>
</body>
</html>