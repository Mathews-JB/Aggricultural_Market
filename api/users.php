<?php
include '../config.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    die("Unauthorized access.");
}

$user_id = $_SESSION['user_id'];
$action = $_POST['action'] ?? '';

if ($action == 'update_info') {
    $full_name = filter_var($_POST['full_name'], FILTER_SANITIZE_STRING);
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);

    try {
        $stmt = $pdo->prepare("UPDATE users SET full_name = ?, email = ? WHERE id = ?");
        if ($stmt->execute([$full_name, $email, $user_id])) {
            $_SESSION['full_name'] = $full_name;
            $_SESSION['email'] = $email;
            header("Location: ../profile.php?success=1");
        }
    } catch (PDOException $e) {
        die("Error: " . $e->getMessage());
    }

} elseif ($action == 'update_image') {
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0) {
        $target_dir = "../assets/img/";
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        $extension = pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION);
        $image_name = "profile_" . $user_id . "_" . time() . "." . $extension;
        $target_file = $target_dir . $image_name;

        if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $target_file)) {
            // Delete old image if not default
            $old_image = $_SESSION['profile_image'];
            if ($old_image != 'default_profile.png' && file_exists($target_dir . $old_image)) {
                unlink($target_dir . $old_image);
            }

            try {
                $stmt = $pdo->prepare("UPDATE users SET profile_image = ? WHERE id = ?");
                $stmt->execute([$image_name, $user_id]);
                $_SESSION['profile_image'] = $image_name;
                header("Location: ../profile.php?success=1");
            } catch (PDOException $e) {
                die("Error updating database: " . $e->getMessage());
            }
        } else {
            die("Error uploading image.");
        }
    }
} elseif ($action == 'update_public_key') {
    $public_key = $_POST['public_key'];
    try {
        $stmt = $pdo->prepare("UPDATE users SET public_key = ? WHERE id = ?");
        $stmt->execute([$public_key, $user_id]);
        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        echo json_encode(['error' => $e->getMessage()]);
    }
    exit();
} elseif ($action == 'update_password') {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if ($new_password !== $confirm_password) {
        die("Passwords do not match. <a href='../settings.php'>Go back</a>");
    }

    try {
        $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();

        if ($user && password_verify($current_password, $user['password'])) {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->execute([$hashed_password, $user_id]);
            header("Location: ../settings.php?success=1");
        } else {
            die("Incorrect current password. <a href='../settings.php'>Go back</a>");
        }
    } catch (PDOException $e) {
        die("Error: " . $e->getMessage());
    }
} else {
    header("Location: ../profile.php");
}
?>
