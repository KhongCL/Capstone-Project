<?php
// filepath: c:\xampp\htdocs\trafanalyz\index.php
session_start();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TrafAnalyz - Web Traffic Analysis Dashboard</title>
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
        
        /* Footer */
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
            <h1>Analyze Your Web Traffic with Ease</h1>
            <p>TrafAnalyz provides powerful web analytics tools to help you understand your visitors, their behavior, and optimize your website performance.</p>
            <div class="cta-buttons">
                <a href="login.php" class="cta-button login-btn">Login</a>
                <a href="register.php" class="cta-button register-btn">Create Account</a>
            </div>
        </div>
    </section>

    <section class="features">
        <h2>Why Choose TrafAnalyz?</h2>
        <div class="features-grid">
            <div class="feature-card">
                <div class="feature-icon">📊</div>
                <h3>Interactive Charts</h3>
                <p>Visualize your traffic data with beautiful, interactive charts that help you identify trends and patterns.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">🔄</div>
                <h3>Comparative Analysis</h3>
                <p>Compare traffic data from different time periods to better understand your website's growth and performance.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">📱</div>
                <h3>Source Tracking</h3>
                <p>Discover where your visitors are coming from and which marketing channels are most effective.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">📝</div>
                <h3>Annotation Feature</h3>
                <p>Add annotations to your traffic charts to mark important events and track their impact on your metrics.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">💾</div>
                <h3>CSV Integration</h3>
                <p>Easily import your Google Analytics data via CSV files for quick and seamless analysis.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">⬇️</div>
                <h3>Export Tools</h3>
                <p>Export your analytics data and visualizations in various formats for reporting and presentation.</p>
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