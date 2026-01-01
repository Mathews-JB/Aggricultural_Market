<?php
include 'includes/header.inc.php';
include 'config.php';

// Check if logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$role = $_SESSION['role'];
$user_id = $_SESSION['user_id'];

// Fetch stats for dashboard
if ($role == 'farmer') {
    $stmt = $pdo->prepare("SELECT o.*, oi.quantity, p.name as product_name, p.image, u.full_name as buyer_name 
                           FROM orders o 
                           JOIN order_items oi ON o.id = oi.order_id 
                           JOIN products p ON oi.product_id = p.id 
                           JOIN users u ON o.buyer_id = u.id
                           WHERE p.farmer_id = ? ORDER BY o.created_at DESC LIMIT 2");
    $stmt->execute([$user_id]);
    $recent_activity = $stmt->fetchAll();

    // Fetch active auctions for farmer
    $stmt = $pdo->prepare("SELECT * FROM products WHERE farmer_id = ? AND is_auction = 1 AND auction_end > NOW() ORDER BY auction_end ASC");
    $stmt->execute([$user_id]);
    $active_auctions = $stmt->fetchAll();
} else {
    $stmt = $pdo->prepare("SELECT o.*, oi.quantity, p.name as product_name, p.image 
                           FROM orders o 
                           JOIN order_items oi ON o.id = oi.order_id 
                           JOIN products p ON oi.product_id = p.id 
                           WHERE o.buyer_id = ? ORDER BY o.created_at DESC LIMIT 2");
    $stmt->execute([$user_id]);
    $recent_activity = $stmt->fetchAll();

    // Fetch products where buyer has bid
    $stmt = $pdo->prepare("SELECT DISTINCT p.*, b.amount as my_bid 
                           FROM products p 
                           JOIN bids b ON p.id = b.product_id 
                           WHERE b.buyer_id = ? AND p.auction_end > NOW() ORDER BY p.auction_end ASC");
    $stmt->execute([$user_id]);
    $active_auctions = $stmt->fetchAll();
}

// Fetch recommendations
// Fetch recommendations - Optimized to avoid ORDER BY RAND() which causes temp tables
$stmt_rec = $pdo->query("SELECT p.*, c.name as category_name FROM products p JOIN categories c ON p.category_id = c.id WHERE p.status = 'active' ORDER BY p.created_at DESC LIMIT 2");
$recommendations = $stmt_rec->fetchAll();
?>

<div class="d-flex">
    <!-- Sidebar -->
    <?php include 'includes/sidebar.php'; ?>

    <!-- Main Content -->
    <div class="main-content w-100">
        <!-- Topbar -->
        <?php include 'includes/topbar.php'; ?>

        <div class="container-fluid p-0 animate-fade">
            <!-- Hero Promo Card -->
            <?php 
            // Ensure we have current membership status from DB
            $stmt_mbr = $pdo->prepare("SELECT membership_type FROM users WHERE id = ?");
            $stmt_mbr->execute([$user_id]);
            $membership = $stmt_mbr->fetchColumn();
            $_SESSION['membership_type'] = $membership; // Keep session synced
            ?>
            <div class="promo-card <?php echo $membership == 'vvip' ? 'vvip-active' : ''; ?>">
                <div class="row align-items-center">
                    <div class="col-lg-6 promo-content">
                        <?php if ($membership == 'vvip'): ?>
                            <div class="vvip-badge-top mb-3"><i class="fas fa-crown me-2"></i> VVIP MEMBER</div>
                            <h2>Welcome to the <span>Luxury Circle</span></h2>
                            <p>You are enjoying priority support, exclusive listing visibility, and zero platform fees on auctions.</p>
                            <a href="products.php" class="btn btn-light rounded-pill px-5 py-3 fw-bold">Explore Exclusive Deals</a>
                        <?php elseif ($role == 'farmer'): ?>
                            <h2>Grow Your Business to <span>VVIP Level</span></h2>
                            <p>Get your products featured at the top of searches and enjoy 0% commission on sales.</p>
                            <a href="api/membership.php" class="btn-promo shadow">Upgrade for K199/mo</a>
                        <?php else: ?>
                            <h2>Upgrade Your Shopping to <span>VVIP Member</span></h2>
                            <p>Elevate your status to unlock hidden deals, bulk discounts, and priority delivery.</p>
                            <a href="api/membership.php" class="btn-promo shadow">Upgrade Now - K99/mo</a>
                        <?php endif; ?>
                    </div>
                    <div class="col-lg-6 text-end d-none d-lg-block">
                        <img src="https://images.unsplash.com/photo-1530519729491-acf5092a8371?auto=format&fit=crop&w=800&q=80" alt="Promo" class="rounded-5 shadow-lg" style="max-height: 250px; width: 100%; object-fit: cover;">
                    </div>
                </div>
            </div>

            <!-- Dashboard Content Based on Role -->
            <div class="row">
                <div class="col-lg-8">
                    <!-- Active Auctions -->
                    <div class="stats-card mb-4" style="border: none;">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h4 class="m-0 text-white"><i class="fas fa-gavel me-2 text-warning"></i> Active Auctions</h4>
                            <a href="products.php" class="btn btn-sm btn-outline-light rounded-pill">View All</a>
                        </div>
                        
                        <div class="row g-3">
                            <?php foreach ($active_auctions as $auc): ?>
                            <div class="col-md-6">
                                <div class="bg-white bg-opacity-10 rounded-4 p-3 border border-white border-opacity-10 h-100">
                                    <div class="d-flex align-items-center mb-3">
                                        <div class="bg-light rounded-3 overflow-hidden me-3" style="width: 50px; height: 50px;">
                                            <img src="<?php echo $auc['image'] ? 'uploads/products/' . $auc['image'] : 'assets/img/default_product.png'; ?>" class="w-100 h-100 object-fit-cover">
                                        </div>
                                        <div class="flex-grow-1 overflow-hidden">
                                            <h6 class="text-white fw-bold mb-0 text-truncate"><?php echo $auc['name']; ?></h6>
                                            <small class="text-white-50">High Bid: K<?php echo $auc['current_bid']; ?></small>
                                        </div>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="badge bg-danger rounded-pill countdown" data-end="<?php echo $auc['auction_end']; ?>">--:--:--</span>
                                        <a href="product_details.php?id=<?php echo $auc['id']; ?>" class="btn btn-primary btn-sm rounded-pill px-3">View</a>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                            <?php if (empty($active_auctions)): ?>
                                <div class="col-12 text-center py-4 text-white-50">
                                    <p class="m-0">No active auctions at the moment.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Statistics or Market Overview -->
                    <div class="stats-card mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h4 class="m-0"><?php echo $role == 'farmer' ? 'Recent Sales' : 'Latest Orders'; ?></h4>
                            <div>
                                <span class="badge bg-secondary p-2 me-2">Last Week</span>
                                <span class="badge bg-warning text-dark p-2">This Week</span>
                                <button class="btn btn-sm btn-outline-light ms-2"><i class="fas fa-filter"></i></button>
                            </div>
                        </div>
                        
                        <div class="table-responsive">
                            <table class="table table-hover mb-0 themed-table" style="background: transparent;">
                                <thead>
                                    <tr>
                                        <th>Item</th>
                                        <th>Amount</th>
                                        <th>Method</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_activity as $act): ?>
                                    <tr style="border-bottom: 1px solid var(--border-color);">
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="bg-light rounded p-1 me-3" style="width: 40px; height: 40px; overflow: hidden;">
                                                    <img src="<?php echo $act['image'] ? 'uploads/products/' . $act['image'] : 'assets/img/default_product.png'; ?>" class="w-100 h-100 object-fit-cover">
                                                </div>
                                                <div>
                                                    <div class="fw-bold"><?php echo $act['product_name']; ?></div>
                                                    <small class="text-muted"><?php echo $role == 'farmer' ? 'Buyer: ' . $act['buyer_name'] : 'Ordered'; ?></small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>K <?php echo number_format($act['total_amount'], 2); ?></td>
                                        <td>Transfer</td>
                                        <td>
                                            <?php 
                                            $status_class = 'text-warning';
                                            if ($act['status'] == 'completed') $status_class = 'text-success';
                                            if ($act['status'] == 'cancelled') $status_class = 'text-danger';
                                            ?>
                                            <span class="<?php echo $status_class; ?>"><?php echo ucfirst($act['status']); ?></span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <?php if (empty($recent_activity)): ?>
                                        <tr><td colspan="4" class="text-center py-4 text-muted">No recent activity.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="card border-0 shadow-sm mb-4" style="border-radius: 30px; background-color: var(--secondary-teal); color: white;">
                        <div class="card-body p-4">
                            <ul class="nav nav-pills mb-4" id="pills-tab" role="tablist">
                                <li class="nav-item flex-fill" role="presentation">
                                    <button class="nav-link active w-100 rounded-pill" id="pills-recommend-tab" data-bs-toggle="pill" data-bs-target="#pills-recommend" type="button" role="tab" style="background-color: var(--primary-teal); color: white;">Recommend</button>
                                </li>
                                <li class="nav-item flex-fill" role="presentation">
                                    <button class="nav-link w-100 rounded-pill text-white" id="pills-you-tab" data-bs-toggle="pill" data-bs-target="#pills-you" type="button" role="tab">For You</button>
                                </li>
                            </ul>
                            
                            <div class="tab-content" id="pills-tabContent">
                                <div class="tab-pane fade show active" id="pills-recommend" role="tabpanel">
                                    <div class="row g-3">
                                        <?php foreach ($recommendations as $rec): ?>
                                        <div class="col-6">
                                            <div class="product-item">
                                                <img src="<?php echo $rec['image'] ? 'uploads/products/' . $rec['image'] : 'https://via.placeholder.com/150'; ?>" class="img-fluid rounded-4 mb-2" alt="Product" style="height: 100px; width: 100%; object-fit: cover;">
                                                <div class="small fw-bold text-truncate"><?php echo $rec['name']; ?></div>
                                                <div class="text-warning small">$ <?php echo $rec['price']; ?></div>
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                        <?php if (empty($recommendations)): ?>
                                            <div class="text-center py-3 opacity-50">No recommendations.</div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Countdown timer script
function updateCountdowns() {
    document.querySelectorAll('.countdown').forEach(el => {
        const endTimeStr = el.dataset.end;
        if (!endTimeStr) return;
        
        const endTime = new Date(endTimeStr).getTime();
        const now = new Date().getTime();
        const distance = endTime - now;

        if (distance < 0) {
            el.innerHTML = "Ended";
            el.classList.remove('bg-danger');
            el.classList.add('bg-secondary');
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
</script>
</body>
</html>
