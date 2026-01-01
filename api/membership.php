<?php
include '../config.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    die("Unauthorized access.");
}

$user_id = $_SESSION['user_id'];

try {
    $stmt = $pdo->prepare("UPDATE users SET membership_type = 'vvip' WHERE id = ?");
    if ($stmt->execute([$user_id])) {
        $_SESSION['membership_type'] = 'vvip'; // Update session
        header("Location: ../dashboard.php?success=membership_upgraded");
    } else {
        die("Error upgrading membership.");
    }
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>
