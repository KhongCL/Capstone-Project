<!-- 
Name: Mervin Ooi Zhian Yang
Position: Developer
TP Number: TP076578
Intake: UCDF2308ICT(SE)
Project Name: TrafAnalyz - Complementary Web Analytics Dashboard
Program Name: admin_footer.php
Description: Admin footer component included across all admin pages containing copyright
             information, navigation links, and styling for consistent admin footer design.
First Written On: 20 April 2025
Edited On: 14 July 2025 
-->

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