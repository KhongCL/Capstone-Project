<!-- 
Name: Mervin Ooi Zhian Yang
Position: Developer
TP Number: TP076578
Intake: UCDF2308ICT(SE)
Project Name: TrafAnalyz - Complementary Web Analytics Dashboard
Program Name: user_footer.php
Description: User dashboard footer component with navigation links, copyright information,
             and responsive styling for consistent footer display across all user pages.
First Written On: 14 April 2025
Edited On: 12 July 2025 
-->

<style>
	/* Footer */
	footer {
			background-color: #fff;
			color: white;
			padding: 2rem;
			text-align: center;
			margin-bottom: 0;
			border-radius: 10px;
			margin-top: 2rem;
			z-index: 100;
			box-shadow: var(--shadow);
	}
	
	.footer-content {
			color: var(--text-color);
			max-width: 800px;
			margin: 0 auto;
	}

	.footer-links a {
			color: var(--primary-color);
			text-decoration: none;
			margin: 0 1rem;
			transition: color 0.5s;
	}

	.footer-links a:hover {
			color: var(--primary-dark);
			text-decoration: underline;
	}
</style>

<footer>
    <div class="footer-content">
        <p>&copy; <?php echo date('Y'); ?> TrafAnalyz. All rights reserved.</p>
        <p>A complementary web traffic analysis dashboard for modern websites.</p>
        <div class="footer-links">
            <a href="../about.php">About Us</a>
            <a href="../privacy.php">Privacy Policy</a>
            <a href="../terms.php">Terms & Conditions</a>
            <a href="../contact_us.php">Contact Us</a>
            <a href="../faq.php">FAQ</a>
            <a href="../supported_formats.php">Supported Formats</a>
        </div>
    </div>
</footer>