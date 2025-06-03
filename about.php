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
    <style>
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
        
        .cta-buttons {
            display: flex;
            justify-content: center;
            gap: 1rem;
            margin-top: 2rem;
        }
        
        .cta-button {
            padding: 0.8rem 2rem;
            font-size: 1.1rem;
            font-weight: 600;
            border-radius: 30px;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        .login-btn {
            background-color: white;
            color: #1e3c72;
            border: 2px solid white;
        }
        
        .login-btn:hover {
            background-color: transparent;
            color: white;
        }
        
        .register-btn {
            background-color: transparent;
            color: white;
            border: 2px solid white;
        }
        
        .register-btn:hover {
            background-color: white;
            color: #1e3c72;
        }
        
        /* Features Section */
        .features {
            padding: 4rem 2rem;
            background-color: #f8f9fa;
        }
        
        .features h2 {
            text-align: center;
            margin-bottom: 3rem;
            color: #333;
        }
        
        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .feature-card {
            background: white;
            border-radius: 10px;
            padding: 2rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s ease;
        }
        
        .feature-card:hover {
            transform: translateY(-5px);
        }
        
        .feature-card h3 {
            color: #1e3c72;
            margin-bottom: 1rem;
        }
        
        .feature-icon {
            font-size: 2rem;
            margin-bottom: 1rem;
            color: #4a6baf;
        }
        
        /* Navigation */
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
      
        /* Responsive Design */
        @media (max-width: 768px) {
            .hero h1 {
                font-size: 2.5rem;
            }
            
            .cta-buttons {
                flex-direction: column;
                align-items: center;
            }
            
            .cta-button {
                width: 100%;
                max-width: 300px;
                text-align: center;
            }
        }
        
        /* Custom styles for about_us.php - adjust as needed */
        .about-us-content {
            padding: 4rem 2rem;
            max-width: 1200px;
            margin: 0 auto;
            text-align: center; /* Center the content */
        }
        
        .about-us-content h2 {
            font-size: 2.5rem;
            margin-bottom: 2rem;
            color: #1e3c72;
        }
        
        .about-us-content p {
            font-size: 1.1rem;
            line-height: 1.7;
            color: #333;
        }
        
        .about-us-content p:last-child {
            margin-bottom: 0; /* Remove bottom margin from the last paragraph */
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