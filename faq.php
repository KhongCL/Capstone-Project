<?php
session_start();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TrafAnalyz - Frequently Asked Questions</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        /* Copied and adapted styles from index.php */
        /* Hero Section Styles */
        .hero {
            background: linear-gradient(135deg, #4a6baf 0%, #1e3c72 100%);
            color: white;
            padding: 5rem 2rem;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-image: url('images/loginbg.png'); /* Ensure this path is correct */
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

        .hero h1 { /* Adjusted for FAQ hero */
            font-size: 3rem;
            margin-bottom: 1rem;
            line-height: 1.2;
        }

        .hero p { /* Adjusted for FAQ hero */
            font-size: 1.2rem;
            margin-bottom: 2rem;
            line-height: 1.6;
        }

        /* Features Section (adapted for FAQ sections) */
        .faq-section { /* Renamed from .features to .faq-section */
            padding: 4rem 2rem;
            background-color: #f8f9fa;
        }

        .faq-section h2 { /* Renamed from .features h2 */
            text-align: center;
            margin-bottom: 3rem;
            color: #333;
        }

        .faq-grid { /* Optional: if you want a grid layout for FAQs */
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            max-width: 1200px;
            margin: 0 auto;
            margin-bottom: 3rem; /* Add margin below each grid section for separation */
        }

        .faq-item { /* Styled similar to .feature-card */
            background: white;
            border-radius: 10px;
            padding: 2rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s ease;
            /* margin-bottom: 2rem; Removed as gap in grid handles spacing */
        }

        .faq-item:hover {
            transform: translateY(-5px);
        }

        .faq-question { /* Styled similar to .feature-card h3 */
            color: #1e3c72;
            margin-bottom: 1rem;
            font-size: 1.2rem;
            font-weight: 600;
        }

        .faq-answer {
            line-height: 1.6;
            color: #555; /* Softer color for answers */
        }

        /* Navigation, Logo, Footer styles (copied directly from index.php) */
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

        footer {
            background-color: #1e3c72;
            color: white;
            padding: 2rem;
            text-align: center;
        }

        .footer-content {
            max-width: 800px;
            margin: 0 auto;
        }

        /* Responsive Design (copied directly from index.php) */
        @media (max-width: 768px) {
            .hero h1 {
                font-size: 2.5rem;
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

    <section class="hero">
        <div class="hero-content">
            <h1>TrafAnalyz: Frequently Asked Questions</h1>
            <p>Welcome to TrafAnalyz! We've put together some common questions to help you get started and make the most of our platform.</p>
        </div>
    </section>

    <section class="faq-section">
        <h2>Getting Started</h2>
        <div class="faq-grid">
            <div class="faq-item">
                <p class="faq-question">What is TrafAnalyz?</p>
                <p class="faq-answer">TrafAnalyz is a web traffic analysis system designed to help you understand your website's performance by analyzing data from your GA4 reports. You can upload CSV files, visualize key metrics, and compare traffic over different periods.</p>
            </div>
            <div class="faq-item">
                <p class="faq-question">How do I create an account?</p>
                <p class="faq-answer">You can easily register for a new account by clicking on the "Register" or "Sign Up" button on the homepage. Follow the on-screen instructions to set up your secure account.</p>
            </div>
            <div class="faq-item">
                <p class="faq-question">How do I log in to my account?</p>
                <p class="faq-answer">Once registered, simply click on the "Login" button and enter your registered email address and password to securely access your dashboard.</p>
            </div>
            <div class="faq-item">
                <p class="faq-question">Is TrafAnalyz user-friendly for non-technical users?</p>
                <p class="faq-answer">Absolutely! TrafAnalyz is designed with an intuitive and user-friendly dashboard interface, clear instructions, and straightforward navigation to make it accessible for everyone, regardless of technical expertise.</p>
            </div>
        </div>

        <h2>Uploading and Managing Data</h2>
        <div class="faq-grid">
            <div class="faq-item">
                <p class="faq-question">What kind of files can I upload to TrafAnalyz?</p>
                <p class="faq-answer">TrafAnalyz accepts CSV files generated directly from your Google Analytics 4 (GA4) reports.</p>
            </div>
            <div class="faq-item">
                <p class="faq-question">Are there any limitations on the size of CSV files I can upload?</p>
                <p class="faq-answer">Yes, TrafAnalyz efficiently supports CSV file uploads up to 5MB without performance degradation.</p>
            </div>
            <div class="faq-item">
                <p class="faq-question">How do I upload a CSV file?</p>
                <p class="faq-answer">After logging in, navigate to the dashboard where you'll find an option to "Upload CSV File." Select your GA4 CSV file from your computer, and the system will begin processing it.</p>
            </div>
            <div class="faq-item">
                <p class="faq-question">What happens if my uploaded CSV file has errors or an incorrect format?</p>
                <p class="faq-answer">TrafAnalyz validates the structure and contents of uploaded CSV files. If any issues are detected, you will see clear and actionable error messages to guide you in correcting the file.</p>
            </div>
            <div class="faq-item">
                <p class="faq-question">Will I know the progress of my CSV file upload and parsing?</p>
                <p class="faq-answer">Yes, TrafAnalyz displays progress indicators during CSV file uploads and parsing, so you can track the status of your data processing.</p>
            </div>
            <div class="faq-item">
                <p class="faq-question">Can I try TrafAnalyz without uploading my own data?</p>
                <p class="faq-answer">Yes! TrafAnalyz provides a sample data option that allows you to explore the dashboard and practice using the features without needing to upload your own GA4 reports.</p>
            </div>
        </div>

        <h2>Analyzing Your Traffic Data</h2>
        <div class="faq-grid">
            <div class="faq-item">
                <p class="faq-question">What web traffic metrics does TrafAnalyz calculate and display?</p>
                <p class="faq-answer">TrafAnalyz calculates and displays key web traffic metrics including: Sessions, Users, Engaged Sessions, Engagement Rate, Average Engagement Time per Session, Traffic Source Distribution, and calculated Bounce Rate.</p>
            </div>
            <div class="faq-item">
                <p class="faq-question">How are traffic metrics presented?</p>
                <p class="faq-answer">Traffic metrics are presented through interactive trend charts for historical analysis, and key performance indicators are displayed using Number Card widgets for quick overviews.</p>
            </div>
            <div class="faq-item">
                <p class="faq-question">How can I compare traffic between two periods?</p>
                <p class="faq-answer">TrafAnalyz allows you to perform comparative period analysis. Simply upload two separate CSV files (one for each period you wish to compare), and you can view side-by-side metrics on your dashboard.</p>
            </div>
            <div class="faq-item">
                <p class="faq-question">How does TrafAnalyz visualize traffic source distribution?</p>
                <p class="faq-answer">Traffic source distribution is visualized effectively using pie or bar charts, giving you a clear understanding of where your website visitors are coming from.</p>
            </div>
            <div class="faq-item">
                <p class="faq-question">Can I see a detailed list of my top traffic sources?</p>
                <p class="faq-answer">Yes, TrafAnalyz provides a table format where you can list and filter your top traffic sources.</p>
            </div>
            <div class="faq-item">
                <p class="faq-question">Can I filter the displayed charts and tables?</p>
                <p class="faq-answer">Absolutely. You can filter displayed charts and tables based on selected traffic sources to focus on specific areas of interest.</p>
            </div>
            <div class="faq-item">
                <p class="faq-question">Can I add notes or observations to my traffic trend charts?</p>
                <p class="faq-answer">Yes, you can add, edit, and delete annotations directly on your traffic trend charts, allowing you to mark significant events or changes.</p>
            </div>
            <div class="faq-item">
                <p class="faq-question">Can I save my comparison setups?</p>
                <p class="faq-answer">Yes, TrafAnalyz allows you to save and load your comparison setups for future use, making it easy to revisit past analyses.</p>
            </div>
        </div>

        <h2>Exporting Data and Reports</h2>
        <div class="faq-grid">
            <div class="faq-item">
                <p class="faq-question">How can I export data from TrafAnalyz?</p>
                <p class="faq-answer">You can export data tables and your current dashboard views to CSV files for further analysis or record-keeping.</p>
            </div>
            <div class="faq-item">
                <p class="faq-question">Can I export my dashboard view as a PDF?</p>
                <p class="faq-answer">Yes, TrafAnalyz allows you to export your dashboard view (including charts and tables) into a basic PDF format.</p>
            </div>
            <div class="faq-item">
                <p class="faq-question">How long does it take to generate exported files?</p>
                <p class="faq-answer">Our export features (CSV and PDF) are designed to generate files quickly, typically within 3 to 10 seconds after your request.</p>
            </div>
        </div>

        <h2>Technical and Security</h2>
        <div class="faq-grid">
            <div class="faq-item">
                <p class="faq-question">What technologies is TrafAnalyz built on?</p>
                <p class="faq-answer">TrafAnalyz is developed using HTML, CSS, JavaScript, PHP, and ensures data is managed using the MySQL Database Management System.</p>
            </div>
            <div class="faq-item">
                <p class="faq-question">Is my data secure with TrafAnalyz?</p>
                <p class="faq-answer">Yes, security is a top priority. TrafAnalyz ensures security with password hashing, secure session management, and protection against common web vulnerabilities such as SQL Injection and XSS.</p>
            </div>
            <div class="faq-item">
                <p class="faq-question">Is TrafAnalyz compatible with different web browsers?</p>
                <p class="faq-answer">Yes, TrafAnalyz is cross-browser compatible, supporting the latest versions of Chrome, Firefox, Edge, and Safari.</p>
            </div>
            <div class="faq-item">
                <p class="faq-question">What is TrafAnalyz's expected response time for loading data?</p>
                <p class="faq-answer">The TrafAnalyz system is optimized for performance, with a response time expected to be within 1 to 5 seconds for loading charts, tables, and dashboards.</p>
            </div>
        </div>
    </section>

    <footer>
        <div class="footer-content">
            <p>&copy; <?php echo date('Y'); ?> TrafAnalyz. All rights reserved.</p>
            <p>A complementary web traffic analysis dashboard for modern websites.</p>
        </div>
    </footer>
</body>
</html>