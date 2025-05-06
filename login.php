<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TrafAnalyz Login</title>
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
            background-color: #007bff;
            border-radius: 4px;
            margin-right: 10px;
        }

        .logo-text {
            color: #333;
            font-weight: 600;
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
            border-color: #007bff;
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
            accent-color: #007bff;
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
            background-color: #007bff;
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
            background-color: #0056b3;
        }

        .sign-up {
            text-align: center;
            color: #666;
        }

        .sign-up a {
            color: #007bff;
            text-decoration: none;
            font-weight: 600;
        }

        .sign-up a:hover {
            text-decoration: underline;
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
                <div class="logo-text">TrafAnalyz</div>
            </div>

            <h1>Hey!<br>Welcome Back</h1>
            <p class="welcome-text">Welcome back to TrafAnalyz the Complementary Web Analytics Dashboard</p>

            <form>
                <div class="form-group">
                    <input type="text" placeholder="Username" required>
                </div>
                <div class="form-group">
                    <input type="password" id="passwordInput" placeholder="Password" required>
                    <span class="password-toggle" onclick="togglePassword()">👁️</span>
                </div>

                <div class="remember-forgot">
                    <label class="remember">
                        <input type="checkbox">
                        Remember me
                    </label>
                </div>

                <button type="submit" class="sign-in-btn">Sign In</button>
            </form>

            <div class="sign-up">
                Don't have an account? <a href="#">Sign Up</a>
            </div>
        </div>
        <div class="login-image"></div>
    </div>

    <script>
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
    </script>
</body>
</html>