<?php
// filepath: c:\xampp\htdocs\trafanalyz\admin_login.php
session_start();
require_once 'config.php';

// Verify admin key for secure access
$admin_key = "trafanalyz";
if (!isset($_GET['key']) || $_GET['key'] !== $admin_key) {
    die("Access denied. Admin area requires proper authorization.");
}

$errors = [];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    // Validate inputs
    if (empty($username)) {
        $errors[] = "Username is required.";
    }
    if (empty($password)) {
        $errors[] = "Password is required.";
    }

    if (empty($errors)) {
        // Check user credentials - ONLY for Admin role
        $sql = "SELECT UserID, Username, PasswordHash, Role FROM USER WHERE Username = ? AND Role = 'Admin'";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            if (password_verify($password, $user['PasswordHash'])) {
                // Login successful
                $_SESSION['user_id'] = $user['UserID'];
                $_SESSION['username'] = $user['Username'];
                $_SESSION['role'] = $user['Role'];
                
                // Set a flag to show success popup
                $_SESSION['login_success'] = true;
                
                // Redirect to admin dashboard
                header("refresh:2;url=admin/index.php");
            } else {
                $errors[] = "Invalid admin credentials.";
            }
        } else {
            $errors[] = "Invalid admin credentials or you don't have admin privileges.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TrafAnalyz Admin Login</title>

    <script>
        // Define functions first
        function removeErrorBubbles() {
            document.querySelectorAll('.error-bubble').forEach(bubble => bubble.remove());
            document.querySelectorAll('.input-error').forEach(input => input.classList.remove('input-error'));
        }

        function showError(inputId, message) {
            const input = document.getElementById(inputId);
            const errorBubble = document.createElement('div');
            errorBubble.className = 'error-bubble';
            errorBubble.textContent = message;
            input.classList.add('input-error');
            input.parentElement.appendChild(errorBubble);
        }

        function togglePassword() {
            const passwordInput = document.getElementById('passwordInput');
            const toggleBtn = document.querySelector('.password-toggle');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleBtn.textContent = '🔒';
            } else {
                passwordInput.type = 'password';
                toggleBtn.textContent = '👁️';
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
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
        });
        
        <?php if (isset($_SESSION['login_success']) && $_SESSION['login_success']): ?>
        document.addEventListener('DOMContentLoaded', function() {
            const overlay = document.getElementById('overlay');
            const popup = document.getElementById('successPopup');
            
            // Show overlay and popup with animation
            overlay.classList.add('show');
            popup.classList.add('show');
            
            // Clear the session flag
            <?php unset($_SESSION['login_success']); ?>
        });
        <?php endif; ?>
    </script>

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

        .login-container {
            display: flex;
            background-color: white;
            border-radius: 20px;
            overflow: hidden;
            width: 90%;
            max-width: 1200px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        .login-form {
            flex: 1;
            padding: 60px;
            display: flex;
            flex-direction: column;
        }

        .login-image {
            flex: 1;
            background-image: url('images/loginbg.png');
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
            width: 20px;
            height: 20px;
            background-color: #9333ea;
            border-radius: 4px;
            margin-right: 10px;
        }

        .logo-text {
            color: #333;
            font-weight: 600;
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
            font-size: 42px;
            font-weight: 700;
            color: #222;
            margin-bottom: 10px;
        }

        .welcome-text {
            color: #666;
            margin-bottom: 40px;
        }

        .form-group {
            margin-bottom: 20px;
            position: relative;
        }

        input[type="text"],
        input[type="password"] {
            width: 100%;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
            outline: none;
            transition: border-color 0.3s;
        }

        input[type="text"]:focus,
        input[type="password"]:focus {
            border-color: #9333ea;
        }

        .remember-forgot {
            display: flex;
            justify-content: flex-start;
            align-items: center;
            margin-bottom: 30px;
        }

        .remember {
            display: flex;
            align-items: center;
        }

        .remember input {
            margin-right: 8px;
            accent-color: #9333ea;
        }

        .password-toggle {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #666;
            user-select: none;
        }

        .sign-in-btn {
            background-color: #9333ea;
            color: white;
            border: none;
            border-radius: 8px;
            padding: 15px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.3s;
            margin-bottom: 30px;
        }

        .sign-in-btn:hover {
            background-color: #7e22ce;
        }

        .sign-up {
            text-align: center;
            color: #666;
        }

        .sign-up a {
            color: #9333ea;
            text-decoration: none;
            font-weight: 600;
        }

        .sign-up a:hover {
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
            .login-container {
                flex-direction: column;
            }

            .login-form {
                padding: 30px;
            }

            .login-image {
                min-height: 300px;
                order: -1;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-form">
            <div class="logo">
                <div class="logo-icon"></div>
                <div class="logo-text">TrafAnalyz <span class="admin-badge">Admin</span></div>
            </div>

            <h1>Admin Login</h1>
            <p class="welcome-text">Access the administrative dashboard to manage users and system settings</p>

            <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>?key=<?php echo $admin_key; ?>">
                <?php if (!empty($errors)): ?>
                    <script>
                        // Store error data to be processed after DOM is loaded
                        var serverErrors = <?php echo json_encode($errors); ?>;
                        
                        document.addEventListener('DOMContentLoaded', function() {
                            // Process each error and show the error bubbles
                            serverErrors.forEach(function(error) {
                                if (error.indexOf("Username") !== -1) {
                                    showError("username", error);
                                } else if (error.indexOf("Password") !== -1 && error.indexOf("required") !== -1) {
                                    showError("passwordInput", error);
                                } else {
                                    // For invalid credentials message
                                    showError("username", error);
                                }
                            });
                        });
                    </script>
                <?php endif; ?>
                <div class="form-group">
                    <input type="text" id="username" name="username" placeholder="Admin Username" required>
                </div>
                <div class="form-group">
                    <input type="password" id="passwordInput" name="password" placeholder="Password" required>
                    <span class="password-toggle" onclick="togglePassword()">👁️</span>
                </div>

                <div class="remember-forgot">
                    <label class="remember">
                        <input type="checkbox" name="remember">
                        Remember me
                    </label>
                </div>

                <button type="submit" class="sign-in-btn">Administrator Sign In</button>
            </form>

            <div class="sign-up">
                Need an admin account? <a href="admin_register.php?key=<?php echo $admin_key; ?>">Register Here</a>
            </div>
            <div class="sign-up" style="margin-top: 10px;">
                <a href="login.php">Go to User Login</a>
            </div>
        </div>
        <div class="login-image"></div>
    </div>

    <div class="overlay" id="overlay"></div>
    <div class="success-popup" id="successPopup">
        <img src="images/success.png" alt="Success">
        <h2>Admin Login Successful!</h2>
    </div>
</body>
</html>