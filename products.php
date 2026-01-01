<?php
include 'includes/header.inc.php';
include 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$role = $_SESSION['role'];
$user_id = $_SESSION['user_id'];

// Fetch categories for the form
$stmt = $pdo->query("SELECT * FROM categories");
$categories = $stmt->fetchAll();

// Search and Filter parameters
$search = $_GET['search'] ?? '';
$category_filter = $_GET['category'] ?? '';

// Fetch products based on role and filters
$query = "SELECT p.*, c.name as category_name" . ($role == 'farmer' ? "" : ", u.full_name as farmer_name, u.membership_type as farmer_membership") . " 
          FROM products p 
          JOIN categories c ON p.category_id = c.id";
if ($role != 'farmer') {
    $query .= " JOIN users u ON p.farmer_id = u.id";
}

$where_clauses = [];
$params = [];

if ($role == 'farmer') {
    $where_clauses[] = "p.farmer_id = ?";
    $params[] = $user_id;
} else {
    $where_clauses[] = "p.status = 'active'";
}

if (!empty($search)) {
    $where_clauses[] = "p.name LIKE ?";
    $params[] = "%$search%";
}

if (!empty($category_filter)) {
    $where_clauses[] = "p.category_id = ?";
    $params[] = $category_filter;
}

if (!empty($where_clauses)) {
    $query .= " WHERE " . implode(" AND ", $where_clauses);
}

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$products = $stmt->fetchAll();
?>

<div class="d-flex">
    <?php include 'includes/sidebar.php'; ?>

    <div class="main-content w-100">
        <?php include 'includes/topbar.php'; ?>

        <div class="container-fluid p-0 animate-fade">
            <div class="row mb-4 align-items-center">
                <div class="col-md-4">
                    <h3 class="fw-bold m-0"><?php echo $role == 'farmer' ? 'My Listings' : 'Browse Products'; ?></h3>
                </div>
                <div class="col-md-8">
                    <form action="products.php" method="GET" class="d-flex gap-2">
                        <div class="input-group shadow-sm rounded-pill overflow-hidden bg-white">
                            <button type="submit" class="btn bg-white border-0 ps-3">
                                <i class="fas fa-search text-muted"></i>
                            </button>
                            <input type="text" name="search" class="form-control border-0 ps-2" placeholder="Search products..." value="<?php echo $search; ?>" style="box-shadow: none;">
                        </div>
                        <select name="category" class="form-select border-0 rounded-pill px-3 me-2" style="width: 200px;" onchange="this.form.submit()">
                            <option value="">All Categories</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo $cat['id']; ?>" <?php echo $category_filter == $cat['id'] ? 'selected' : ''; ?>>
                                    <?php echo $cat['name']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if ($role == 'farmer'): ?>
                            <button type="button" class="btn btn-primary rounded-pill px-4 flex-shrink-0" data-bs-toggle="modal" data-bs-target="#addProductModal">
                                <i class="fas fa-plus me-2"></i> Add
                            </button>
                        <?php endif; ?>
                    </form>
                </div>
            </div>

            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show rounded-4 mb-4" role="alert">
                    Product updated successfully!
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="row">
                <?php foreach ($products as $product): ?>
                    <div class="col-md-6 col-lg-4 col-xl-3">
                        <div class="product-card">
                            <span class="product-price">K<?php echo $product['price']; ?></span>
                            <?php if ($role == 'farmer'): ?>
                                <div class="position-absolute top-0 end-0 mt-3 me-3">
                                    <div class="dropdown">
                                        <button class="btn btn-light btn-sm rounded-circle" data-bs-toggle="dropdown">
                                            <i class="fas fa-ellipsis-v"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end shadow border-0 rounded-4">
                                            <li><a class="dropdown-item" href="#" onclick="editProduct(<?php echo htmlspecialchars(json_encode($product)); ?>)"><i class="fas fa-edit me-2"></i> Edit</a></li>
                                            <li><a class="dropdown-item text-danger" href="api/products.php?action=delete&id=<?php echo $product['id']; ?>"><i class="fas fa-trash me-2"></i> Delete</a></li>
                                        </ul>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <a href="product_details.php?id=<?php echo $product['id']; ?>" class="text-decoration-none text-dark">
                                <img src="<?php echo $product['image'] ? 'uploads/products/' . $product['image'] : 'https://images.unsplash.com/photo-1595147389795-37094173bfd8?ixlib=rb-1.2.1&auto=format&fit=crop&w=500&q=60'; ?>" class="product-img" alt="<?php echo $product['name']; ?>">
                            </a>
                            
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div>
                                    <h5 class="fw-bold mb-0">
                                        <?php echo $product['name']; ?>
                                        <?php if (isset($product['farmer_membership']) && $product['farmer_membership'] == 'vvip'): ?>
                                            <span class="vvip-tag">VVIP</span>
                                        <?php endif; ?>
                                    </h5>
                                    <small class="text-muted"><?php echo $product['category_name']; ?></small>
                                </div>
                                <?php if ($role == 'buyer'): ?>
                                    <div class="d-flex gap-2">
                                        <a href="api/wishlist.php?action=add&product_id=<?php echo $product['id']; ?>" class="btn btn-outline-danger btn-sm rounded-circle p-2" title="Add to Wishlist">
                                            <i class="far fa-heart"></i>
                                        </a>
                                        <a href="product_details.php?id=<?php echo $product['id']; ?>" class="btn btn-outline-primary btn-sm rounded-pill px-3" title="View Details">
                                            <i class="fas fa-eye me-1"></i> View
                                        </a>
                                    </div>
                                <?php endif; ?>
                             </div>

                             <?php if ($product['is_auction']): ?>
                                <div class="auction-status mb-3">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <span class="badge bg-danger rounded-pill"><i class="fas fa-gavel me-1"></i> Auction</span>
                                        <span class="text-danger fw-bold small countdown" data-end="<?php echo $product['auction_end']; ?>">
                                            <i class="fas fa-clock me-1"></i> Loading...
                                        </span>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <small class="text-muted">Current Bid:</small>
                                        <span class="fw-bold text-primary">K<?php echo $product['current_bid'] ?? $product['price']; ?></span>
                                    </div>
                                </div>
                             <?php endif; ?>
                            
                            <p class="text-muted small mb-3"><?php echo substr($product['description'], 0, 60) . '...'; ?></p>
                            
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="badge bg-light text-dark rounded-pill">Qty: <?php echo $product['quantity']; ?> <?php echo $product['unit']; ?></span>
                                <?php if ($role == 'buyer'): ?>
                                    <?php if ($product['is_auction']): ?>
                                        <a href="product_details.php?id=<?php echo $product['id']; ?>" class="btn btn-primary btn-sm rounded-pill px-3">Place Bid</a>
                                    <?php else: ?>
                                        <div class="d-flex gap-2">
                                            <a href="api/cart.php?action=add&product_id=<?php echo $product['id']; ?>&redirect=back" class="btn btn-success btn-sm rounded-pill px-3">
                                                <i class="fas fa-cart-plus me-1"></i> Cart
                                            </a>
                                            <form action="api/orders.php" method="POST" class="d-inline">
                                                <input type="hidden" name="action" value="place_order">
                                                <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                                <input type="hidden" name="quantity" value="1">
                                                <button type="submit" class="btn btn-primary btn-sm rounded-pill px-3">Order Now</button>
                                            </form>
                                        </div>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="badge bg-<?php echo $product['status'] == 'active' ? 'success' : 'warning'; ?> rounded-pill"><?php echo ucfirst($product['status']); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
                
                <?php if (empty($products)): ?>
                    <div class="col-12 text-center py-5">
                        <i class="fas fa-box-open fa-4x text-muted mb-3"></i>
                        <h5>No products found.</h5>
                        <p class="text-muted">Start by adding your first product listing.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Add Product Modal -->
<div class="modal fade" id="addProductModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow" style="border-radius: 30px;">
            <div class="modal-header border-0 p-4 pb-0">
                <h5 class="modal-title fw-bold">Add New Product</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <form action="api/products.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="create">
                    <div class="mb-3">
                        <label class="form-label">Product Name</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Category</label>
                            <select name="category_id" class="form-select form-control" required>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo $cat['id']; ?>"><?php echo $cat['name']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Starting Price (K)</label>
                            <input type="number" step="0.01" name="price" class="form-control" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Quantity</label>
                            <input type="number" name="quantity" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Unit (e.g., kg, bag)</label>
                            <input type="text" name="unit" class="form-control" placeholder="kg" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="is_auction" id="isAuctionCheck">
                            <label class="form-check-label" for="isAuctionCheck">Enable Auction</label>
                        </div>
                    </div>
                    <div id="auctionFields" style="display: none;">
                        <div class="mb-3">
                            <label class="form-label">Auction Duration (Hours)</label>
                            <select name="auction_duration" class="form-select">
                                <option value="24">24 Hours</option>
                                <option value="48">48 Hours</option>
                                <option value="72">72 Hours</option>
                                <option value="168">1 Week</option>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Location (Lat)</label>
                            <input type="text" name="location_lat" class="form-control" placeholder="-15.387">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Location (Lng)</label>
                            <input type="text" name="location_lng" class="form-control" placeholder="28.322">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="3" required></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-4">
                            <label class="form-label">Product Image</label>
                            <input type="file" name="image" class="form-control" accept="image/*" required>
                        </div>
                        <div class="col-md-6 mb-4">
                            <label class="form-label">Product Video (Optional)</label>
                            <input type="file" name="video" class="form-control" accept="video/*">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary w-100 py-3">Publish Product</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Edit Product Modal -->
<div class="modal fade" id="editProductModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow" style="border-radius: 30px;">
            <div class="modal-header border-0 p-4 pb-0">
                <h5 class="modal-title fw-bold">Edit Product</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <form action="api/products.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="product_id" id="edit_id">
                    <div class="mb-3">
                        <label class="form-label">Product Name</label>
                        <input type="text" name="name" id="edit_name" class="form-control" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Category</label>
                            <select name="category_id" id="edit_category" class="form-select form-control" required>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo $cat['id']; ?>"><?php echo $cat['name']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Price (K)</label>
                            <input type="number" step="0.01" name="price" id="edit_price" class="form-control" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Quantity</label>
                            <input type="number" name="quantity" id="edit_quantity" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Unit</label>
                            <input type="text" name="unit" id="edit_unit" class="form-control" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="is_auction" id="edit_is_auction">
                            <label class="form-check-label" for="edit_is_auction">Enable Auction</label>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" id="edit_desc" class="form-control" rows="3" required></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-4">
                            <label class="form-label">Replace Image (Optional)</label>
                            <input type="file" name="image" class="form-control" accept="image/*">
                        </div>
                        <div class="col-md-6 mb-4">
                            <label class="form-label">Replace Video (Optional)</label>
                            <input type="file" name="video" class="form-control" accept="video/*">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary w-100 py-3">Update Product</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.getElementById('isAuctionCheck').addEventListener('change', function() {
    document.getElementById('auctionFields').style.display = this.checked ? 'block' : 'none';
});

function editProduct(product) {
    document.getElementById('edit_id').value = product.id;
    document.getElementById('edit_name').value = product.name;
    document.getElementById('edit_category').value = product.category_id;
    document.getElementById('edit_price').value = product.price;
    document.getElementById('edit_quantity').value = product.quantity;
    document.getElementById('edit_unit').value = product.unit;
    document.getElementById('edit_desc').value = product.description;
    document.getElementById('edit_is_auction').checked = product.is_auction == 1;
    
    new bootstrap.Modal(document.getElementById('editProductModal')).show();
}

// Countdown timer script
function updateCountdowns() {
    document.querySelectorAll('.countdown').forEach(el => {
        const endTime = new Date(el.dataset.end).getTime();
        const now = new Date().getTime();
        const distance = endTime - now;

        if (distance < 0) {
            el.innerHTML = "Auction Ended";
            el.classList.remove('text-danger');
            el.classList.add('text-muted');
        } else {
            const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
            const seconds = Math.floor((distance % (1000 * 60)) / 1000);
            el.innerHTML = `<i class="fas fa-clock me-1"></i> ${hours}h ${minutes}m ${seconds}s`;
        }
    });
}
setInterval(updateCountdowns, 1000);
updateCountdowns();
</script>
</body>
</html>
