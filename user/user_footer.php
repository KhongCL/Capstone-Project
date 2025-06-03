<style>
	/* Footer */
	footer {
	    background-color: #fff;
	    color: white;
	    padding: 2rem;
	    text-align: center;
	}
	
	.footer-content {
			color: var(--text-color);
	    max-width: 800px;
	    margin: 0 auto;
	}
	
	.footer-links {
	    margin-top: 1rem;
	    padding-top: 1rem;
	    border-top: 1px solid rgba(255, 255, 255, 0.2);
	}
	
	.footer-links a {
	    color: #4c78d0;
	    text-decoration: none;
	    margin: 0 1rem;
	    transition: color 0.3s;
	}
	
	.footer-links a:hover {
	    color: #3a5fb0;
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
        </div>
    </div>
</footer>