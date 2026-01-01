<?php
include '../config.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    if ($action == 'register') {
        $full_name = filter_var($_POST['full_name'], FILTER_SANITIZE_STRING);
        $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
        $phone_number = filter_var($_POST['phone_number'], FILTER_SANITIZE_STRING);
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $role = $_POST['role'];

        try {
            // Check if email already exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->rowCount() > 0) {
                die("Email already registered. <a href='../register.php'>Go back</a>");
            }

            $stmt = $pdo->prepare("INSERT INTO users (full_name, email, phone_number, password, role) VALUES (?, ?, ?, ?, ?)");
            if ($stmt->execute([$full_name, $email, $phone_number, $password, $role])) {
                header("Location: ../login.php?success=registered");
            }
        } catch (PDOException $e) {
            die("Error: " . $e->getMessage());
        }

    } elseif ($action == 'login') {
        $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
        $password = $_POST['password'];

        try {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                if ($user['status'] == 'suspended') {
                    die("Your account is suspended. Please contact support.");
                }

                $_SESSION['user_id'] = $user['id'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['profile_image'] = $user['profile_image'];
                $_SESSION['membership_type'] = $user['membership_type'] ?? 'basic';

                header("Location: ../dashboard.php");
            } else {
                header("Location: ../login.php?error=invalid_credentials");
            }
        } catch (PDOException $e) {
            die("Error: " . $e->getMessage());
        }
    }
} else {
    header("Location: ../login.php");
}
?>
