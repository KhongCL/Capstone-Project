<?php
// filepath: c:\xampp\htdocs\trafanalyz\admin_register.php
session_start();
require_once 'config.php';

// Verify admin key for secure access
$admin_key = "trafanalyz";
if (!isset($_GET['key']) || $_GET['key'] !== $admin_key) {
    die("Access denied. Admin registration requires proper authorization.");
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get and sanitize form data
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm-password'];
    $created_at = date('Y-m-d H:i:s');

    // Validation
    $errors = [];

    // Username validation
    if (!preg_match('/^[a-zA-Z0-9_]{5,20}$/', $username)) {
        $errors[] = "Username must be 5-20 characters and contain only letters, numbers, and underscores.";
    }

    // Email validation
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please enter a valid email address.";
    }

    // Password validation
    if (!preg_match('/^(?=.*[A-Za-z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/', $password)) {
        $errors[] = "Password must be at least 8 characters and contain at least one letter, one number, and one special character.";
    }

    // Password confirmation
    if ($password !== $confirm_password) {
        $errors[] = "Passwords do not match.";
    }

    // Check if username or email already exists
    $check_sql = "SELECT Username, Email FROM USER WHERE Username = ? OR Email = ?";
    $stmt = $conn->prepare($check_sql);
    $stmt->bind_param("ss", $username, $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            if ($row['Username'] === $username) {
                $errors[] = "Username already exists.";
            }
            if ($row['Email'] === $email) {
                $errors[] = "Email already exists.";
            }
        }
    }

    // If no errors, proceed with registration
    if (empty($errors)) {
        // Hash the password
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
    
        // Prepare INSERT statement - specifically with Admin role
        $sql = "INSERT INTO USER (Username, Email, PasswordHash, Role, AccountStatus, CreatedAt) 
                VALUES (?, ?, ?, 'Admin', 'Active', ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssss", $username, $email, $password_hash, $created_at);
    
        if ($stmt->execute()) {
            // Registration successful
            $_SESSION['register_success'] = true;
            
            // Show popup for 2 seconds then redirect
            header("refresh:2;url=admin_login.php?key=$admin_key");
            
            // Don't exit - let the page render with popup
        } else {
            $errors[] = "Registration failed. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Registration - TrafAnalyz</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue', sans-serif;
        }

        body {
            background-color: #1e293b;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }

        .register-container {
            display: flex;
            background-color: white;
            border-radius: 20px;
            overflow: hidden;
            width: 90%;
            max-width: 1200px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        .register-form {
            flex: 1;
            padding: 50px;
            display: flex;
            flex-direction: column;
        }

        .register-image {
            flex: 1;
            background-image: url('images/registerbg1.png');
            background-size: cover;
            background-position: center;
            min-height: 500px;
        }

        .logo {
            display: flex;
            align-items: center;
            margin-bottom: 40px;
        }

        .logo-icon {
            width: 24px;
            height: 24px;
            border: 2px solid #9333ea;
            border-radius: 50%;
            margin-right: 10px;
            position: relative;
        }

        .logo-icon::before {
            content: "";
            position: absolute;
            width: 10px;
            height: 10px;
            background-color: #9333ea;
            border-radius: 50%;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
        }

        .logo-text {
            color: #333;
            font-weight: 600;
            font-size: 18px;
        }

        .admin-badge {
            display: inline-block;
            background-color: #9333ea;
            color: white;
            padding: 5px 10px;
            border-radius: 10px;
            font-size: 14px;
            margin-left: 10px;
        }

        h1 {
            font-size: 32px;
            font-weight: 700;
            color: #222;
            margin-bottom: 10px;
        }

        .subtitle {
            color: #666;
            margin-bottom: 30px;
        }

        .form-group {
            margin-bottom: 20px;
            position: relative;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #555;
            font-weight: 500;
        }

        .form-row {
            display: flex;
            gap: 15px;
        }

        .form-row .form-group {
            flex: 1;
        }

        input[type="text"],
        input[type="email"],
        input[type="password"] {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 15px;
            outline: none;
            transition: border-color 0.3s;
        }

        input[type="text"]:focus,
        input[type="email"]:focus,
        input[type="password"]:focus {
            border-color: #9333ea;
        }

        .password-field {
            position: relative;
        }

        .toggle-password {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            color: #999;
        }

        .register-btn {
            background-color: #9333ea;
            color: white;
            border: none;
            border-radius: 8px;
            padding: 14px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.3s;
            margin-top: 20px;
            margin-bottom: 20px;
        }

        .register-btn:hover {
            background-color: #7e22ce;
        }

        .sign-in {
            text-align: center;
            color: #666;
            margin-top: 20px;
        }

        .sign-in a {
            color: #9333ea;
            text-decoration: none;
            font-weight: 600;
        }

        .sign-in a:hover {
            text-decoration: underline;
        }

        .error-bubble {
            position: absolute;
            background-color: #ff4444;
            color: white;
            padding: 8px 12px;
            border-radius: 6px;
            font-size: 14px;
            margin-top: 5px;
            z-index: 100;
            max-width: 250px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }

        .error-bubble::before {
            content: '';
            position: absolute;
            top: -6px;
            left: 10px;
            border-left: 6px solid transparent;
            border-right: 6px solid transparent;
            border-bottom: 6px solid #ff4444;
        }

        .input-error {
            border-color: #ff4444 !important;
        }

        .success-popup {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) scale(0.7);
            background: white;
            padding: 30px 40px;
            border-radius: 15px;
            box-shadow: 0 5px 30px rgba(0,0,0,0.2);
            text-align: center;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
            z-index: 1000;
        }

        .success-popup.show {
            opacity: 1;
            visibility: visible;
            transform: translate(-50%, -50%) scale(1);
        }

        .success-popup img {
            width: 80px;
            height: 80px;
            margin-bottom: 20px;
            animation: bounce 0.6s ease;
        }

        .success-popup h2 {
            color: #333;
            margin-bottom: 10px;
        }

        .overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
        }

        .overlay.show {
            opacity: 1;
            visibility: visible;
        }

        @keyframes bounce {
            0%, 20%, 50%, 80%, 100% {
                transform: translateY(0);
            }
            40% {
                transform: translateY(-30px);
            }
            60% {
                transform: translateY(-15px);
            }
        }

        @media (max-width: 768px) {
            .register-container {
                flex-direction: column;
            }

            .register-form {
                padding: 30px;
            }

            .register-image {
                min-height: 300px;
                order: -1;
            }
        }

        .form-note {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            color: #666;
            font-size: 14px;
            line-height: 1.5;
            border-left: 4px solid #9333ea;
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="register-form">
            <div class="logo">
                <div class="logo-icon"></div>
                <div class="logo-text">TrafAnalyz <span class="admin-badge">Admin</span></div>
            </div>

            <h1>Create Admin Account</h1>
            <p class="subtitle">Register to access administrative features and manage the system</p>
            
            <div class="form-note">
                <strong>Note:</strong> This form is for administrator accounts only. Regular users should register through the standard registration page.
            </div>

            <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>?key=<?php echo $admin_key; ?>" onsubmit="return validateForm()">
                <?php if (!empty($errors)): ?>
                    <script>
                        // Store error data to be processed after DOM is loaded
                        var serverErrors = <?php echo json_encode($errors); ?>;
                        
                        document.addEventListener('DOMContentLoaded', function() {
                            // Function to show error messages
                            function showError(inputId, message) {
                                const input = document.getElementById(inputId);
                                if (!input) return;
                                
                                const errorBubble = document.createElement('div');
                                errorBubble.className = 'error-bubble';
                                errorBubble.textContent = message;
                                input.classList.add('input-error');
                                input.parentElement.appendChild(errorBubble);
                            }
                            
                            // Process each error and show the error bubbles
                            serverErrors.forEach(function(error) {
                                if (error.indexOf("Username") !== -1) {
                                    showError("username", error);
                                } else if (error.indexOf("Email") !== -1) {
                                    showError("email", error);
                                } else if (error.indexOf("match") !== -1) {
                                    showError("confirm-password", error);
                                } else if (error.indexOf("Password") !== -1) {
                                    showError("password", error);
                                } else {
                                    showError("username", error);
                                }
                            });
                        });
                    </script>
                <?php endif; ?>

                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" placeholder="Choose a username" required>
                </div>

                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" placeholder="Enter your email" required>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="password-field">
                        <input type="password" id="password" name="password" placeholder="Create a strong password" required>
                        <button type="button" class="toggle-password" onclick="togglePassword('password')">👁️</button>
                    </div>
                </div>

                <div class="form-group">
                    <label for="confirm-password">Confirm Password</label>
                    <div class="password-field">
                        <input type="password" id="confirm-password" name="confirm-password" placeholder="Confirm your password" required>
                        <button type="button" class="toggle-password" onclick="togglePassword('confirm-password')">👁️</button>
                    </div>
                </div>

                <div class="form-group">
                    <label>
                        <input type="checkbox" id="terms" name="terms" required>
                        I confirm that I am authorized to create an admin account
                    </label>
                </div>

                <button type="submit" class="register-btn">Create Admin Account</button>
            </form>

            <div class="sign-in">
                Already have an admin account? <a href="admin_login.php?key=<?php echo $admin_key; ?>">Sign In</a>
            </div>
            <div class="sign-in" style="margin-top: 10px;">
                <a href="login.php">Go to User Login</a>
            </div>
        </div>
        <div class="register-image"></div>
    </div>

    <div class="overlay" id="overlay"></div>
    <div class="success-popup" id="successPopup">
        <img src="images/success.png" alt="Success">
        <h2>Admin Registration Successful!<br>Redirecting to login page...</h2>
    </div>

    <script>
        // Toggle password visibility
        function togglePassword(id) {
            const input = document.getElementById(id);
            const button = input.nextElementSibling;
            
            if (input.type === 'password') {
                input.type = 'text';
                button.textContent = '🔒';
            } else {
                input.type = 'password';
                button.textContent = '👁️';
            }
        }
        
        // Form validation
        function validateForm() {
            let isValid = true;
            
            // Remove any existing error bubbles
            document.querySelectorAll('.error-bubble').forEach(bubble => bubble.remove());
            document.querySelectorAll('.input-error').forEach(input => input.classList.remove('input-error'));
            
            // Function to show errors
            function showError(inputId, message) {
                const input = document.getElementById(inputId);
                const errorBubble = document.createElement('div');
                errorBubble.className = 'error-bubble';
                errorBubble.textContent = message;
                input.classList.add('input-error');
                input.parentElement.appendChild(errorBubble);
                isValid = false;
            }
            
            // Get form values
            const username = document.getElementById('username').value;
            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm-password').value;
            const terms = document.getElementById('terms').checked;
            
            // Validate username
            if (!/^[a-zA-Z0-9_]{5,20}$/.test(username)) {
                showError('username', 'Username must be 5-20 characters and contain only letters, numbers, and underscores');
            }
            
            // Validate email
            if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                showError('email', 'Please enter a valid email address');
            }
            
            // Validate password
            if (!/^(?=.*[A-Za-z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/.test(password)) {
                showError('password', 'Password must be at least 8 characters with at least one letter, one number, and one special character');
            }
            
            // Confirm passwords match
            if (password !== confirmPassword) {
                showError('confirm-password', 'Passwords do not match');
            }
            
            // Check terms
            if (!terms) {
                showError('terms', 'You must confirm that you are authorized');
            }
            
            return isValid;
        }
        
        // Show success popup if registration successful
        <?php if (isset($_SESSION['register_success']) && $_SESSION['register_success']): ?>
        document.addEventListener('DOMContentLoaded', function() {
            const overlay = document.getElementById('overlay');
            const popup = document.getElementById('successPopup');
            
            // Show overlay and popup with animation
            overlay.classList.add('show');
            popup.classList.add('show');
            
            // Clear the session flag
            <?php unset($_SESSION['register_success']); ?>
        });
        <?php endif; ?>
    </script>
</body>
</html>