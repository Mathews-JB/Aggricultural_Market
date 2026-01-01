<?php
include 'includes/header.inc.php';
include 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: dashboard.php");
    exit();
}

// Fetch all products
$stmt = $pdo->query("SELECT p.*, c.name as category_name, u.full_name as farmer_name 
                     FROM products p 
                     JOIN categories c ON p.category_id = c.id 
                     JOIN users u ON p.farmer_id = u.id 
                     ORDER BY p.created_at DESC");
$products = $stmt->fetchAll();

// Handle moderation actions
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = $_GET['id'];
    $new_status = ($_GET['action'] == 'approve') ? 'active' : 'moderated';
    
    try {
        $stmt = $pdo->prepare("UPDATE products SET status = ? WHERE id = ?");
        $stmt->execute([$new_status, $id]);
        header("Location: admin_products.php?success=1");
        exit();
    } catch (PDOException $e) {
        die("Error: " . $e->getMessage());
    }
}
?>

<div class="d-flex">
    <?php include 'includes/sidebar.php'; ?>

    <div class="main-content w-100">
        <?php include 'includes/topbar.php'; ?>

        <div class="container-fluid p-0 animate-fade">
            <h3 class="fw-bold mb-4">Product Moderation</h3>

            <div class="stats-card">
                <div class="table-responsive">
                    <table class="table table-dark table-hover mb-0" style="background: transparent;">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Farmer</th>
                                <th>Category</th>
                                <th>Price</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($products as $product): ?>
                                <tr style="border-bottom: 1px solid rgba(255,255,255,0.1);">
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <img src="<?php echo $product['image'] ? 'uploads/products/' . $product['image'] : 'https://via.placeholder.com/40'; ?>" class="rounded p-1 me-3" style="width: 40px; height: 40px; object-fit: cover;">
                                            <div>
                                                <div class="fw-bold"><?php echo $product['name']; ?></div>
                                                <small class="text-muted">ID: #<?php echo $product['id']; ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?php echo $product['farmer_name']; ?></td>
                                    <td><span class="badge bg-light text-dark rounded-pill"><?php echo $product['category_name']; ?></span></td>
                                    <td>$<?php echo $product['price']; ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo $product['status'] == 'active' ? 'success' : 'warning'; ?> rounded-pill">
                                            <?php echo ucfirst($product['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group">
                                            <?php if ($product['status'] != 'active'): ?>
                                                <a href="admin_products.php?action=approve&id=<?php echo $product['id']; ?>" class="btn btn-sm btn-outline-success rounded-pill px-3 me-2">Approve</a>
                                            <?php else: ?>
                                                <a href="admin_products.php?action=suspend&id=<?php echo $product['id']; ?>" class="btn btn-sm btn-outline-warning rounded-pill px-3">Moderate</a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
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
