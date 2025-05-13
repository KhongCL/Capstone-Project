<?php
session_start();
require_once 'config.php';

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
    
        // Prepare INSERT statement - removed FullName from SQL
        $sql = "INSERT INTO USER (Username, Email, PasswordHash, Role, AccountStatus, CreatedAt) 
                VALUES (?, ?, ?, 'End-User', 'Active', ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssss", $username, $email, $password_hash, $created_at);
    
        if ($stmt->execute()) {
            // Registration successful
            $_SESSION['register_success'] = true;
            
            // Show popup for 2 seconds then redirect
            header("refresh:2;url=login.php");
            
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
    <title>Register - Create an Account</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue', sans-serif;
        }

        body {
            background-color: #f0f0f0;
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
            background-size: 600px auto;  
            background-position: center;
            background-repeat: no-repeat;
            min-height: 500px;
            background-color: #f8f9fa;
        }

        .logo {
            display: flex;
            align-items: center;
            margin-bottom: 40px;
        }

        .logo-icon {
            width: 24px;
            height: 24px;
            border: 2px solid #007bff;
            border-radius: 50%;
            margin-right: 10px;
            position: relative;
        }

        .logo-icon::before {
            content: "";
            position: absolute;
            width: 10px;
            height: 10px;
            background-color: #007bff;
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
            border-color: #007bff;
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
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 8px;
            padding: 14px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.3s;
            margin-top: 10px;
            margin-bottom: 20px;
            width: 100%;
        }

        .register-btn:hover {
            background-color: #0069d9;
        }

        .sign-in {
            text-align: center;
            color: #666;
            margin-top: 20px;
        }

        .sign-in a {
            color: #007bff;
            text-decoration: none;
            font-weight: 600;
        }

        .sign-in a:hover {
            text-decoration: underline;
        }

        .footer {
            margin-top: auto;
            display: flex;
            justify-content: space-between;
            color: #999;
            font-size: 14px;
            padding-top: 30px;
        }

        .footer a {
            color: #999;
            text-decoration: none;
        }

        .footer a:hover {
            color: #007bff;
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

            .form-group {
                position: relative;
                margin-bottom: 20px;
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
                min-height: 250px;
                order: -1;
            }

        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="register-form">
            <div class="logo">
                <div class="logo-icon"></div>
                <div class="logo-text">TrafAnalyz</div>
            </div>

            <h1>Create an Account</h1>
            <p class="subtitle">Join now to analyse your web traffic data 📊</p>

            <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
            <?php
            if (!empty($errors)) {
                echo '<script>';
                foreach ($errors as $error) {
                    if (strpos($error, "Username") !== false) {
                        echo 'showError("username", "' . addslashes($error) . '");';
                    } else if (strpos($error, "Email") !== false) {
                        echo 'showError("email", "' . addslashes($error) . '");';
                    } else if (strpos($error, "Password") !== false) {
                        if (strpos($error, "match") !== false) {
                            echo 'showError("confirm-password", "' . addslashes($error) . '");';
                        } else {
                            echo 'showError("password", "' . addslashes($error) . '");';
                        }
                    }
                }
                echo '</script>';
            }
            ?>
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" placeholder="Enter your username" required>
                </div>

                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" placeholder="Enter your email" required>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="password-field">
                        <input type="password" id="password" name="password" placeholder="Create a password" required>
                        <button type="button" class="toggle-password">👁️</button>
                    </div>
                </div>

                <div class="form-group">
                    <label for="confirm-password">Confirm Password</label>
                    <div class="password-field">
                        <input type="password" id="confirm-password" name="confirm-password" placeholder="Confirm your password" required>
                        <button type="button" class="toggle-password">👁️</button>
                    </div>
                </div>

                <button type="submit" class="register-btn">Register</button>
            </form>

            <div class="sign-in">
                Already Have An Account? <a href="login.php">Sign In</a>
            </div>

            <div class="footer">
                <div>Copyright © 2025 TrafAnalyz Enterprises SDN BHD.</div>
                <div><a href="#">Privacy Policy</a></div>
            </div>
        </div>
        <div class="register-image"></div>
    </div>

    <script>
        // Remove any existing error bubbles
        function removeErrorBubbles() {
            document.querySelectorAll('.error-bubble').forEach(bubble => bubble.remove());
            document.querySelectorAll('.input-error').forEach(input => input.classList.remove('input-error'));
        }

        // Show error bubble for a specific input
        function showError(inputId, message) {
            const input = document.getElementById(inputId);
            const errorBubble = document.createElement('div');
            errorBubble.className = 'error-bubble';
            errorBubble.textContent = message;
            input.classList.add('input-error');
            input.parentElement.appendChild(errorBubble);
        }

        // Toggle password visibility
        document.querySelectorAll('.toggle-password').forEach(button => {
            button.addEventListener('click', function() {
                const input = this.previousElementSibling;
                if (input.type === 'password') {
                    input.type = 'text';
                    this.textContent = '🔒';
                } else {
                    input.type = 'password';
                    this.textContent = '👁️';
                }
            });
        });
        
        // Form validation and submission
        document.querySelector('form').addEventListener('submit', function(e) {
            e.preventDefault();
            removeErrorBubbles();
            
            // Get form values
            const username = document.getElementById('username').value;
            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm-password').value;

            let isValid = true;

            // Username validation
            if (!/^[a-zA-Z0-9_]{5,20}$/.test(username)) {
                showError('username', "Username must be 5-20 characters and contain only letters, numbers, and underscores.");
                isValid = false;
            }

            // Email validation
            if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                showError('email', "Please enter a valid email address.");
                isValid = false;
            }

            // Password validation
            if (!/^(?=.*[A-Za-z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/.test(password)) {
                showError('password', "Password must be at least 8 characters and contain at least one letter, one number, and one special character.");
                isValid = false;
            }

            // Confirm password
            if (password !== confirmPassword) {
                showError('confirm-password', "Passwords do not match.");
                isValid = false;
            }

            if (isValid) {
                // If validation passes, submit the form
                this.submit();
            }
        });

        // Remove error when user starts typing
        document.querySelectorAll('input').forEach(input => {
            input.addEventListener('input', function() {
                const errorBubble = this.parentElement.querySelector('.error-bubble');
                if (errorBubble) {
                    errorBubble.remove();
                    this.classList.remove('input-error');
                }
            });
        });

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

        <div class="overlay" id="overlay"></div>
        <div class="success-popup" id="successPopup">
            <img src="images/success.png" alt="Success">
            <h2>Registration Successful!
                <br>Redirecting to login page...</h2>
            </h2>
        </div>
</body>
</body>
</html>