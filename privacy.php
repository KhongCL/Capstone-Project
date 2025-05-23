<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Privacy Policy - TrafAnalyz</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        /* General content styling for readability */
        .content-section {
            padding: 4rem 2rem;
            max-width: 900px;
            margin: 0 auto;
            background-color: #ffffff;
            box-shadow: 0 0 20px rgba(0,0,0,0.05);
            border-radius: 8px;
            line-height: 1.6;
            color: #333;
        }

        .content-section h1 {
            color: #1e3c72;
            text-align: center;
            margin-bottom: 2rem;
            font-size: 2.5rem;
        }

        .content-section h2 {
            color: #4a6baf;
            margin-top: 2.5rem;
            margin-bottom: 1rem;
            font-size: 1.8rem;
        }

        .content-section p {
            margin-bottom: 1rem;
        }

        .content-section ul {
            list-style: disc;
            margin-left: 20px;
            margin-bottom: 1rem;
        }

        .content-section ul li {
            margin-bottom: 0.5rem;
        }

        .content-section a {
            color: #4a6baf;
            text-decoration: none;
        }

        .content-section a:hover {
            text-decoration: underline;
        }

        /* Hero Section Styles - Reused from privacy.php for consistent header */
        .hero {
            background: linear-gradient(135deg, #4a6baf 0%, #1e3c72 100%);
            color: white;
            padding: 5rem 2rem;
            text-align: center;
            position: relative;
            overflow: hidden;
            margin-bottom: 2rem; /* Added margin to separate from content */
        }
        
        .hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-image: url('images/loginbg.png');
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
        }
        
        .hero p {
            font-size: 1.2rem;
            margin-bottom: 2rem;
            line-height: 1.6;
        }
        
        /* Navigation - Reused from privacy.php */
        header {
            background-color: white;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
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
        
        /* Footer - Reused from privacy.php */
        footer {
            background-color: #1e3c72;
            color: white;
            padding: 2rem;
            text-align: center;
            margin-top: 2rem; /* Added margin to separate from content */
        }
        
        .footer-content {
            max-width: 800px;
            margin: 0 auto;
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
            <h1>Privacy Policy</h1>
            <p>Your privacy is important to us. This policy explains how we collect, use, and protect your information.</p>
        </div>
    </div>

    <div class="content-section">
        <h2>Introduction</h2>
        <p>TrafAnalyz is a complementary web traffic analysis dashboard designed to provide accessible web traffic insights. This Privacy Policy describes how TrafAnalyz collects, uses, and shares your information when you use our web-based system.</p>

        <h2>Information We Collect</h2>
        <p>We collect various types of information to provide and improve our service:</p>
        <ul>
            <li>**Personal Identification Information**: When you register and create a new account, we collect information such as your name and email address.</li>
            <li>**Account Management Information**: For administrators, we collect information necessary to view and manage registered users and their account statuses.</li>
            <li>**Uploaded Data**: As a core functionality, end-users can upload CSV files generated from GA4 reports. These files contain web traffic data. We process this data into meaningful information.</li>
            <li>**Usage Data**: We may collect information on how the service is accessed and used. This includes details like annotations added to traffic trend charts, and saved comparison setups.</li>
            <li>**Export History**: We track records of when users export data, including who performed the export and the timestamp.</li>
        </ul>

        <h2>How We Use Your Information</h2>
        <p>The information we collect is used for various purposes:</p>
        <ul>
            <li>To provide and maintain the TrafAnalyz system, including secure account access through registration and login.</li>
            <li>To process and analyze uploaded GA4 CSV data to provide meaningful insights and visualizations such as interactive trend charts and key performance indicators.</li>
            <li>To allow users to perform comparative analysis by uploading two CSV files and viewing side-by-side metrics.</li>
            <li>To enable features like adding, editing, and deleting annotations on traffic trend charts and saving preferred views.</li>
            <li>For administrators, to manage CSV data import formats, define column mappings, and manage user accounts (e.g., viewing status, suspending, restoring, and deleting users).</li>
            <li>To provide basic and enhanced export options for reports.</li>
            <li>To monitor the usage of the service and improve system functionalities.</li>
            <li>To ensure security with password hashing, secure session management, and protection against common web vulnerabilities.</li>
        </ul>

        <h2>Data Storage and Security</h2>
        <p>All data within the TrafAnalyz system is managed using a MySQL Database Management System. We are committed to ensuring the security of your data. We implement security measures such as password hashing and secure session management to protect against common web vulnerabilities like SQL Injection and XSS. While we strive to use commercially acceptable means to protect your Personal Data, we cannot guarantee its absolute security.</p>

        <h2>Data Retention</h2>
        <p>We retain your Personal Data and uploaded web traffic data only for as long as is necessary for the purposes set out in this Privacy Policy. We will retain and use your information to the extent necessary to comply with our legal obligations, resolve disputes, and enforce our legal agreements and policies.</p>

        <h2>Disclosure of Data</h2>
        <p>We do not sell, trade, or otherwise transfer to outside parties your Personally Identifiable Information unless we provide users with advance notice. This does not include website hosting partners and other parties who assist us in operating our website, conducting our business, or serving our users, so long as those parties agree to keep this information confidential. We may also release information when its release is appropriate to comply with the law, enforce our site policies, or protect ours or others' rights, property or safety.</p>

        <h2>Your Data Protection Rights</h2>
        <p>Depending on your location, you may have the following rights regarding your data:</p>
        <ul>
            <li>The right to access – You have the right to request copies of your personal data.</li>
            <li>The right to rectification – You have the right to request that we correct any information you believe is inaccurate or complete information you believe is incomplete.</li>
            <li>The right to erasure – You have the right to request that we erase your personal data, under certain conditions.</li>
            <li>The right to restrict processing – You have the right to request that we restrict the processing of your personal data, under certain conditions.</li>
            <li>The right to object to processing – You have the right to object to our processing of your personal data, under certain conditions.</li>
            <li>The right to data portability – You have the right to request that we transfer the data that we have collected to another organization, or directly to you, under certain conditions.</li>
        </ul>
        <p>If you make a request, we have one month to respond to you. If you would like to exercise any of these rights, please contact us.</p>

        <h2>Changes to This Privacy Policy</h2>
        <p>We may update our Privacy Policy from time to time. We will notify you of any changes by posting the new Privacy Policy on this page. You are advised to review this Privacy Policy periodically for any changes. Changes to this Privacy Policy are effective when they are posted on this page.</p>

        <h2>Contact Us</h2>
        <p>If you have any questions about this Privacy Policy, please contact us <a href="contact_us.php">here</a>.</p>
    </div>

    <footer>
        <div class="footer-content">
            <p>&copy; <?php echo date('Y'); ?> TrafAnalyz. All rights reserved.</p>
            <p>A complementary web traffic analysis dashboard for modern websites.</p>
        </div>
    </footer>
</body>
</html>