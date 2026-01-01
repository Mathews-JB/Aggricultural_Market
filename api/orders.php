<?php
include '../config.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    die("Unauthorized access.");
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

if ($action == 'place_order' && $role == 'buyer') {
    $product_id = $_POST['product_id'];
    $quantity = $_POST['quantity'];
    
    try {
        $pdo->beginTransaction();

        // Get product details
        $stmt = $pdo->prepare("SELECT price, quantity FROM products WHERE id = ?");
        $stmt->execute([$product_id]);
        $product = $stmt->fetch();

        if (!$product || $product['quantity'] < $quantity) {
            die("Product unavailable or insufficient stock.");
        }

        $total_amount = $product['price'] * $quantity;
        $discount = 0;

        if (isset($_SESSION['membership_type']) && $_SESSION['membership_type'] == 'vvip') {
            $discount = $total_amount * 0.10;
            $total_amount -= $discount;
        }

        // Create order
        $stmt = $pdo->prepare("INSERT INTO orders (buyer_id, total_amount, discount_amount, status) VALUES (?, ?, ?, 'pending')");
        $stmt->execute([$user_id, $total_amount, $discount]);
        $order_id = $pdo->lastInsertId();

        // Create order item
        $stmt = $pdo->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
        $stmt->execute([$order_id, $product_id, $quantity, $product['price']]);

        // Update product quantity
        $stmt = $pdo->prepare("UPDATE products SET quantity = quantity - ? WHERE id = ?");
        $stmt->execute([$quantity, $product_id]);

        // Get farmer ID for notification
        $stmt = $pdo->prepare("SELECT farmer_id, name FROM products WHERE id = ?");
        $stmt->execute([$product_id]);
        $prod_data = $stmt->fetch();
        $farmer_id = $prod_data['farmer_id'];
        $prod_name = $prod_data['name'];

        // Create notification for farmer
        $notif_stmt = $pdo->prepare("INSERT INTO notifications (user_id, title, message, type) VALUES (?, ?, ?, 'order')");
        $notif_message = $_SESSION['full_name'] . " placed an order for " . $quantity . " " . $prod_name;
        $notif_stmt->execute([$farmer_id, "New Order Received", $notif_message]);

        $pdo->commit();
        header("Location: ../orders.php?success=ordered");
    } catch (Exception $e) {
        $pdo->rollBack();
        die("Error: " . $e->getMessage());
    }

} elseif ($action == 'update_status' && $role == 'farmer') {
    $order_id = $_GET['order_id'];
    $status = $_GET['status'];

    try {
        $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
        $stmt->execute([$status, $order_id]);

        // Get buyer ID for notification
        $stmt = $pdo->prepare("SELECT buyer_id FROM orders WHERE id = ?");
        $stmt->execute([$order_id]);
        $buyer_id = $stmt->fetchColumn();

        // Create notification for buyer
        $notif_stmt = $pdo->prepare("INSERT INTO notifications (user_id, title, message, type) VALUES (?, ?, ?, 'order')");
        $notif_message = "Your order #" . $order_id . " has been " . $status;
        $notif_stmt->execute([$buyer_id, "Order Status Updated", $notif_message]);

        header("Location: ../orders.php?success=updated");
    } catch (PDOException $e) {
        die("Error: " . $e->getMessage());
    }
} else {
    header("Location: ../orders.php");
}
?>
