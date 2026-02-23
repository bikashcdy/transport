<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - Vehicle Booking System</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background-color: #f5f7f9;
            color: #333;
            line-height: 1.6;
        }

        .auth-container {
            display: flex;
            min-height: 100vh;
            align-items: center;
            justify-content: center;
        }

        .right-panel {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 40px;
        }

        .auth-form {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 450px;
        }

        h2 {
            text-align: center;
            margin-bottom: 10px;
            color: #1f2937;
        }

        .subtitle {
            text-align: center;
            color: #6b7280;
            margin-bottom: 30px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #374151;
        }

        input[type="text"],
        input[type="email"],
        input[type="password"] {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 16px;
            transition: border-color 0.3s;
        }

        input[type="text"]:focus,
        input[type="email"]:focus,
        input[type="password"]:focus {
            border-color: #1a56db;
            outline: none;
        }

        input.invalid {
            border-color: #ef4444;
        }

        input.valid {
            border-color: #10b981;
        }

        .btn {
            width: 100%;
            padding: 12px;
            background-color: #1a56db;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .btn:hover:not(:disabled) {
            background-color: #1e40af;
        }

        .btn:disabled {
            background-color: #9ca3af;
            cursor: not-allowed;
            opacity: 0.6;
        }

        .form-footer {
            text-align: center;
            margin-top: 20px;
            color: #6b7280;
        }

        .form-footer a {
            color: #1a56db;
            text-decoration: none;
            font-weight: 500;
        }

        .form-footer a:hover {
            text-decoration: underline;
        }

        .password-requirements {
            margin-top: 10px;
            padding: 12px;
            background-color: #f9fafb;
            border-radius: 6px;
            border: 1px solid #e5e7eb;
            display: none;
        }

        .password-requirements.show {
            display: block;
        }

        .password-requirements h4 {
            font-size: 13px;
            margin-bottom: 8px;
            color: #374151;
            font-weight: 600;
        }

        .requirement {
            display: flex;
            align-items: center;
            font-size: 13px;
            margin: 6px 0;
            color: #6b7280;
        }

        .requirement::before {
            content: "✗";
            margin-right: 8px;
            color: #ef4444;
            font-weight: bold;
            font-size: 14px;
        }

        .requirement.valid {
            color: #059669;
        }

        .requirement.valid::before {
            content: "✓";
            color: #059669;
        }

        .message {
            padding: 15px 20px;
            margin: 20px auto;
            border-radius: 8px;
            max-width: 500px;
            text-align: center;
            font-weight: 500;
            animation: slideDown 0.4s ease-out;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .message.success {
            background-color: #d1fae5;
            color: #065f46;
            border: 2px solid #10b981;
        }

        .message.error {
            background-color: #fee2e2;
            color: #991b1b;
            border: 2px solid #ef4444;
        }
    </style>
</head>

<body>

    <?php
    if (isset($_SESSION['error'])) {
        echo '<div class="message error">' . htmlspecialchars($_SESSION['error']) . '</div>';
        unset($_SESSION['error']);
    }

    if (isset($_SESSION['message'])) {
        echo '<div class="message success">' . htmlspecialchars($_SESSION['message']) . '</div>';
        unset($_SESSION['message']);
    }
    ?>

    <div class="auth-container">
        <div class="right-panel">
            <div class="auth-form">
                <h2>Create Account</h2>
                <p class="subtitle">Set up your Vehicle Booking account</p>

                <form id="signup-form" method="POST" action="server.php" onsubmit="return validateSignupForm()">
                    <div class="form-group">
                        <label for="full-name">Full Name</label>
                        <input type="text" id="full-name" name="name" placeholder="Enter your full name">
                    </div>

                    <div class="form-group">
                        <label for="signup-email">Email Address</label>
                        <input type="text" id="signup-email" name="email" placeholder="Enter your email">
                    </div>

                    <div class="form-group">
                        <label for="signup-password">Password</label>
                        <input type="password" id="signup-password" name="password" placeholder="Create a password">

                        <div class="password-requirements" id="password-requirements">
                            <h4>Password must contain:</h4>
                            <div class="requirement" id="req-length">At least 8 characters</div>
                            <div class="requirement" id="req-uppercase">One uppercase letter (A-Z)</div>
                            <div class="requirement" id="req-lowercase">One lowercase letter (a-z)</div>
                            <div class="requirement" id="req-number">One number (0-9)</div>
                            <div class="requirement" id="req-special">One special character (!@#$%^&*)</div>
                        </div>
                    </div>

                    <div class="form-group">
                        <button type="submit" class="btn" name="signup" id="signup-btn">Create Account</button>
                    </div>
                </form>

                <div class="form-footer">
                    <p>Already have an account? <a href="Sign_in.php">Sign In</a></p>
                </div>
            </div>
        </div>
    </div>

    <script>
        // ===== Password Validation =====
        const passwordInput = document.getElementById('signup-password');
        const requirementsBox = document.getElementById('password-requirements');
        const signupBtn = document.getElementById('signup-btn');

        const requirements = {
            length: document.getElementById('req-length'),
            uppercase: document.getElementById('req-uppercase'),
            lowercase: document.getElementById('req-lowercase'),
            number: document.getElementById('req-number'),
            special: document.getElementById('req-special')
        };

        let checks = {
            length: false,
            uppercase: false,
            lowercase: false,
            number: false,
            special: false
        };

        passwordInput.addEventListener('focus', function () {
            requirementsBox.classList.add('show');
        });

        passwordInput.addEventListener('input', function () {
            const password = this.value;

            checks = {
                length: password.length >= 8,
                uppercase: /[A-Z]/.test(password),
                lowercase: /[a-z]/.test(password),
                number: /[0-9]/.test(password),
                special: /[!@#$%^&*(),.?":{}|<>]/.test(password)
            };

            for (let key in checks) {
                if (checks[key]) {
                    requirements[key].classList.add('valid');
                } else {
                    requirements[key].classList.remove('valid');
                }
            }

            const allValid = Object.values(checks).every(check => check === true);
            if (password.length > 0) {
                if (allValid) {
                    passwordInput.classList.remove('invalid');
                    passwordInput.classList.add('valid');
                } else {
                    passwordInput.classList.remove('valid');
                    passwordInput.classList.add('invalid');
                }
            } else {
                passwordInput.classList.remove('valid', 'invalid');
            }

            signupBtn.disabled = !allValid;
        });

        // ===== Email Validation =====
        const fullNameInput = document.getElementById('full-name');
        const emailInput = document.getElementById('signup-email');

        function validateEmail(email) {
            const strongRegex = /^[A-Za-z0-9._%+-]+@[A-Za-z.-]+\.[A-Za-z]{2,}$/;
            const domainPart = email.split('@')[1] || "";
            const domainName = domainPart.split('.')[0] || "";
            if (!strongRegex.test(email)) return false;
            if (!/[A-Za-z]/.test(domainName)) return false;
            return true;
        }

        function validateSignupForm() {
            const fullName = fullNameInput.value.trim();
            const password = passwordInput.value;

            // Full Name Validation
            const nameRegex = /^[A-Za-z\s]+$/;
            if (!nameRegex.test(fullName)) {
                alert('Full name must contain only letters and spaces.');
                fullNameInput.focus();
                return false;
            }

            const words = fullName.split(' ').filter(word => word.length > 0);
            if (words.length < 2) {
                alert('Please enter your full name (at least 2 words).');
                fullNameInput.focus();
                return false;
            }

            for (let i = 0; i < words.length; i++) {
                if (words[i].length < 4) {
                    alert('Each part of your full name must be at least 4 letters.');
                    fullNameInput.focus();
                    return false;
                }
            }

            // Email Validation
            const email = emailInput.value.trim();
            if (!validateEmail(email)) {
                alert('Invalid Email Format! Please enter a valid email like example@gmail.com');
                emailInput.focus();
                return false;
            }

            // Password Validation
            if (!checks.length) { alert('Password must be at least 8 characters long.'); passwordInput.focus(); return false; }
            if (!checks.uppercase) { alert('Password must contain at least one uppercase letter.'); passwordInput.focus(); return false; }
            if (!checks.lowercase) { alert('Password must contain at least one lowercase letter.'); passwordInput.focus(); return false; }
            if (!checks.number) { alert('Password must contain at least one number.'); passwordInput.focus(); return false; }
            if (!checks.special) { alert('Password must contain at least one special character.'); passwordInput.focus(); return false; }

            return true;
        }

        // Auto hide messages
        const messages = document.querySelectorAll('.message');
        messages.forEach(function (msg) {
            setTimeout(function () {
                msg.style.transition = 'opacity 0.3s, transform 0.3s';
                msg.style.opacity = '0';
                msg.style.transform = 'translateY(-10px)';
                setTimeout(function () { msg.style.display = 'none'; }, 300);
            }, 6000);
        });
    </script>

</body>

</html>