<?php
include 'includes/header.inc.php';
include 'config.php';

if (!isset($_GET['id'])) {
    header("Location: products.php");
    exit();
}

$product_id = $_GET['id'];
$stmt = $pdo->prepare("SELECT p.*, c.name as category_name, u.full_name as farmer_name, u.profile_image as farmer_img, u.phone_number, u.membership_type as farmer_membership 
                       FROM products p 
                       JOIN categories c ON p.category_id = c.id 
                       JOIN users u ON p.farmer_id = u.id 
                       WHERE p.id = ?");
$stmt->execute([$product_id]);
$product = $stmt->fetch();

if (!$product) {
    die("Product not found.");
}

// Fetch bid history if it's an auction
$bids = [];
if ($product['is_auction']) {
    $stmt = $pdo->prepare("SELECT b.*, u.full_name FROM bids b JOIN users u ON b.buyer_id = u.id WHERE b.product_id = ? ORDER BY b.amount DESC");
    $stmt->execute([$product_id]);
    $bids = $stmt->fetchAll();
}
?>

<!-- Add Leaflet CSS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>

<div class="d-flex">
    <?php include 'includes/sidebar.php'; ?>

    <div class="main-content w-100">
        <?php include 'includes/topbar.php'; ?>

        <div class="container-fluid p-4 animate-fade">
            <nav aria-label="breadcrumb" class="mb-4">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="products.php" class="text-decoration-none">Products</a></li>
                    <li class="breadcrumb-item active"><?php echo $product['name']; ?></li>
                </ol>
            </nav>

            <div class="row g-5">
                <!-- Product Media & Map -->
                <div class="col-lg-6">
                    <div class="rounded-5 overflow-hidden shadow-lg mb-4 bg-dark">
                        <?php if ($product['video_url']): ?>
                            <video controls class="w-100" style="max-height: 400px; object-fit: contain;">
                                <source src="uploads/videos/<?php echo $product['video_url']; ?>" type="video/mp4">
                                Your browser does not support the video tag.
                            </video>
                        <?php endif; ?>
                        <img src="<?php echo $product['image'] ? 'uploads/products/' . $product['image'] : 'https://images.unsplash.com/photo-1595147389795-37094173bfd8?ixlib=rb-1.2.1&auto=format&fit=crop&w=1200&q=80'; ?>" class="img-fluid w-100" alt="<?php echo $product['name']; ?>">
                    </div>

                    <?php if ($product['location_lat'] && $product['location_lng']): ?>
                    <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
                        <div class="card-header bg-white border-0 py-3">
                            <h6 class="fw-bold m-0"><i class="fas fa-map-marker-alt text-danger me-2"></i> Farm Location</h6>
                        </div>
                        <div id="map" style="height: 300px;"></div>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Product Info & Bidding -->
                <div class="col-lg-6">
                    <div class="card border-0 bg-transparent">
                        <div class="mb-2">
                            <span class="badge bg-warning text-dark rounded-pill px-3"><?php echo $product['category_name']; ?></span>
                            <?php if ($product['is_auction']): ?>
                                <span class="badge bg-danger rounded-pill px-3 ms-2">LIVE AUCTION</span>
                            <?php endif; ?>
                        </div>
                        <h1 class="fw-bold mb-3">
                            <?php echo $product['name']; ?>
                            <?php if (isset($product['farmer_membership']) && $product['farmer_membership'] == 'vvip'): ?>
                                <span class="vvip-tag">VVIP</span>
                            <?php endif; ?>
                        </h1>

                        <?php if ($product['is_auction']): ?>
                            <div class="auction-box bg-white p-4 rounded-4 shadow-sm mb-4 border-start border-4 border-danger">
                                <div class="row align-items-center">
                                    <div class="col-md-6">
                                        <small class="text-muted d-block mb-1">Current Bid</small>
                                        <h2 class="text-primary fw-bold mb-0">K<?php echo $product['current_bid'] ?: $product['price']; ?></h2>
                                    </div>
                                    <div class="col-md-6 text-md-end">
                                        <small class="text-muted d-block mb-1">Time Remaining</small>
                                        <h4 class="text-danger fw-bold mb-0 countdown" data-end="<?php echo $product['auction_end']; ?>">--:--:--</h4>
                                    </div>
                                </div>
                            </div>

                            <?php if ($_SESSION['role'] == 'buyer'): ?>
                                <div class="card border-0 shadow-sm rounded-4 p-4 mb-4">
                                    <h6 class="fw-bold mb-3">Place Your Bid</h6>
                                    <form id="bidForm" class="d-flex gap-2">
                                        <input type="hidden" name="action" value="place_bid">
                                        <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                        <div class="input-group">
                                            <span class="input-group-text bg-light border-0">K</span>
                                            <input type="number" name="amount" class="form-control bg-light border-0" placeholder="Enter bid amount" required min="<?php echo ($product['current_bid'] ?: $product['price']) + 1; ?>">
                                        </div>
                                        <button type="submit" class="btn btn-primary rounded-pill px-4">Place Bid</button>
                                    </form>
                                    <div id="bidMessage" class="mt-2 small"></div>
                                </div>
                            <?php endif; ?>

                            <div class="card border-0 shadow-sm rounded-4 mb-4">
                                <div class="card-header bg-white border-0 py-3">
                                    <h6 class="fw-bold m-0"><i class="fas fa-history text-muted me-2"></i> Bidding History</h6>
                                </div>
                                <div class="card-body p-0">
                                    <div class="table-responsive">
                                        <table class="table table-hover mb-0">
                                            <thead class="bg-light">
                                                <tr>
                                                    <th class="ps-4">Bidder</th>
                                                    <th>Amount</th>
                                                    <th>Time</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if (empty($bids)): ?>
                                                    <tr><td colspan="3" class="text-center py-4 text-muted">No bids yet. Be the first!</td></tr>
                                                <?php else: ?>
                                                    <?php foreach ($bids as $bid): ?>
                                                        <tr>
                                                            <td class="ps-4 fw-medium"><?php echo htmlspecialchars($bid['full_name']); ?></td>
                                                            <td class="text-primary fw-bold">K<?php echo $bid['amount']; ?></td>
                                                            <td class="text-muted small"><?php echo date('M d, H:i', strtotime($bid['created_at'])); ?></td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        <?php else: ?>
                            <h2 class="text-success fw-bold mb-4">K<?php echo $product['price']; ?> <small class="text-muted fs-6">/ <?php echo $product['unit']; ?></small></h2>
                        <?php endif; ?>

                        <!-- Added: User's own order status for this product -->
                        <?php if ($_SESSION['role'] == 'buyer'): ?>
                            <?php 
                            $stmt = $pdo->prepare("SELECT o.status, o.created_at, oi.quantity 
                                                   FROM orders o 
                                                   JOIN order_items oi ON o.id = oi.order_id 
                                                   WHERE o.buyer_id = ? AND oi.product_id = ?");
                            $stmt->execute([$_SESSION['user_id'], $product_id]);
                            $my_order = $stmt->fetch();
                            ?>
                            <?php if ($my_order): ?>
                                <div class="card border-0 shadow-sm rounded-4 mb-4 bg-primary bg-opacity-10 border-start border-4 border-primary">
                                    <div class="card-body">
                                        <h6 class="fw-bold text-primary mb-2"><i class="fas fa-shopping-bag me-2"></i> Your Order Status</h6>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <span class="badge bg-<?php echo $my_order['status'] == 'completed' ? 'success' : 'warning'; ?> rounded-pill text-capitalize"><?php echo $my_order['status']; ?></span>
                                                <small class="text-muted ms-2">Ordered <?php echo $my_order['quantity']; ?> <?php echo $product['unit']; ?></small>
                                            </div>
                                            <small class="text-muted"><?php echo date('M d, Y', strtotime($my_order['created_at'])); ?></small>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>

                        <!-- Added: Recent Orders section for Farmer to keep things organized -->
                        <?php if ($_SESSION['user_id'] == $product['farmer_id']): ?>
                            <?php 
                            $stmt = $pdo->prepare("SELECT o.*, u.full_name as buyer_name, oi.quantity 
                                                   FROM orders o 
                                                   JOIN order_items oi ON o.id = oi.order_id 
                                                   JOIN users u ON o.buyer_id = u.id 
                                                   WHERE oi.product_id = ? ORDER BY o.created_at DESC LIMIT 5");
                            $stmt->execute([$product_id]);
                            $recent_orders = $stmt->fetchAll();
                            ?>
                            <div class="card border-0 shadow-sm rounded-4 mb-4 bg-light border-start border-4 border-success">
                                <div class="card-header bg-transparent border-0 py-3">
                                    <h6 class="fw-bold m-0 text-success"><i class="fas fa-receipt me-2"></i> Recent Direct Orders</h6>
                                </div>
                                <div class="card-body p-0">
                                    <div class="table-responsive">
                                        <table class="table mb-0">
                                            <thead>
                                                <tr class="small text-muted">
                                                    <th class="ps-4">Buyer</th>
                                                    <th>Qty</th>
                                                    <th>Status</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if (empty($recent_orders)): ?>
                                                    <tr><td colspan="3" class="text-center py-3 text-muted">No direct orders yet.</td></tr>
                                                <?php else: ?>
                                                    <?php foreach ($recent_orders as $ro): ?>
                                                        <tr>
                                                            <td class="ps-4 small"><?php echo htmlspecialchars($ro['buyer_name']); ?></td>
                                                            <td class="small"><?php echo $ro['quantity']; ?></td>
                                                            <td><span class="badge bg-<?php echo $ro['status'] == 'completed' ? 'success' : 'warning'; ?> rounded-pill" style="font-size: 0.6rem;"><?php echo $ro['status']; ?></span></td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <p class="text-muted mb-5 fs-5"><?php echo $product['description']; ?></p>

                        <!-- Farmer Card -->
                        <div class="card border-0 shadow-sm rounded-4 p-3 mb-4">
                            <div class="d-flex align-items-center">
                                <img src="assets/img/<?php echo $product['farmer_img']; ?>" class="rounded-circle me-3" style="width: 60px; height: 60px; object-fit: cover;">
                                <div>
                                    <h6 class="fw-bold mb-0"><?php echo $product['farmer_name']; ?></h6>
                                    <small class="text-muted"><i class="fas fa-check-circle text-primary me-1"></i> Verified Farmer</small>
                                </div>
                                <div class="ms-auto d-flex gap-2">
                                    <?php if ($product['phone_number']): ?>
                                        <a href="tel:<?php echo $product['phone_number']; ?>" class="btn btn-outline-success rounded-circle" title="Call Farmer">
                                            <i class="fas fa-phone"></i>
                                        </a>
                                    <?php endif; ?>
                                    <a href="messages.php?contact=<?php echo $product['farmer_id']; ?>" class="btn btn-primary rounded-pill px-4">
                                        <i class="fas fa-comment-dots me-2"></i> Chat
                                    </a>
                                </div>
                            </div>
                        </div>

                        <?php if ($_SESSION['role'] == 'buyer' && !$product['is_auction']): ?>
                        <div class="row g-3">
                            <div class="col-md-12">
                                <div class="d-flex gap-3 align-items-center">
                                    <form action="api/orders.php" method="POST" class="flex-grow-1">
                                        <input type="hidden" name="action" value="place_order">
                                        <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                        <div class="input-group">
                                            <input type="number" name="quantity" class="form-control rounded-start-pill ps-4" value="1" min="1" max="<?php echo $product['quantity']; ?>" style="max-width: 100px;">
                                            <button class="btn btn-primary rounded-end-pill px-4" type="submit">Buy Now Direct</button>
                                        </div>
                                    </form>
                                    
                                    <a href="api/cart.php?action=add&product_id=<?php echo $product['id']; ?>" class="btn btn-success rounded-pill px-4 py-3">
                                        <i class="fas fa-cart-plus me-2"></i> Add to Cart
                                    </a>

                                    <a href="api/wishlist.php?action=add&product_id=<?php echo $product['id']; ?>" class="btn btn-outline-danger rounded-pill px-4 py-3">
                                        <i class="far fa-heart me-2"></i> Wishlist
                                    </a>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>

                        <div class="mt-4">
                            <small class="text-muted"><i class="fas fa-box me-2"></i> Stock: <?php echo $product['quantity']; ?> <?php echo $product['unit']; ?> available</small>
                            <small class="text-muted ms-4"><i class="fas fa-truck me-2"></i> Shipping: Buyer Pickup / Farmer Delivery</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Leaflet JS -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
// Map Initialization
<?php if ($product['location_lat'] && $product['location_lng']): ?>
    const map = L.map('map').setView([<?php echo $product['location_lat']; ?>, <?php echo $product['location_lng']; ?>], 13);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
    }).addTo(map);
    L.marker([<?php echo $product['location_lat']; ?>, <?php echo $product['location_lng']; ?>]).addTo(map)
        .bindPopup('Farmer\'s Warehouse/Farm Location')
        .openPopup();
<?php endif; ?>

// Countdown timer script
function updateCountdowns() {
    document.querySelectorAll('.countdown').forEach(el => {
        const endTimeStr = el.dataset.end;
        if (!endTimeStr) return;
        
        const endTime = new Date(endTimeStr).getTime();
        const now = new Date().getTime();
        const distance = endTime - now;

        if (distance < 0) {
            el.innerHTML = "Auction Ended";
            el.classList.remove('text-danger');
            el.classList.add('text-muted');
            if (document.getElementById('bidForm')) {
                document.getElementById('bidForm').style.opacity = '0.5';
                document.getElementById('bidForm').style.pointerEvents = 'none';
            }
        } else {
            const h = Math.floor(distance / (1000 * 60 * 60));
            const m = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
            const s = Math.floor((distance % (1000 * 60)) / 1000);
            el.innerHTML = `${h}h ${m}m ${s}s`;
        }
    });
}
setInterval(updateCountdowns, 1000);
updateCountdowns();

// Bid Form Handling
if (document.getElementById('bidForm')) {
    document.getElementById('bidForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        const msgEl = document.getElementById('bidMessage');
        
        fetch('api/bids.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                msgEl.className = 'mt-2 small text-success';
                msgEl.innerText = data.message;
                setTimeout(() => location.reload(), 1500);
            } else {
                msgEl.className = 'mt-2 small text-danger';
                msgEl.innerText = data.message;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            msgEl.className = 'mt-2 small text-danger';
            msgEl.innerText = 'Something went wrong. Please try again.';
        });
    });
}
</script>
</body>
</html>
