<?php
session_start();
require_once 'config.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'Admin') {
        header("Location: admin/index.php");
    } else {
        header("Location: user/index.php");
    }
    exit();
}

// Initialize variables
$errors = [];
$success = false;

// Initialize form variables
$firstname = '';
$lastname = '';
$username = '';
$email = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstname = trim($_POST['firstname'] ?? '');
    $lastname = trim($_POST['lastname'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm-password'] ?? '';
    $terms = isset($_POST['terms']);
    
    // Validate input
    if (empty($firstname)) {
        $errors[] = 'First name is required';
    }
    
    if (empty($lastname)) {
        $errors[] = 'Last name is required';
    }
    
    if (empty($username)) {
        $errors[] = 'Username is required';
    } elseif (strlen($username) < 3) {
        $errors[] = 'Username must be at least 3 characters';
    }
    
    if (empty($email)) {
        $errors[] = 'Email is required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address';
    }
    
    if (empty($password)) {
        $errors[] = 'Password is required';
    } elseif (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters';
    }
    
    if ($password !== $confirmPassword) {
        $errors[] = 'Passwords do not match';
    }
    
    if (!$terms) {
        $errors[] = 'You must agree to the terms and conditions';
    }
    
    // Check for existing users if no errors
    if (empty($errors)) {
        $stmt = $conn->prepare("SELECT UserID FROM user WHERE Username = ? OR Email = ?");
        $stmt->bind_param("ss", $username, $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->fetch_assoc()) {
            if ($stmt = $conn->prepare("SELECT UserID FROM user WHERE Username = ?")) {
                $stmt->bind_param("s", $username);
                $stmt->execute();
                $usernameExists = $stmt->get_result()->fetch_assoc();
            }
            
            if ($stmt = $conn->prepare("SELECT UserID FROM user WHERE Email = ?")) {
                $stmt->bind_param("s", $email);
                $stmt->execute();
                $emailExists = $stmt->get_result()->fetch_assoc();
            }
            
            if ($usernameExists) {
                $errors[] = 'Username already exists';
            }
            if ($emailExists) {
                $errors[] = 'Email address already exists';
            }
        } else {
            // Create the user account - FIXED: Removed FirstName and LastName from INSERT
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO user (Username, Email, PasswordHash, Role, AccountStatus) VALUES (?, ?, ?, 'End-User', 'Active')");
            $stmt->bind_param("sss", $username, $email, $hashedPassword);
            
            if ($stmt->execute()) {
                $success = true;
                $_SESSION['register_success'] = true;
            } else {
                $errors[] = 'Registration failed. Please try again.';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - TrafAnalyz</title>
    <link rel="stylesheet" href="styles.css">
    <style>

    </style>
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
            
            // Validate fields
            const firstname = document.getElementById('firstname').value.trim();
            const lastname = document.getElementById('lastname').value.trim();
            const username = document.getElementById('username').value.trim();
            const email = document.getElementById('email').value.trim();
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm-password').value;
            const terms = document.getElementById('terms').checked;
            
            if (!firstname) showError('firstname', 'First name is required');
            if (!lastname) showError('lastname', 'Last name is required');
            if (!username) showError('username', 'Username is required');
            else if (username.length < 3) showError('username', 'Username must be at least 3 characters');
            
            if (!email) showError('email', 'Email is required');
            else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) showError('email', 'Please enter a valid email');
            
            if (!password) showError('password', 'Password is required');
            else if (password.length < 8) showError('password', 'Password must be at least 8 characters');
            
            if (password !== confirmPassword) showError('confirm-password', 'Passwords do not match');
            
            if (!terms) showError('terms', 'You must agree to the terms');
            
            return isValid;
        }
        
        document.addEventListener('DOMContentLoaded', function() {
            // Process server errors
            <?php if (!empty($errors)): ?>
            var serverErrors = <?php echo json_encode($errors); ?>;
            
            serverErrors.forEach(function(error) {
                // Check for username errors
                if (error.indexOf("Username") !== -1) {
                    showError("username", error);
                } 
                // Check for email errors
                else if (error.indexOf("Email") !== -1) {
                    showError("email", error);
                } 
                // Add other field-specific error handling as needed
            });
            
            function showError(inputId, message) {
                const input = document.getElementById(inputId);
                if (input) {
                    const errorBubble = document.createElement('div');
                    errorBubble.className = 'error-bubble';
                    errorBubble.textContent = message;
                    input.classList.add('input-error');
                    input.parentElement.appendChild(errorBubble);
                }
            }
            <?php endif; ?>
            
            // Remove errors when typing
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
        
        <?php if (isset($_SESSION['register_success']) && $_SESSION['register_success']): ?>
        document.addEventListener('DOMContentLoaded', function() {
            const overlay = document.getElementById('overlay');
            const popup = document.getElementById('successPopup');
            
            overlay.classList.add('show');
            popup.classList.add('show');
            
            setTimeout(function() {
                window.location.href = 'login.php';
            }, 3000);
            
            <?php unset($_SESSION['register_success']); ?>
        });
        <?php endif; ?>
    </script>
</head>
<body style="display: flex; justify-content: center; align-items: center;">    
    <div class="auth-container">
        <div class="auth-form">
            <div class="logo">
                <img src="images/logo2.png" alt="TrafAnalyz Logo" class="logo-image">
            </div>

            <h1>Create an Account</h1>
            <p class="subtitle">Join now to analyse your web traffic data</p>

            <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" onsubmit="return validateForm()">
                <div class="form-row">
                    <div class="form-group">
                        <label for="firstname">First Name</label>
                        <input type="text" id="firstname" name="firstname" placeholder="Enter your first name" 
                               value="<?php echo htmlspecialchars($firstname ?? ''); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="lastname">Last Name</label>
                        <input type="text" id="lastname" name="lastname" placeholder="Enter your last name" 
                               value="<?php echo htmlspecialchars($lastname ?? ''); ?>" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" placeholder="Enter a username" 
                           value="<?php echo htmlspecialchars($username ?? ''); ?>" required>
                </div>

                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" placeholder="Enter your email address" 
                           value="<?php echo htmlspecialchars($email ?? ''); ?>" required>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="password-field">
                        <input type="password" id="password" name="password" placeholder="Create a password" required>
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
                        I agree to the <a href="terms.php" class="terms-link" target="_blank">Terms & Conditions</a> and <a href="privacy.php" class="privacy-link" target="_blank">Privacy Policy</a>
                    </label>
                </div>

                <button type="submit" class="auth-btn">Create Account</button>
            </form>

            <div class="sign-in">
                Already have an account? <a href="login.php">Sign In</a>
            </div>
            <div class="sign-in" style="margin-top: 10px;">
                <a href="index.php">Return to Home Page</a>
            </div>
        </div>
        <div class="auth-image"></div>
    </div>

    <div class="overlay" id="overlay"></div>
    <div class="success-popup" id="successPopup">
        <img src="images/success.png" alt="Success">
        <h2>Registration Successful!<br>Redirecting to login page...</h2>
    </div>
</body>
</html>
