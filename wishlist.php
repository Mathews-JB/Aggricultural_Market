<?php
include 'includes/header.inc.php';
include 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'buyer') {
    header("Location: dashboard.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT p.*, c.name as category_name, u.full_name as farmer_name 
                       FROM wishlist w 
                       JOIN products p ON w.product_id = p.id 
                       JOIN categories c ON p.category_id = c.id
                       JOIN users u ON p.farmer_id = u.id
                       WHERE w.buyer_id = ?");
$stmt->execute([$user_id]);
$wishlist = $stmt->fetchAll();
?>

<div class="d-flex">
    <?php include 'includes/sidebar.php'; ?>

    <div class="main-content w-100">
        <?php include 'includes/topbar.php'; ?>

        <div class="container-fluid p-0 animate-fade">
            <h3 class="fw-bold mb-4">My Wishlist</h3>

            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show rounded-4 mb-4" role="alert">
                    Item removed from wishlist.
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="row">
                <?php foreach ($wishlist as $product): ?>
                    <div class="col-md-6 col-lg-4 col-xl-3">
                        <div class="product-card">
                            <span class="product-price">K<?php echo $product['price']; ?></span>
                            <div class="position-absolute top-0 end-0 mt-3 me-3">
                                <a href="api/wishlist.php?action=remove&product_id=<?php echo $product['id']; ?>" class="btn btn-light btn-sm rounded-circle shadow-sm">
                                    <i class="fas fa-trash text-danger"></i>
                                </a>
                            </div>
                            
                            <a href="product_details.php?id=<?php echo $product['id']; ?>" class="text-decoration-none text-dark">
                                <img src="<?php echo $product['image'] ? 'uploads/products/' . $product['image'] : 'https://images.unsplash.com/photo-1595147389795-37094173bfd8?ixlib=rb-1.2.1&auto=format&fit=crop&w=500&q=60'; ?>" class="product-img" alt="<?php echo $product['name']; ?>">
                            </a>
                            
                            <div class="mb-2">
                                <h5 class="fw-bold mb-0"><?php echo $product['name']; ?></h5>
                                <small class="text-muted"><?php echo $product['category_name']; ?></small>
                            </div>
                            
                            <div class="d-flex justify-content-between align-items-center mt-3">
                                <span class="badge bg-light text-dark rounded-pill">By <?php echo $product['farmer_name']; ?></span>
                                <form action="api/orders.php" method="POST" class="d-inline">
                                    <input type="hidden" name="action" value="place_order">
                                    <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                    <input type="hidden" name="quantity" value="1">
                                    <button type="submit" class="btn btn-primary btn-sm rounded-pill px-3">Order Now</button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
                
                <?php if (empty($wishlist)): ?>
                    <div class="col-12 text-center py-5">
                        <i class="far fa-heart fa-4x text-muted mb-3 opacity-25"></i>
                        <h5>Your wishlist is empty.</h5>
                        <p class="text-muted">Save products you're interested in for later.</p>
                        <a href="products.php" class="btn btn-primary rounded-pill px-4 mt-3">Browse Products</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
