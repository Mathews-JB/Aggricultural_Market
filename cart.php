<?php
include 'includes/header.inc.php';
include 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'buyer') {
    header("Location: dashboard.php");
    exit();
}

$cart = $_SESSION['cart'] ?? [];
$cart_items = [];
$total_price = 0;

if (!empty($cart)) {
    $product_ids = implode(',', array_keys($cart));
    $stmt = $pdo->query("SELECT p.*, c.name as category_name FROM products p JOIN categories c ON p.category_id = c.id WHERE p.id IN ($product_ids)");
    while ($row = $stmt->fetch()) {
        $row['qty'] = $cart[$row['id']];
        $row['subtotal'] = $row['price'] * $row['qty'];
        $total_price += $row['subtotal'];
        $cart_items[] = $row;
    }
}
?>

<div class="d-flex">
    <?php include 'includes/sidebar.php'; ?>

    <div class="main-content w-100">
        <?php include 'includes/topbar.php'; ?>

        <div class="container-fluid p-0 animate-fade">
            <h3 class="fw-bold mb-4">My Shopping Cart</h3>

            <div class="row">
                <div class="col-lg-8">
                    <div class="card border-0 shadow-sm rounded-5 overflow-hidden mb-4">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0 p-4">
                                <thead class="bg-light">
                                    <tr>
                                        <th class="border-0 p-4">Product</th>
                                        <th class="border-0 p-4">Price</th>
                                        <th class="border-0 p-4">Quantity</th>
                                        <th class="border-0 p-4">Subtotal</th>
                                        <th class="border-0 p-4"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($cart_items as $item): ?>
                                    <tr>
                                        <td class="p-4 align-middle">
                                            <div class="d-flex align-items-center">
                                                <img src="<?php echo $item['image'] ? 'uploads/products/' . $item['image'] : 'https://via.placeholder.com/60'; ?>" class="rounded-3 me-3" style="width: 60px; height: 60px; object-fit: cover;">
                                                <div>
                                                    <h6 class="fw-bold mb-0"><?php echo $item['name']; ?></h6>
                                                    <small class="text-muted"><?php echo $item['category_name']; ?></small>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="p-4 align-middle fw-bold">K<?php echo $item['price']; ?></td>
                                        <td class="p-4 align-middle">
                                            <form action="api/cart.php" method="POST" class="d-flex align-items-center" style="width: 100px;">
                                                <input type="hidden" name="action" value="update">
                                                <input type="hidden" name="product_id" value="<?php echo $item['id']; ?>">
                                                <input type="number" name="quantity" class="form-control form-control-sm rounded-pill text-center" value="<?php echo $item['qty']; ?>" onchange="this.form.submit()">
                                            </form>
                                        </td>
                                        <td class="p-4 align-middle fw-bold text-success">K<?php echo number_format($item['subtotal'], 2); ?></td>
                                        <td class="p-4 align-middle text-end">
                                            <a href="api/cart.php?action=remove&product_id=<?php echo $item['id']; ?>" class="text-danger"><i class="fas fa-times"></i></a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                    
                                    <?php if (empty($cart_items)): ?>
                                    <tr>
                                        <td colspan="5" class="text-center py-5">
                                            <div class="py-4">
                                                <i class="fas fa-shopping-basket fa-4x text-muted mb-4 opacity-25"></i>
                                                <h4 class="fw-bold text-muted">Your cart is feeling lonely!</h4>
                                                <p class="text-muted mb-4">You haven't added any premium agricultural products yet.</p>
                                                <a href="products.php" class="btn btn-primary btn-lg rounded-pill px-5 shadow">
                                                    Explore Marketplace
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="card border-0 shadow-sm rounded-5 p-4 bg-primary text-white">
                        <h5 class="fw-bold mb-4">Order Summary</h5>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Subtotal</span>
                            <span>K<?php echo number_format($total_price, 2); ?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-4">
                            <span>Shipping</span>
                            <span>K0.00</span>
                        </div>
                        <hr class="bg-white">
                        <div class="d-flex justify-content-between mb-4">
                            <span class="fs-5 fw-bold">Total</span>
                            <span class="fs-5 fw-bold">K<?php echo number_format($total_price, 2); ?></span>
                        </div>
                        <form action="api/checkout.php" method="POST">
                            <button type="submit" class="btn btn-light w-100 rounded-pill py-3 fw-bold text-primary" <?php echo empty($cart_items) ? 'disabled' : ''; ?>>
                                Proceed to Checkout
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
