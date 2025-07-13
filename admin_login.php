<?php
session_start();
require_once 'config.php';

// Check for admin key
$admin_key = $_GET['key'] ?? '';
if ($admin_key !== 'trafanalyz') {
    displayAccessDeniedMessage();
}

if (isset($_SESSION['role']) && $_SESSION['role'] === 'End-User') {
        displayAccessDeniedMessage();		
}

function displayAccessDeniedMessage() {
        echo '<!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Access Denied</title>
    </head>
    <body>
        <div class="access-denied">
            <h2>Access Denied</h2>
            <p>Access denied. Admin area requires proper authorization.</p>
            <a href="index.php">Return to Homepage</a>
        </div>
    </body>
    </html>';
        exit();
}

// Initialize variables
$errors = [];

// Redirect if already logged in as admin
if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'Admin') {
    header("Location: admin/index.php");
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
        // FIXED: Only check for Admin users in admin login
        $stmt = $conn->prepare("SELECT UserID, Username, PasswordHash, Role, AccountStatus FROM user WHERE Username = ? AND Role = 'Admin'");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($user = $result->fetch_assoc()) {
            if ($user['AccountStatus'] === 'Suspended') {
                $errors['general'] = 'Your admin account has been suspended. Please contact support.';
            } elseif (password_verify($password, $user['PasswordHash'])) {
                $_SESSION['user_id'] = $user['UserID'];
                $_SESSION['username'] = $user['Username'];
                $_SESSION['role'] = $user['Role'];
                
                // Only redirect to admin area since we verified Admin role
                header("Location: admin/index.php");
                exit();
            } else {
                $errors['general'] = 'Invalid admin credentials. Please check your username and password.';
            }
        } else {
            // ENHANCED: More specific error message for admin login
            $errors['general'] = 'Invalid admin credentials. This login is for administrators only.';
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
    <link rel="stylesheet" href="styles.css">
    <style>


    </style>
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
                
                // FIXED: Use window.location.replace() instead of href for same-tab redirect
                setTimeout(function() {
                    window.location.replace('admin/index.php');
                }, 1500);
            }
            
            <?php unset($_SESSION['login_success']); ?>
        });
        <?php endif; ?>
    </script>
</head>
<body style="display: flex; justify-content: center; align-items: center;">
    <div class="auth-container">
        <div class="auth-form">
            <div class="logo">
                <img src="images/logo2.png" alt="TrafAnalyz Logo" class="logo-image">
                <span class="admin-badge">Admin</span>
            </div>

            <h1>Admin Login</h1>
            <p class="welcome-text">Access the administrative dashboard to manage users and system settings</p>

            <?php if (isset($errors['general'])): ?>
                <div style="background-color: #f8d7da; color: #721c24; padding: 12px; border-radius: 6px; margin-bottom: 20px;">
                    <?php echo htmlspecialchars($errors['general']); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>?key=<?php echo $admin_key; ?>">
                <div class="form-group">
                    <label for="usernameInput">Admin Username</label>
                    <input type="text" id="usernameInput" name="username" placeholder="Enter admin username" required>
                </div>

                <div class="form-group">
                    <label for="passwordInput">Admin Password</label>
                    <div class="password-field">
                        <input type="password" id="passwordInput" name="password" placeholder="Enter admin password" required>
                        <span class="password-toggle" onclick="togglePassword()">👁️</span>
                    </div>
                </div>

                <button type="submit" class="auth-btn">Administrator Sign In</button>
            </form>

            <div class="sign-up">
                Need an admin account? <a href="admin_register.php?key=<?php echo $admin_key; ?>">Register Here</a>
            </div>
            <div class="sign-up" style="margin-top: 10px;">
                <a href="login.php">Go to User Login</a> | <a href="index.php">Return to Home Page</a>
            </div>
        </div>
        <div class="auth-image"></div>
    </div>

    <div class="overlay" id="overlay"></div>
    <div class="success-popup" id="successPopup">
        <img src="images/success.png" alt="Success">
        <h2>Admin Login Successful!</h2>
    </div>
</body>
</html>
