<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TrafAnalyz - Terms of Service</title>
    <link rel="stylesheet" href="styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* General Body and Font */
        body {
            font-family: 'Inter', sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f4f7f6;
            margin: 0;
            padding: 0;
        }

        /* Hero Section Styles (from original terms.php, adapted for consistency) */
        .hero {
            background: linear-gradient(135deg, #4a6baf 0%, #1e3c72 100%);
            color: white;
            padding: 5rem 2rem;
            text-align: center;
            position: relative;
            overflow: hidden;
            border-bottom-left-radius: 15px;
            border-bottom-right-radius: 15px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }
        
        .hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-image: url('images/loginbg.png'); /* Placeholder, ensure this path is correct or remove if not needed */
            background-size: cover;
            background-position: center;
            opacity: 0.15;
            z-index: 0;
        }
        
        .hero-content {
            position: relative;
            z-index: 1;
            max-width: 800px;
            margin: 0 auto;
        }
        
        .hero h1 {
            font-size: 3rem;
            margin-bottom: 1rem;
            line-height: 1.2;
            font-weight: 700;
        }
        
        .hero p {
            font-size: 1.2rem;
            margin-bottom: 2rem;
            line-height: 1.6;
            opacity: 0.9;
        }
        
        /* Navigation (from original terms.php) */
        header {
            background-color: white;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #eee;
        }
        
        .logo {
            display: flex;
            align-items: center;
            font-weight: 700;
            font-size: 1.5rem;
            color: #1e3c72;
            text-decoration: none;
        }
        
        .logo-icon {
            width: 30px;
            height: 30px;
            background-color: #4a6baf;
            border-radius: 6px;
            margin-right: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
        }
        
        nav ul {
            display: flex;
            gap: 1.5rem;
            list-style: none;
            margin: 0;
            padding: 0;
        }
        
        nav a {
            color: #333;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s;
        }
        
        nav a:hover {
            color: #4a6baf;
        }
        
        /* Main Content Section for Terms */
        .terms-container {
            max-width: 900px;
            margin: 3rem auto;
            padding: 2rem;
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
        }

        .terms-container h2 {
            color: #1e3c72;
            font-size: 2.2rem;
            margin-bottom: 1.5rem;
            text-align: center;
            font-weight: 700;
        }

        .terms-container h3 {
            color: #4a6baf;
            font-size: 1.6rem;
            margin-top: 2.5rem;
            margin-bottom: 1rem;
            font-weight: 600;
            border-bottom: 2px solid #e0e7ff;
            padding-bottom: 0.5rem;
        }

        .terms-container p {
            margin-bottom: 1rem;
            line-height: 1.7;
            font-size: 1rem;
        }

        .terms-container ul {
            list-style: disc;
            margin-left: 25px;
            margin-bottom: 1rem;
        }

        .terms-container ul li {
            margin-bottom: 0.5rem;
            line-height: 1.6;
        }

        /* Footer (from original terms.php) */
        footer {
            background-color: #1e3c72;
            color: white;
            padding: 2rem;
            text-align: center;
            margin-top: 4rem;
            border-top-left-radius: 15px;
            border-top-right-radius: 15px;
            box-shadow: 0 -4px 15px rgba(0, 0, 0, 0.1);
        }
        
        .footer-content {
            max-width: 800px;
            margin: 0 auto;
        }
        
        .footer-content p {
            margin: 0.5rem 0;
            font-size: 0.9rem;
            opacity: 0.9;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .hero h1 {
                font-size: 2.5rem;
            }
            .terms-container {
                margin: 2rem 1rem;
                padding: 1.5rem;
            }
            .terms-container h2 {
                font-size: 1.8rem;
            }
            .terms-container h3 {
                font-size: 1.4rem;
            }
            nav ul {
                gap: 1rem;
            }
            header {
                flex-direction: column;
                align-items: flex-start;
                padding: 1rem;
            }
            nav {
                margin-top: 1rem;
                width: 100%;
            }
            nav ul {
                flex-direction: column;
                align-items: flex-start;
                width: 100%;
            }
            nav ul li {
                width: 100%;
                text-align: left;
                padding: 0.5rem 0;
            }
        }
    </style>
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

    <div class="hero">
        <div class="hero-content">
            <h1>Terms of Service</h1>
            <p>Understand your rights and responsibilities when using TrafAnalyz.</p>
        </div>
    </div>

    <main class="terms-container">
        <h2>TrafAnalyz Terms of Service</h2>
        <p><em>Last updated: <?php echo date('F j, Y'); ?></em></p>

        <h3>1. Introduction</h3>
        <p>These Terms of Service ("Terms") govern your access to and use of TrafAnalyz, a complementary web analytics dashboard ("Service"). By accessing or using the Service, you agree to be bound by these Terms. If you do not agree with any part of these terms, you must not use the Service.</p>

        <h3>2. Service Description (TrafAnalyz)</h3>
        <p>TrafAnalyz is a web-based system designed to provide users with a practical tool to import, visualize, and understand web traffic data in a simple and accessible manner. It serves as a <strong>complementary tool</strong> to existing analytics platforms. Key functionalities include:</p>
        <ul>
            <li>Secure user account registration and login.</li>
            <li>Importing formatted Google Analytics 4 (GA4) CSV data for processing and visualization.</li>
            <li>Display of key web traffic metrics such as Sessions, Users, Engaged Sessions, Engagement Rate, Average Engagement Time per Session, Traffic Source Distribution, and Calculated Bounce Rate through interactive charts and widgets.</li>
            <li>Comparative period analysis by uploading two CSV files.</li>
            <li>Annotation tools on trend charts.</li>
            <li>Saving and loading comparison setups.</li>
            <li>Export options for data tables and dashboard views (CSV, basic PDF).</li>
            <li>Administrator functionalities for managing CSV import formats and user accounts.</li>
        </ul>

        <h3>3. User Responsibilities</h3>
        <p>When using TrafAnalyz, you agree to:</p>
        <ul>
            <li>Maintain the confidentiality of your account information, including your password.</li>
            <li>Upload only valid and properly formatted GA4 CSV data, specifically from the "Traffic acquisition: Session primary channel group (Default channel group)" report.</li>
            <li>Acknowledge that the accuracy and reliability of insights generated by the dashboard are directly dependent on the quality of the data you import.</li>
            <li>Ensure you have the necessary rights and permissions to upload and process the data within TrafAnalyz.</li>
            <li>Comply with all applicable laws and regulations regarding your use of the Service.</li>
        </ul>

        <h3>4. Data Usage and Privacy</h3>
        <p>TrafAnalyz processes web traffic data <strong>provided by you</strong> through CSV file uploads. The system does not directly integrate with external analytics platforms (e.g., Google Analytics 4) via APIs for automated data retrieval. Your uploaded data is used solely for the purpose of providing the visualization and analysis functionalities within the Service. We are committed to protecting your privacy and data security as outlined in our separate Privacy Policy (which you should consult for more details).</p>

        <h3>5. Limitations of Service</h3>
        <p>The TrafAnalyz system, within its defined scope, <strong>will not</strong> address or provide the following:</p>
        <ul>
            <li>Direct API integrations for automated data retrieval from external analytics platforms.</li>
            <li>Advanced statistical modeling or predictive forecasting.</li>
            <li>Customizable data collection setups.</li>
            <li>Business system integrations.</li>
            <li>Comprehensive role-based access control beyond basic End-User and Administrator roles.</li>
            <li>Handling of very large datasets beyond the supported CSV file upload limit (up to 5MB efficiently).</li>
            <li>Automated alerting mechanisms.</li>
            <li>A/B testing experiment analysis.</li>
            <li>Website optimization recommendations.</li>
            <li>Advanced user segmentation features.</li>
            <li>Real-time, continuously updating data analysis; the focus is on analyzing historical data from imported CSV files.</li>
        </ul>

        <h3>6. Assumptions</h3>
        <p>The Service operates under the following assumptions:</p>
        <ul>
            <li>Users will be able to export their web traffic data from Google Analytics 4 (specifically the “Traffic acquisition: Session primary channel group (Default channel group)” report) in a Comma Separated Value (CSV) format.</li>
            <li>A reasonable consistency in the fundamental structure and data types across imported CSV files is expected.</li>
            <li>The accuracy and reliability of insights are directly dependent on the quality of imported data, presuming the source analytics platforms provide accurate and representative data.</li>
            <li>Users will have a basic understanding of common web traffic metrics for effective use of the dashboard.</li>
            <li>Stable internet connectivity and access through a modern web browser supporting standard HTML, CSS, and JavaScript technologies are assumed for optimal system performance.</li>
        </ul>

        <h3>7. Account Management</h3>
        <ul>
            <li>You agree to provide accurate and complete information during registration.</li>
            <li>TrafAnalyz administrators have the right to view, suspend, restore, and delete user accounts as per system management policies and applicable laws.</li>
        </ul>

        <h3>8. Intellectual Property</h3>
        <p>All intellectual property rights in the TrafAnalyz system, including its design, code, and content, are owned by the project development team. You are granted a limited, non-exclusive, non-transferable license to use the Service solely for its intended purpose in accordance with these Terms.</p>

        <h3>9. Disclaimer of Warranties</h3>
        <p>The Service is provided "as is" and "as available" without any warranties of any kind, either express or implied, including, but not limited to, implied warranties of merchantability, fitness for a particular purpose, or non-infringement. We do not warrant that the Service will be uninterrupted, error-free, or secure.</p>

        <h3>10. Limitation of Liability</h3>
        <p>To the fullest extent permitted by law, the project development team shall not be liable for any indirect, incidental, special, consequential, or punitive damages, or any loss of profits or revenues, whether incurred directly or indirectly, or any loss of data, use, goodwill, or other intangible losses, resulting from (a) your access to or use of or inability to access or use the Service; (b) any conduct or content of any third party on the Service; or (c) unauthorized access, use, or alteration of your transmissions or content.</p>

        <h3>11. Governing Law</h3>
        <p>These Terms shall be governed by and construed in accordance with the laws of Malaysia, without regard to its conflict of law provisions.</p>

        <h3>12. Changes to Terms</h3>
        <p>We reserve the right to modify or replace these Terms at any time. If a revision is material, we will provide at least 30 days' notice prior to any new terms taking effect. By continuing to access or use our Service after those revisions become effective, you agree to be bound by the revised terms. If you do not agree to the new terms, please stop using the Service.</p>

        <h3>13. Contact Information</h3>
        <p>If you have any questions about these Terms, please contact us <a href="contact_us.php" style="color: #4a6baf; text-decoration: underline;">here</a>.</p>
    </main>
    
    <footer>
        <div class="footer-content">
            <p>&copy; <?php echo date('Y'); ?> TrafAnalyz. All rights reserved.</p>
            <p>A complementary web traffic analysis dashboard for modern websites.</p>
        </div>
    </footer>
</body>
</html>
