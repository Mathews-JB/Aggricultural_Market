<?php
include 'includes/header.inc.php';
include 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'farmer') {
    header("Location: dashboard.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// 1. Fetch Monthly Revenue
$stmt = $pdo->prepare("SELECT DATE_FORMAT(o.created_at, '%b') as month, SUM(oi.price * oi.quantity) as earnings 
                       FROM orders o 
                       JOIN order_items oi ON o.id = oi.order_id 
                       JOIN products p ON oi.product_id = p.id 
                       WHERE p.farmer_id = ? AND o.status != 'cancelled'
                       GROUP BY MONTH(o.created_at) 
                       ORDER BY o.created_at ASC");
$stmt->execute([$user_id]);
$monthly_raw = $stmt->fetchAll();

$months = [];
$sales_data = [];
foreach ($monthly_raw as $row) {
    $months[] = $row['month'];
    $sales_data[] = (float)$row['earnings'];
}

// Fallback if no data
if (empty($months)) {
    $months = ["Empty"];
    $sales_data = [0];
}

// 2. Fetch Category Breakdown
$stmt = $pdo->prepare("SELECT c.name as category, SUM(oi.price * oi.quantity) as total_sales 
                       FROM categories c 
                       JOIN products p ON c.id = p.category_id 
                       JOIN order_items oi ON p.id = oi.product_id 
                       JOIN orders o ON oi.order_id = o.id 
                       WHERE p.farmer_id = ? AND o.status != 'cancelled'
                       GROUP BY c.id");
$stmt->execute([$user_id]);
$category_raw = $stmt->fetchAll();

$category_labels = [];
$category_data = [];
foreach ($category_raw as $row) {
    $category_labels[] = $row['category'];
    $category_data[] = (float)$row['total_sales'];
}

// Fallback for category
if (empty($category_labels)) {
    $category_labels = ["No Data"];
    $category_data = [1];
}

// 3. Fetch Overview Stats
$total_earnings = $pdo->prepare("SELECT SUM(oi.price * oi.quantity) FROM order_items oi JOIN products p ON oi.product_id = p.id JOIN orders o ON oi.order_id = o.id WHERE p.farmer_id = ? AND o.status != 'cancelled'");
$total_earnings->execute([$user_id]);
$total_sales_value = $total_earnings->fetchColumn() ?: 0;

$active_products_count = $pdo->prepare("SELECT COUNT(*) FROM products WHERE farmer_id = ? AND status = 'active'");
$active_products_count->execute([$user_id]);
$num_active = $active_products_count->fetchColumn();
?>

<div class="d-flex">
    <?php include 'includes/sidebar.php'; ?>

    <div class="main-content w-100">
        <?php include 'includes/topbar.php'; ?>

        <div class="container-fluid p-0 animate-fade">
            <h3 class="fw-bold mb-4">Sales Analytics</h3>

            <div class="row">
                <!-- Revenue Chart -->
                <div class="col-lg-8 mb-4">
                    <div class="card border-0 shadow-sm rounded-5 overflow-hidden">
                        <div class="card-header bg-white border-0 p-4">
                            <h5 class="fw-bold mb-0">Revenue Overview</h5>
                            <small class="text-muted">Monthly earnings from all products</small>
                        </div>
                        <div class="card-body p-4">
                            <div style="position: relative; height: 300px;">
                                <canvas id="revenueChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Category Breakdown -->
                <div class="col-lg-4 mb-4">
                    <div class="card border-0 shadow-sm rounded-5 overflow-hidden">
                        <div class="card-header bg-white border-0 p-4">
                            <h5 class="fw-bold mb-0">Category Sales</h5>
                            <small class="text-muted">Distribution of sales by category</small>
                        </div>
                        <div class="card-body p-4">
                            <div style="position: relative; height: 300px;">
                                <canvas id="categoryChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Summary Cards -->
            <div class="row">
                <div class="col-md-4 mb-4">
                    <div class="stats-card bg-primary bg-gradient shadow-sm">
                        <h6 class="opacity-75">Total Earnings</h6>
                        <h3>K <?php echo number_format($total_sales_value, 2); ?></h3>
                        <small><i class="fas fa-chart-line me-1"></i> Lifetime platform revenue</small>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="stats-card bg-success bg-gradient shadow-sm">
                        <h6 class="opacity-75">Active Products</h6>
                        <h3><?php echo $num_active; ?></h3>
                        <small><i class="fas fa-check-circle me-1"></i> Listed and available</small>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="stats-card bg-info bg-gradient shadow-sm">
                        <h6 class="opacity-75">Average Rating</h6>
                        <h3>4.8 / 5.0</h3>
                        <small><i class="fas fa-star me-1"></i> Based on buyer feedback</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Revenue Chart
    const ctxRevenue = document.getElementById('revenueChart');
    if (ctxRevenue) {
        new Chart(ctxRevenue, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($months); ?>,
                datasets: [{
                    label: 'Revenue (K)',
                    data: <?php echo json_encode($sales_data); ?>,
                    borderColor: '#e67e22',
                    backgroundColor: 'rgba(230, 126, 34, 0.1)',
                    fill: true,
                    tension: 0.4,
                    pointRadius: 6,
                    pointBackgroundColor: '#e67e22',
                    borderWidth: 3
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { 
                    legend: { display: false },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                        backgroundColor: '#1e3a3a'
                    }
                },
                scales: {
                    y: { 
                        beginAtZero: true,
                        grid: { color: 'rgba(0,0,0,0.05)' } 
                    },
                    x: { grid: { display: false } }
                }
            }
        });
    }

    // Category Chart
    const ctxCategory = document.getElementById('categoryChart');
    if (ctxCategory) {
        new Chart(ctxCategory, {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode($category_labels); ?>,
                datasets: [{
                    data: <?php echo json_encode($category_data); ?>,
                    backgroundColor: ['#1e3a3a', '#e67e22', '#2ecc71', '#3498db', '#f1c40f', '#9b59b6'],
                    borderWidth: 0,
                    hoverOffset: 10
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { 
                    legend: { position: 'bottom', labels: { usePointStyle: true, padding: 20 } } 
                },
                cutout: '70%'
            }
        });
    }
});
</script>
</body>
</html>
