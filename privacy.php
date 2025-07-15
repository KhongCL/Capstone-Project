<?php

// Name: Lim Jia Jhen
// Position: Developer
// TP Number: TP077404
// Intake: UCDF2308ICT(SE)
// Project Name: TrafAnalyz - Complementary Web Analytics Dashboard
// Program Name: privacy.php
// Description: Privacy policy page displaying data collection, usage, security policies,
//              and user rights information for TrafAnalyz analytics platform.
// First Written On: 14 April 2025
// Edited On: 14 July 2025

session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Privacy Policy - TrafAnalyz</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <?php include 'header.php'; ?>

    <main class="content-page">
        <div class="privacy-content">
            <h1>Privacy Policy</h1>
            
            <div class="effective-date">
                <strong>Effective Date:</strong> November 2024
            </div>

            <h2>1. Information We Collect</h2>
            <p>We collect information you provide directly to us, such as when you create an account, upload data, or contact us for support.</p>
            
            <h3>Personal Information</h3>
            <ul>
                <li>Name and contact information</li>
                <li>Account credentials</li>
                <li>Communication preferences</li>
            </ul>

            <h3>Analytics Data</h3>
            <ul>
                <li>CSV files you upload for analysis</li>
                <li>Website traffic data</li>
                <li>User interaction data within our platform</li>
            </ul>

            <h2>2. How We Use Your Information</h2>
            <p>We use the information we collect to:</p>
            <ul>
                <li>Provide, operate, and maintain our services</li>
                <li>Process and analyze your uploaded data</li>
                <li>Communicate with you about our services</li>
                <li>Improve our platform and user experience</li>
                <li>Comply with legal obligations</li>
            </ul>

            <h2>3. Information Sharing</h2>
            <p>We do not sell, trade, or otherwise transfer your personal information to third parties except as described in this policy.</p>

            <h2>4. Data Security</h2>
            <p>We implement appropriate security measures to protect your personal information against unauthorized access, alteration, disclosure, or destruction.</p>

            <h2>5. Data Retention</h2>
            <p>We retain your information for as long as necessary to provide our services and fulfill the purposes outlined in this policy.</p>

            <h2>6. Your Rights</h2>
            <p>You have the right to access, update, or delete your personal information. Contact us to exercise these rights.</p>

            <h2>7. Cookies</h2>
            <p>We use cookies to enhance your experience on our platform. You can control cookie settings through your browser.</p>

            <h2>8. Third-Party Services</h2>
            <p>Our platform may contain links to third-party websites. We are not responsible for their privacy practices.</p>

            <h2>9. Changes to This Policy</h2>
            <p>We may update this privacy policy from time to time. We will notify you of any material changes by posting the new policy on this page and updating the effective date.</p>

            <h2>10. Contact Us</h2>
            <p>If you have any questions about this Privacy Policy, please contact us at:</p>
            <ul>
                <li>Email: privacy@trafanalyz.com</li>
                <li>Phone: +60 3-8996 1234</li>
                <li>Address: Asia Pacific University of Technology & Innovation (APU), Kuala Lumpur, Malaysia</li>
            </ul>
        </div>
    </main>

    <?php include 'footer.php'; ?>
</body>
</html>