<?php
session_start();
// ...existing code...
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <!-- ...existing meta and title... -->
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

        /* Contact Us Section */
        #contact-us {
            padding: 50px 20px;
            background-color: #f9f9f9;
        }

        #contact-us .container {
            max-width: 1200px;
            margin: 0 auto;
            text-align: center;
        }

        #contact-us h2 {
            font-size: 2.5em;
            color: #333;
            margin-bottom: 40px;
        }

        #contact-block {
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            gap: 30px;
        }

        .contact-box {
            background-color: #fff;
            border: 1px solid #ddd;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            flex: 1;
            min-width: 280px;
            max-width: 350px;
            text-align: center;
            transition: transform 0.3s ease-in-out;
        }

        .contact-box:hover {
            transform: translateY(-10px);
        }

        .contact-box h3 {
            font-size: 1.5em;
            color: #007bff; /* A nice blue for headings */
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .contact-box .icon {
            font-size: 1.8em;
            margin-right: 10px;
            color: #007bff;
        }

        .contact-box p {
            font-size: 1.1em;
            color: #555;
            line-height: 1.6;
            margin-bottom: 10px;
        }

        .contact-box p:last-child {
            margin-bottom: 0;
        }

        .contact-box a {
            color: #007bff;
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .contact-box a:hover {
            color: #0056b3;
            text-decoration: underline;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            #contact-block {
                flex-direction: column;
                align-items: center;
            }

            .contact-box {
                width: 90%;
                max-width: 400px;
            }
        }

        @media (max-width: 480px) {
            #contact-us h2 {
                font-size: 2em;
            }

            .contact-box h3 {
                font-size: 1.3em;
            }

            .contact-box p {
                font-size: 1em;
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

    <section id="contact-us">
        <div class="container">
            <h2>Connect with the TrafAnalyz Team</h2>
            <div id="contact-block">
                <div class="contact-box">
                    <h3><i class="fas fa-phone-alt icon fa-flip-horizontal"></i> Project Inquiries</h3>
                    <p>+60 3-8996 1234 (Project Lead)</p>
                    <p>+60 3-8996 5678 (Technical Support)</p>
                    <p>+60 3-8996 9012 (General Assistance)</p>
                </div>
                <div class="contact-box">
                    <h3><i class="fas fa-envelope icon"></i> Email Us </h3>
                    <p><a href="mailto:info@trafanalyz.com">info@trafanalyz.com</a></p>
                    <p><a href="mailto:support@trafanalyz.com">support@trafanalyz.com</a></p>
                    <p><a href="mailto:feedback@trafanalyz.com">feedback@trafanalyz.com</a></p>
                </div>
                <div class="contact-box">
                    <h3><i class="fas fa-map-marker-alt icon"></i> Project Base </h3>
                    <p>Asia Pacific University of Technology & Innovation (APU)</p>
                    <p>Jalan Teknologi 5, Taman Teknologi Malaysia,</p>
                    <p>57000 Kuala Lumpur, Wilayah Persekutuan Kuala Lumpur, Malaysia</p>
                </div>
            </div>
        </div>
        <div style="height: 75px;"></div>
    </section>

    <?php include 'footer.php'; ?>
</body>
</html>