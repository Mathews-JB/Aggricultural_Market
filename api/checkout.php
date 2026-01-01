<?php
include '../config.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'buyer' || empty($_SESSION['cart'])) {
    header("Location: ../cart.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$cart = $_SESSION['cart'];

try {
    $pdo->beginTransaction();

    foreach ($cart as $product_id => $qty) {
        // Get product price
        $stmt = $pdo->prepare("SELECT price, quantity FROM products WHERE id = ?");
        $stmt->execute([$product_id]);
        $product = $stmt->fetch();

        if (!$product || $product['quantity'] < $qty) {
            throw new Exception("Product ID $product_id is unavailable or out of stock.");
        }

        $total_amount = $product['price'] * $qty;
        $discount = 0;

        // Apply 10% discount for VVIP members
        if (isset($_SESSION['membership_type']) && $_SESSION['membership_type'] == 'vvip') {
            $discount = $total_amount * 0.10;
            $total_amount -= $discount;
        }

        // Create individual order for each farmer (simplification)
        $stmt = $pdo->prepare("INSERT INTO orders (buyer_id, total_amount, discount_amount, status) VALUES (?, ?, ?, 'pending')");
        $stmt->execute([$user_id, $total_amount, $discount]);
        $order_id = $pdo->lastInsertId();

        $stmt = $pdo->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
        $stmt->execute([$order_id, $product_id, $qty, $product['price']]);

        // Update stock
        $stmt = $pdo->prepare("UPDATE products SET quantity = quantity - ? WHERE id = ?");
        $stmt->execute([$qty, $product_id]);
    }

    $pdo->commit();
    $_SESSION['cart'] = []; // Clear cart
    header("Location: ../orders.php?success=checkout_complete");

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    die("Error during checkout: " . $e->getMessage());
}
?>
