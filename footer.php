<!-- 
Name: Mervin Ooi Zhian Yang
Position: Developer
TP Number: TP076578
Intake: UCDF2308ICT(SE)
Project Name: TrafAnalyz - Complementary Web Analytics Dashboard
Program Name: footer.php
Description: Footer component included across all pages containing copyright information,
             navigation links, and styling for consistent site-wide footer design.
First Written On: 16 April 2025
Edited On: 14 July 2025 
-->

<style>
		footer {
		    background-color: #1e3c72;
		    color: white;
		    padding: 2rem;
		    text-align: center;
		    border-radius: 1rem 1rem 0 0 !important;
		    margin-bottom: 0 !important;
		}

    .footer-content {
            color: white;
        max-width: 800px;
        margin: 0 auto;
    }
    .footer-links {
        margin-top: 1rem;
        padding-top: 1rem;
        border-top: 1px solid rgba(255, 255, 255, 0.2);
    }
    .footer-links a {
            color:rgb(235, 235, 235);
            text-decoration: none;
            margin: 0 0.5rem;
            transition: color 0.3s;
    }
    .footer-links a:hover {
            color: #ffffff;
            text-decoration: underline;
    }
    
    body {
        padding-bottom: 0 !important;
    }
    
    .container:has(footer),
    footer .container {
        max-width: 100% !important;
        padding-left: 0 !important;
        padding-right: 0 !important;
        margin-bottom: 0 !important;
    }
</style>

<footer>
    <div class="footer-content">
        <p>&copy; <?php echo date('Y'); ?> TrafAnalyz. All rights reserved.</p>
        <p>A complementary web traffic analysis dashboard for modern websites.</p>
        <div class="footer-links">
            <a href="about.php">About Us</a>
            <a href="privacy.php">Privacy Policy</a>
            <a href="terms.php">Terms & Conditions</a>
            <a href="contact_us.php">Contact Us</a>
            <a href="faq.php">FAQ</a>
            <a href="supported_formats.php">Supported Formats</a>
        </div>
    </div>
</footer>