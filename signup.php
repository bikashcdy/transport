<form method="POST" action="server.php">
    <input type="text" name="name" placeholder="Enter your name" required>

    <input type="email" name="email" placeholder="Enter your email" required>

    <input type="text" name="user_type" placeholder="Enter user type" required>

    <input type="password" name="password" placeholder="Enter your password" required
           pattern=".{8,}"
           title="Password must be at least 8 characters long">

    <button type="submit" name="signup">Sign Up</button>
</form>
