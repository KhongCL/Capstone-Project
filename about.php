<?php

// Name: Lim Jia Jhen
// Position: Developer
// TP Number: TP077404
// Intake: UCDF2308ICT(SE)
// Project Name: TrafAnalyz - Complementary Web Analytics Dashboard
// Program Name: about.php
// Description: About page that provides information about TrafAnalyz system,
//              its mission, features, technology stack, and contact details.
// First Written On: 14 April 2025
// Edited On: 14 July 2025

session_start();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us - TrafAnalyz</title>
    <link rel="stylesheet" href="styles.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
		<?php include 'header.php'; ?>


    <main class="content-page">
        <div class="about-content">
            <h1>About TrafAnalyz</h1>

            <p>Welcome to TrafAnalyz, a web-based system designed to complement existing analytics platforms by providing a user-friendly Web Traffic Analysis Dashboard. Our mission is to empower individuals, businesses, and organizations in Malaysia to effectively understand and utilize their website traffic data for strategic decision-making.</p>

            <p>In today's digital age, a strong online presence is crucial. Websites serve as vital tools for communication, commerce, and information sharing. Understanding how users interact with these platforms is essential for maximizing online visibility, enhancing user experience, and achieving strategic goals.</p>

            <p>TrafAnalyz aims to address the challenges faced by website owners and administrators in effectively utilizing their web traffic data. We understand that many organizations struggle with a lack of specialized resources and expertise, steep learning curves associated with current analytics tools, limited visualization options, difficulties in contextual interpretation, and complex data analysis requirements.</p>

            <p>Our solution is to provide a user-friendly dashboard that simplifies web traffic analysis. TrafAnalyz offers secure account access and authentication, seamless import of GA4 CSV data, interactive visualizations and charts, comparative analysis features, annotation tools for data context, and customizable dashboard views for all users. For administrators, we provide CSV data import format management, user account administration, system configuration tools, and data validation and processing oversight.</p>

            <p>We are committed to providing a powerful, yet accessible tool that helps our users make data-driven decisions, optimize their online presence, and ultimately achieve their goals. TrafAnalyz bridges the gap between complex analytics platforms and user-friendly data visualization.</p>

            <p>TrafAnalyz is built using modern web technologies to ensure reliability, security, and performance. Our technology stack includes PHP backend for robust server-side processing, MySQL database for secure data storage, Chart.js for interactive data visualizations, responsive design for cross-device compatibility, and advanced CSV parsing and validation systems.</p>

            <p>Have questions about TrafAnalyz or need support? We're here to help. You can reach us by email at info@trafanalyz.com, by phone at +60 3-8996 1234, or visit us at Asia Pacific University of Technology & Innovation (APU), Kuala Lumpur, Malaysia.</p>
        </div>
    </main>

    <?php include 'footer.php'; ?>
</body>
</html>