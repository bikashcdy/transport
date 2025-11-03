<?php
$accountCreated = isset($_GET['success']) && $_GET['success'] == 1;
?>

<form method="POST" action="server.php">
  <input type="email" name="email" placeholder="Enter your email" required>

  <input type="password" name="password" placeholder="Enter your password" required
         pattern="^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&]).{8,}$"
         title="Password must be at least 8 characters, include uppercase, lowercase, number, and special character">

  <button type="submit" name="login">Log In</button>
</form>

<?php
if ($accountCreated) {
    echo '<p style="color: green;">Account created successfully! You can now log in.</p>';
}
?>
