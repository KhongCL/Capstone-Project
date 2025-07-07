<?php
session_start();
require_once 'config.php';

// Initialize variables
$errors = [];

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'Admin') {
        header("Location: admin/index.php");
    } else {
        header("Location: user/index.php");
    }
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // Validate input
    if (empty($username)) {
        $errors['username'] = 'Username is required';
    }
    
    if (empty($password)) {
        $errors['password'] = 'Password is required';
    }
    
    // Process login if no errors
    if (empty($errors)) {
        // FIXED: Use correct column name PasswordHash
        $stmt = $conn->prepare("SELECT UserID, Username, PasswordHash, Role, AccountStatus FROM user WHERE Username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($user = $result->fetch_assoc()) {
            if ($user['AccountStatus'] === 'Suspended') {
                $errors['general'] = 'Your account has been suspended. Please contact support.';
            } elseif (password_verify($password, $user['PasswordHash'])) { // FIXED: Use PasswordHash
                $_SESSION['user_id'] = $user['UserID'];
                $_SESSION['username'] = $user['Username'];
                $_SESSION['role'] = $user['Role'];
                $_SESSION['login_success'] = true;
                
                // Redirect based on role
                if ($user['Role'] === 'Admin') {
                    header("Location: admin/index.php");
                } else {
                    header("Location: user/index.php");
                }
                exit();
            } else {
                $errors['general'] = 'Invalid username or password';
            }
        } else {
            $errors['general'] = 'Invalid username or password';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - TrafAnalyz</title>
    <link rel="stylesheet" href="styles.css">
    <script>
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
            // Process server errors
            <?php if (!empty($errors)): ?>
            var serverErrors = <?php echo json_encode($errors); ?>;
            
            removeErrorBubbles();
            
            Object.keys(serverErrors).forEach(function(field) {
                if (field !== 'general') {
                    showError(field === 'username' ? 'usernameInput' : 'passwordInput', serverErrors[field]);
                }
            });
            <?php endif; ?>
            
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
            
            if (overlay && popup) {
                overlay.classList.add('show');
                popup.classList.add('show');
                
                // Redirect in the same tab after showing popup briefly
                setTimeout(function() {
                    <?php if ($_SESSION['role'] === 'Admin'): ?>
                        window.location.href = 'admin/index.php';
                    <?php else: ?>
                        window.location.href = 'user/index.php';
                    <?php endif; ?>
                }, 1500); // Show popup for 1.5 seconds then redirect
            }
            
            <?php unset($_SESSION['login_success']); ?>
        });
        <?php endif; ?>
    </script>
</head>
<body style="background-color: #1e293b; display: flex; justify-content: center; align-items: center; min-height: 100vh;">
    <div class="auth-container">
        <div class="auth-form">
            <div class="logo">
                <div class="logo-icon"></div>
                <div class="logo-text">TrafAnalyz</div>
            </div>

            <h1>Welcome Back</h1>
            <p class="welcome-text">Sign in to access your analytics dashboard</p>

            <?php if (isset($errors['general'])): ?>
                <div style="background-color: #f8d7da; color: #721c24; padding: 12px; border-radius: 6px; margin-bottom: 20px;">
                    <?php echo htmlspecialchars($errors['general']); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                <div class="form-group">
                    <label for="usernameInput">Username</label>
                    <input type="text" id="usernameInput" name="username" placeholder="Enter your username" required>
                </div>

                <div class="form-group">
                    <label for="passwordInput">Password</label>
                    <div class="password-field">
                        <input type="password" id="passwordInput" name="password" placeholder="Enter your password" required>
                        <span class="password-toggle" onclick="togglePassword()">👁️</span>
                    </div>
                </div>

                <div class="remember-forgot">
                    <div class="remember">
                        <input type="checkbox" id="remember" name="remember">
                        <label for="remember">Remember me</label>
                    </div>
                </div>

                <button type="submit" class="auth-btn">Sign In</button>
            </form>

            <div class="sign-up">
                Don't have an account? <a href="register.php">Create Account</a>
            </div>
        </div>
        <div class="auth-image"></div>
    </div>

    <div class="overlay" id="overlay"></div>
    <div class="success-popup" id="successPopup">
        <img src="images/success.png" alt="Success">
        <h2>Login Successful!</h2>
    </div>
</body>
</html>