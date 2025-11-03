<?php
session_start();
include "db.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    if (isset($_POST['signup'])) {
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        $user_type = 'user';
        $password = $_POST['password'];

        if (strlen($password) < 8) {
            $_SESSION['error'] = "Password must be at least 8 characters long.";
            header("Location: index.php");
            exit();
        } elseif (!preg_match("/[A-Z]/", $password)) {
            $_SESSION['error'] = "Password must contain at least one uppercase letter.";
            header("Location: index.php");
            exit();
        } elseif (!preg_match("/[a-z]/", $password)) {
            $_SESSION['error'] = "Password must contain at least one lowercase letter.";
            header("Location: index.php");
            exit();
        } elseif (!preg_match("/[0-9]/", $password)) {
            $_SESSION['error'] = "Password must contain at least one number.";
            header("Location: index.php");
            exit();
        } elseif (!preg_match("/[!@#$%^&*(),.?\":{}|<>]/", $password)) {
            $_SESSION['error'] = "Password must contain at least one special character.";
            header("Location: index.php");
            exit();
        }

      
        $check_email = "SELECT * FROM users WHERE email=?";
        $check_stmt = $conn->prepare($check_email);
        $check_stmt->bind_param("s", $email);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();

        if ($check_result->num_rows > 0) {
            $_SESSION['error'] = "❌ Email already exists.";
            header("Location: index.php");
            exit();
        }

        // -------- If password is valid and email is unique --------
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $sql = "INSERT INTO users (name, email, user_type, password) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssss", $name, $email, $user_type, $hashed_password);

        if ($stmt->execute()) {
            $_SESSION['message'] = "✅ Account created successfully! Please log in.";
            header("Location: index.php");
            exit();
        } else {
            $_SESSION['error'] = "❌ Database error. Please try again.";
            header("Location: index.php");
            exit();
        }
    }

    if (isset($_POST['login'])) {
        $email = trim($_POST['email']);
        $password = $_POST['password'];

        $sql = "SELECT * FROM users WHERE email=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 1) {
            $row = $result->fetch_assoc();
            if (password_verify($password, $row['password'])) {
                $_SESSION['user_id'] = $row['id'];
                $_SESSION['user_name'] = $row['name'];
                $_SESSION['user_type'] = $row['user_type'];

                if ($_SESSION['user_type'] === 'admin') {
                    header("Location: admin/admin_dashboard.php");
                } else {
                    header("Location: user/user_dashboard.php");
                }
                exit();
            } else {
                $_SESSION['error'] = "❌ Invalid password!";
                header("Location: index.php");
                exit();
            }
        } else {
            $_SESSION['error'] = "❌ No account found with that email.";
            header("Location: index.php");
            exit();
        }
    }
}

// If someone accesses this file directly
header("Location: index.php");
exit();
?>