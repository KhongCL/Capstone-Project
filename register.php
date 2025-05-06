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
            background-image: url('registerbg.jpg');
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
            <p class="subtitle">Join now to streamline your experience from day one.</p>

            <form>
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" placeholder="Enter your username" required>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="firstname">First Name</label>
                        <input type="text" id="firstname" placeholder="First name" required>
                    </div>
                    <div class="form-group">
                        <label for="lastname">Last Name</label>
                        <input type="text" id="lastname" placeholder="Last name" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" placeholder="Enter your email" required>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="password-field">
                        <input type="password" id="password" placeholder="Create a password" required>
                        <button type="button" class="toggle-password">👁️</button>
                    </div>
                </div>

                <div class="form-group">
                    <label for="confirm-password">Confirm Password</label>
                    <div class="password-field">
                        <input type="password" id="confirm-password" placeholder="Confirm your password" required>
                        <button type="button" class="toggle-password">👁️</button>
                    </div>
                </div>

                <button type="submit" class="register-btn">Register</button>
            </form>

            <div class="sign-in">
                Already Have An Account? <a href="#">Sign In</a>
            </div>

            <div class="footer">
                <div>Copyright © 2025 TrafAnalyz Enterprises SDN BHD.</div>
                <div><a href="#">Privacy Policy</a></div>
            </div>
        </div>
        <div class="register-image"></div>
    </div>

    <script>
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
    </script>
</body>
</html>