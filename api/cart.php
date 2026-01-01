<?php
include '../config.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'buyer') {
    die("Unauthorized access.");
}

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

if ($action == 'add') {
    $product_id = $_GET['product_id'];
    if (!isset($_SESSION['cart'][$product_id])) {
        $_SESSION['cart'][$product_id] = 1;
    } else {
        $_SESSION['cart'][$product_id]++;
    }
    $redirect = $_GET['redirect'] ?? 'cart';
    if ($redirect == 'back' && isset($_SERVER['HTTP_REFERER'])) {
        header("Location: " . $_SERVER['HTTP_REFERER'] . (strpos($_SERVER['HTTP_REFERER'], '?') !== false ? '&' : '?') . "success=cart_added");
    } else {
        header("Location: ../cart.php");
    }

} elseif ($action == 'remove') {
    $product_id = $_GET['product_id'];
    unset($_SESSION['cart'][$product_id]);
    header("Location: ../cart.php");

} elseif ($action == 'update') {
    $product_id = $_POST['product_id'];
    $quantity = $_POST['quantity'];
    if ($quantity <= 0) {
        unset($_SESSION['cart'][$product_id]);
    } else {
        $_SESSION['cart'][$product_id] = $quantity;
    }
    header("Location: ../cart.php");
}
?>
