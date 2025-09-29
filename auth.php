<?php
session_start();
?>

<?php if (isset($_SESSION['message'])): ?>
<p style="color:green; text-align:center;"><?= $_SESSION['message']; ?></p>
<?php unset($_SESSION['message']); ?>
<?php endif; ?>

<?php if (isset($_SESSION['error'])): ?>
<p style="color:red; text-align:center;"><?= $_SESSION['error']; ?></p>
<?php unset($_SESSION['error']); ?>
<?php endif; ?>

<a href="index.php">Go to Login / Signup</a>