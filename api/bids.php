<?php
include '../config.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'buyer') {
    die(json_encode(['status' => 'error', 'message' => 'Unauthorized access.']));
}

$user_id = $_SESSION['user_id'];
$action = $_POST['action'] ?? '';

if ($action == 'place_bid') {
    $product_id = $_POST['product_id'];
    $amount = $_POST['amount'];

    try {
        // Fetch current product details
        $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
        $stmt->execute([$product_id]);
        $product = $stmt->fetch();

        if (!$product) {
            die(json_encode(['status' => 'error', 'message' => 'Product not found.']));
        }

        if (!$product['is_auction']) {
            die(json_encode(['status' => 'error', 'message' => 'This product is not up for auction.']));
        }

        if (strtotime($product['auction_end']) < time()) {
            die(json_encode(['status' => 'error', 'message' => 'Auction has ended.']));
        }

        $min_bid = $product['current_bid'] > 0 ? $product['current_bid'] + 1 : $product['price'] + 1;

        if ($amount < $min_bid) {
            die(json_encode(['status' => 'error', 'message' => 'Bid must be greater than current bid.']));
        }

        // Get previous high bidder for outbid notification
        $prev_bidder_id = null;
        $prev_bid_stmt = $pdo->prepare("SELECT buyer_id FROM bids WHERE product_id = ? AND buyer_id != ? ORDER BY amount DESC LIMIT 1");
        $prev_bid_stmt->execute([$product_id, $user_id]);
        $prev_bidder_id = $prev_bid_stmt->fetchColumn();

        // Insert new bid
        $stmt = $pdo->prepare("INSERT INTO bids (product_id, buyer_id, amount) VALUES (?, ?, ?)");
        $stmt->execute([$product_id, $user_id, $amount]);

        // Update current bid in products table
        $stmt = $pdo->prepare("UPDATE products SET current_bid = ? WHERE id = ?");
        $stmt->execute([$amount, $product_id]);

        // 1. Notify Farmer
        $notif_stmt = $pdo->prepare("INSERT INTO notifications (user_id, title, message, type) VALUES (?, ?, ?, 'bid')");
        $farmer_msg = $_SESSION['full_name'] . " placed a new bid of K" . $amount . " on " . $product['name'];
        $notif_stmt->execute([$product['farmer_id'], "New Bid on Your Product", $farmer_msg]);

        // 2. Notify Previous Bidder (Outbid)
        if ($prev_bidder_id) {
            $outbid_msg = "You've been outbid on " . $product['name'] . ". New bid is K" . $amount;
            $notif_stmt->execute([$prev_bidder_id, "Highly Important: You've been Outbid!", $outbid_msg]);
        }

        echo json_encode(['status' => 'success', 'message' => 'Bid placed successfully!']);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
}
?>
