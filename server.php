<?php
session_start();
include "db.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['signup'])) {

        $name = $_POST['name'];
        $email = $_POST['email'];
        $user_type = 'user';
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

        $sql = "INSERT INTO users (name, email, user_type, password) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssss", $name, $email, $user_type, $password);

        if ($stmt->execute()) {
            $_SESSION['message'] = "Account created successfully. Please log in!";
            header("Location: index.php");
        } else {
            $_SESSION['error'] = "Error: Email already exists.";
            header("Location: auth.php");
        }
        exit();
    }

    if (isset($_POST['login'])) {
        $email = $_POST['email'];
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
            } else {
                $_SESSION['error'] = "Invalid password!";
                header("Location: index.php");
            }
        } else {
            $_SESSION['error'] = "No account found with that email.";
            header("Location: auth.php");
        }
        exit();
    }
}
?>