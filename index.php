<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transportation Management System</title>
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

    .container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 20px;
    }

    .auth-container {
        display: flex;
        min-height: 100vh;
        align-items: center;
    }

    .left-panel {
        flex: 1;
        background: linear-gradient(135deg, #1a56db 0%, #2a6bc1 100%);
        color: white;
        padding: 40px;
        display: flex;
        flex-direction: column;
        justify-content: center;
        min-height: 100vh;
        display: none;
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

    .logo {
        text-align: center;
        margin-bottom: 30px;
    }

    .logo h1 {
        font-size: 24px;
        color: #1a56db;
        margin-top: 10px;
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
    input[type="password"],
    select {
        width: 100%;
        padding: 12px 15px;
        border: 1px solid #d1d5db;
        border-radius: 6px;
        font-size: 16px;
        transition: border-color 0.3s;
    }

    input[type="text"]:focus,
    input[type="email"]:focus,
    input[type="password"]:focus,
    select:focus {
        border-color: #1a56db;
        outline: none;
    }

    .error-message {
        color: #ef4444;
        font-size: 14px;
        margin-top: 5px;
        display: none;
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

    .btn:hover {
        background-color: #1e40af;
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

    .toggle-forms {
        display: flex;
        justify-content: center;
        margin-top: 30px;
    }

    .toggle-btn {
        background: none;
        border: none;
        color: #1a56db;
        cursor: pointer;
        font-weight: 500;
        text-decoration: underline;
    }

    @media (min-width: 992px) {
        .left-panel {
            display: flex;
        }
    }
    </style>
</head>

<body>
    <div class="container">


        <div class="auth-container" id="login-container">
            <div class="left-panel">
                <div class="left-content">
                    <h1>Transportation Management System</h1>
                    <p>“Efficiency in transportation is not just about speed, but about smart management.”</p>
                    <div class="features">
                        <p>✓ User Management</p>
                        <p>✓ Vehicle Management</p>
                        <p>✓ Booking and Ticketing</p>
                        <p>✓ Fare Management</p>
                    </div>
                </div>
            </div>
            <div class="right-panel">
                <div class="auth-form">
                    <h2>Welcome Back</h2>
                    <p class="subtitle">Log in to access transportation management system</p>
                    <!-- LOGIN FORM -->
                    <form id="login-form" method="POST" action="server.php">
                        <div class="form-group">
                            <label for="login-email">Email Address</label>
                            <input type="email" id="login-email" name="email" placeholder="Enter your email" required>
                        </div>

                        <div class="form-group">
                            <label for="login-password">Password</label>
                            <input type="password" id="login-password" name="password" placeholder="Enter your password"
                                required>
                        </div>

                        <div class="form-group">
                            <button type="submit" class="btn" name="login">Log In</button>
                        </div>
                    </form>

                    <div class="toggle-forms">
                        <p>Don't have an account? <button class="toggle-btn" onclick="showSignup()">Sign Up</button></p>
                    </div>
                </div>
            </div>
        </div>


        <div class="auth-container" id="signup-container" style="display: none;">
            <div class="left-panel">
                <div class="left-content">
                    <h1>Transportation Management System</h1>
                    <p>“From booking to billing—simplify your transport operations.”</p>
                    <div class="features">
                        <p>✓ Booking and Ticketing</p>
                        <p>✓ Fare Details</p>
                        <p>✓ Cancel Booking</p>
                    </div>
                </div>
            </div>
            <div class="right-panel">
                <div class="auth-form">
                    <h2>Create Account</h2>
                    <p class="subtitle">Set up your transportation management account</p>
                    <!-- SIGNUP FORM -->
                    <form id="signup-form" method="POST" action="server.php">
                        <div class="form-group">
                            <label for="full-name">Full Name</label>
                            <input type="text" id="full-name" name="name" placeholder="Enter your full name" required>
                        </div>

                        <div class="form-group">
                            <label for="signup-email">Email Address</label>
                            <input type="email" id="signup-email" name="email" placeholder="Enter your email" required>
                        </div>

                        <div class="form-group">
                            <label for="signup-password">Password</label>
                            <input type="password" id="signup-password" name="password" placeholder="Create a password"
                                required>
                        </div>

                        <div class="form-group">
                            <button type="submit" class="btn" name="signup">Create Account</button>
                        </div>
                    </form>

                    <div class="toggle-forms">
                        <p>Already have an account? <button class="toggle-btn" onclick="showLogin()">Sign In</button>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
    function showSignup() {
        document.getElementById('login-container').style.display = 'none';
        document.getElementById('signup-container').style.display = 'flex';
    }

    function showLogin() {
        document.getElementById('signup-container').style.display = 'none';
        document.getElementById('login-container').style.display = 'flex';
    }
    </script>
</body>

</html>