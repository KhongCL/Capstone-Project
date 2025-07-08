<style>
	  /* Footer */
    footer {
        background-color: #1e3c72;
        color: white;
        padding: 2rem;
        text-align: center;
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
</style>

<footer>
    <div class="footer-content">
        <p>&copy; <?php echo date('Y'); ?> TrafAnalyz. All rights reserved.</p>
        <p>A complementary web traffic analysis dashboard for modern websites.</p>
        <div class="footer-links">
            <a href="/trafanalyz/about.php">About Us</a>
            <a href="/trafanalyz/privacy.php">Privacy Policy</a>
            <a href="/trafanalyz/terms.php">Terms & Conditions</a>
            <a href="/trafanalyz/contact_us.php">Contact Us</a>
            <a href="/trafanalyz/faq.php">FAQ</a>
        </div>
    </div>
</footer>