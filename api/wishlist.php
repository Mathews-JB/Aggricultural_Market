<?php
include '../config.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'buyer') {
    die("Unauthorized access.");
}

$user_id = $_SESSION['user_id'];
$action = $_GET['action'] ?? $_POST['action'] ?? '';

if ($action == 'add') {
    $product_id = $_GET['product_id'];
    try {
        $stmt = $pdo->prepare("INSERT IGNORE INTO wishlist (buyer_id, product_id) VALUES (?, ?)");
        $stmt->execute([$user_id, $product_id]);
        
        if (isset($_SERVER['HTTP_REFERER'])) {
             header("Location: " . $_SERVER['HTTP_REFERER'] . (strpos($_SERVER['HTTP_REFERER'], '?') !== false ? '&' : '?') . "success=wishlist_added");
        } else {
             header("Location: ../wishlist.php?success=wishlist_added");
        }
    } catch (PDOException $e) {
        die("Error: " . $e->getMessage());
    }
} elseif ($action == 'remove') {
    $product_id = $_GET['product_id'];
    try {
        $stmt = $pdo->prepare("DELETE FROM wishlist WHERE buyer_id = ? AND product_id = ?");
        $stmt->execute([$user_id, $product_id]);
        header("Location: ../wishlist.php?success=removed");
    } catch (PDOException $e) {
        die("Error: " . $e->getMessage());
    }
} else {
    header("Location: ../wishlist.php");
}
?>
