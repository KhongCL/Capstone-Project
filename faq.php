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
    <?php include 'header.php'; ?>

    <main class="content-page">
        <div class="faq-content">
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
                    <p>We currently support CSV files exported from Google Analytics 4 (GA4) and other major analytics platforms. Our system can automatically detect and map common CSV formats. For a complete list of supported formats and step-by-step export instructions, visit our <a href="user/supported_formats.php" style="color: #007bff; text-decoration: underline;">Supported CSV Formats page</a>.</p>
                </div>
            </div>

            <div class="faq-item">
                <h3 class="faq-question">Is my data secure?</h3>
                <div class="faq-answer">
                    <p>Yes, we take data security seriously. All uploaded data is encrypted and stored securely. We never share your analytics data with third parties.</p>
                </div>
            </div>

            <div class="faq-item">
                <h3 class="faq-question">Can I export my analysis results?</h3>
                <div class="faq-answer">
                    <p>Absolutely! You can export your charts, reports, and data tables in various formats including PDF, CSV, and image formats.</p>
                </div>
            </div>

            <div class="faq-item">
                <h3 class="faq-question">Do you offer customer support?</h3>
                <div class="faq-answer">
                    <p>Yes, we provide customer support through our contact form. Our team typically responds within 24 hours during business days.</p>
                </div>
            </div>

            <div class="faq-item">
                <h3 class="faq-question">I'm having trouble with my CSV upload. What should I do?</h3>
                <div class="faq-answer">
                    <p>If you're experiencing issues with CSV uploads, first check our <a href="user/supported_formats.php" style="color: #007bff; text-decoration: underline;">Supported CSV Formats guide</a> which includes troubleshooting tips and requirements for successful uploads. Make sure your file is under 5MB, properly formatted, and matches one of our supported formats.</p>
                </div>
            </div>
        </div>
    </main>

    <?php include 'footer.php'; ?>
</body>
</html>