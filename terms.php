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
            <p>In no event shall TrafAnalyz or its suppliers be liable for any damages (including, without limitation, damages for loss of data or profit, or due to business interruption) arising out of the use or inability to use TrafAnalyz, even if TrafAnalyz or an authorized representative has been notified orally or in writing of the possibility of such damage.</p>

            <h2>5. Privacy Policy</h2>
            <p>Your privacy is important to us. Please review our Privacy Policy, which also governs your use of the service, to understand our practices.</p>

            <h2>6. User Accounts</h2>
            <p>When you create an account with us, you must provide information that is accurate, complete, and current at all times. You are responsible for safeguarding the password and for all activities that occur under your account.</p>

            <h2>7. Data Upload and Processing</h2>
            <p>Users are responsible for ensuring they have the right to upload and analyze data through TrafAnalyz. Users must comply with all applicable data protection laws and regulations when using our service.</p>

            <h2>8. Service Modifications</h2>
            <p>TrafAnalyz reserves the right to modify or discontinue the service at any time without prior notice. We shall not be liable to you or any third party for any modification, suspension, or discontinuance of the service.</p>

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