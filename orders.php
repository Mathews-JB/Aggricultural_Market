<?php
include 'includes/header.inc.php';
include 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$role = $_SESSION['role'];
$user_id = $_SESSION['user_id'];

// Fetch orders based on role
if ($role == 'buyer') {
    $stmt = $pdo->prepare("SELECT o.*, oi.quantity, oi.price, p.name as product_name, p.image 
                           FROM orders o 
                           JOIN order_items oi ON o.id = oi.order_id 
                           JOIN products p ON oi.product_id = p.id 
                           WHERE o.buyer_id = ? ORDER BY o.created_at DESC");
    $stmt->execute([$user_id]);
} else {
    $stmt = $pdo->prepare("SELECT o.*, oi.quantity, oi.price, p.name as product_name, p.image, u.full_name as buyer_name 
                           FROM orders o 
                           JOIN order_items oi ON o.id = oi.order_id 
                           JOIN products p ON oi.product_id = p.id 
                           JOIN users u ON o.buyer_id = u.id
                           WHERE p.farmer_id = ? ORDER BY o.created_at DESC");
    $stmt->execute([$user_id]);
}
$orders = $stmt->fetchAll();
?>

<div class="d-flex">
    <?php include 'includes/sidebar.php'; ?>

    <div class="main-content w-100">
        <?php include 'includes/topbar.php'; ?>

        <div class="container-fluid p-0 animate-fade">
            <h3 class="fw-bold mb-4">Order History</h3>

            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show rounded-4 mb-4" role="alert">
                    Order status updated!
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="stats-card">
                <div class="table-responsive">
                    <table class="table table-dark table-hover mb-0" style="background: transparent;">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th><?php echo $role == 'farmer' ? 'Buyer' : 'Date'; ?></th>
                                <th>Amount</th>
                                <th>Status</th>
                                <?php if ($role == 'farmer'): ?>
                                    <th>Action</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orders as $order): ?>
                                <tr style="border-bottom: 1px solid rgba(255,255,255,0.1);">
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <img src="<?php echo $order['image'] ? 'uploads/products/' . $order['image'] : 'https://via.placeholder.com/40'; ?>" class="rounded p-1 me-3" style="width: 40px; height: 40px; object-fit: cover;">
                                            <div>
                                                <div class="fw-bold"><?php echo $order['product_name']; ?></div>
                                                <small class="text-muted">Qty: <?php echo $order['quantity']; ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if ($role == 'farmer'): ?>
                                            <?php echo $order['buyer_name']; ?>
                                        <?php else: ?>
                                            <?php echo date('M d, Y', strtotime($order['created_at'])); ?>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="fw-bold">K<?php echo $order['total_amount']; ?></div>
                                        <?php if (isset($order['discount_amount']) && $order['discount_amount'] > 0): ?>
                                            <div class="text-success small" style="font-size: 0.65rem;"><i class="fas fa-tag me-1"></i> Saved K<?php echo $order['discount_amount']; ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php 
                                            $badge_class = 'secondary';
                                            if ($order['status'] == 'completed') $badge_class = 'success';
                                            if ($order['status'] == 'pending') $badge_class = 'warning';
                                            if ($order['status'] == 'confirmed') $badge_class = 'info';
                                        ?>
                                        <span class="badge bg-<?php echo $badge_class; ?> rounded-pill"><?php echo ucfirst($order['status']); ?></span>
                                    </td>
                                    <?php if ($role == 'farmer'): ?>
                                        <td>
                                            <div class="dropdown">
                                                <button class="btn btn-sm btn-outline-light rounded-pill px-3" data-bs-toggle="dropdown">
                                                    Update
                                                </button>
                                                <ul class="dropdown-menu dropdown-menu-end shadow border-0 rounded-4">
                                                    <li><a class="dropdown-item" href="api/orders.php?action=update_status&order_id=<?php echo $order['id']; ?>&status=confirmed">Confirm</a></li>
                                                    <li><a class="dropdown-item text-success" href="api/orders.php?action=update_status&order_id=<?php echo $order['id']; ?>&status=completed">Complete</a></li>
                                                    <li><a class="dropdown-item text-danger" href="api/orders.php?action=update_status&order_id=<?php echo $order['id']; ?>&status=cancelled">Cancel</a></li>
                                                </ul>
                                            </div>
                                        </td>
                                    <?php endif; ?>
                                </tr>
                            <?php endforeach; ?>
                            
                            <?php if (empty($orders)): ?>
                                <tr>
                                    <td colspan="<?php echo $role == 'farmer' ? 5 : 4; ?>" class="text-center py-5 text-muted">No orders found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
