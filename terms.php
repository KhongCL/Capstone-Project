<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Terms & Conditions - TrafAnalyz</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <header>
        <a href="index.php" class="logo">
            <div class="logo-icon">T</div>
            TrafAnalyz
        </a>
        <nav>
            <ul>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <?php if ($_SESSION['role'] === 'Admin'): ?>
                        <li><a href="admin/index.php">Admin Dashboard</a></li>
                    <?php else: ?>
                        <li><a href="user/index.php">Dashboard</a></li>
                    <?php endif; ?>
                    <li><a href="logout.php">Logout</a></li>
                <?php else: ?>
                    <li><a href="login.php">Login</a></li>
                    <li><a href="register.php">Register</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </header>

    <main class="content-page">
        <div class="terms-content">
            <h1>Terms & Conditions</h1>
            
            <div class="effective-date">
                <strong>Effective Date:</strong> November 2024
            </div>

            <h2>1. Acceptance of Terms</h2>
            <p>By accessing and using TrafAnalyz, you accept and agree to be bound by the terms and provision of this agreement. If you do not agree to abide by the above, please do not use this service.</p>

            <h2>2. Use License</h2>
            <p>Permission is granted to temporarily download one copy of TrafAnalyz for personal, non-commercial transitory viewing only. This is the grant of a license, not a transfer of title, and under this license you may not:</p>
            <ul>
                <li>Modify or copy the materials</li>
                <li>Use the materials for any commercial purpose or for any public display (commercial or non-commercial)</li>
                <li>Attempt to decompile or reverse engineer any software contained on TrafAnalyz</li>
                <li>Remove any copyright or other proprietary notations from the materials</li>
            </ul>

            <h2>3. Disclaimer</h2>
            <p>The materials on TrafAnalyz are provided on an 'as is' basis. TrafAnalyz makes no warranties, expressed or implied, and hereby disclaims and negates all other warranties including without limitation, implied warranties or conditions of merchantability, fitness for a particular purpose, or non-infringement of intellectual property or other violation of rights.</p>

            <h2>4. Limitations</h2>
            <p>In no event shall TrafAnalyz or its suppliers be liable for any damages (including, without limitation, damages for loss of data or profit, or due to business interruption) arising out of the use or inability to use the materials on TrafAnalyz, even if TrafAnalyz or an authorized representative has been notified orally or in writing of the possibility of such damage.</p>

            <h2>5. Accuracy of Materials</h2>
            <p>The materials appearing on TrafAnalyz could include technical, typographical, or photographic errors. TrafAnalyz does not warrant that any of the materials on its website are accurate, complete, or current.</p>

            <h2>6. Links</h2>
            <p>TrafAnalyz has not reviewed all of the sites linked to our website and is not responsible for the contents of any such linked site. The inclusion of any link does not imply endorsement by TrafAnalyz of the site.</p>

            <h2>7. Modifications</h2>
            <p>TrafAnalyz may revise these terms of service for its website at any time without notice. By using this website you are agreeing to be bound by the then current version of these terms of service.</p>

            <h2>8. Privacy Policy</h2>
            <p>Your privacy is important to us. Please review our Privacy Policy, which also governs your use of the website, to understand our practices.</p>

            <h2>9. Governing Law</h2>
            <p>These terms and conditions are governed by and construed in accordance with the laws of Malaysia and you irrevocably submit to the exclusive jurisdiction of the courts in that State or location.</p>

            <h2>10. Contact Information</h2>
            <p>If you have any questions about these Terms & Conditions, please contact us at:</p>
            <ul>
                <li>Email: info@trafanalyz.com</li>
                <li>Phone: +60 3-8996 1234</li>
                <li>Address: Asia Pacific University of Technology & Innovation (APU), Kuala Lumpur, Malaysia</li>
            </ul>
        </div>
    </main>

    <?php include 'footer.php'; ?>
</body>
</html>