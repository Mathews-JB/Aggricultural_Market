<?php
include 'includes/header.inc.php';
include 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: dashboard.php");
    exit();
}

// Fetch system stats
$user_count = $pdo->query("SELECT COUNT(*) FROM users WHERE role != 'admin'")->fetchColumn();
$product_count = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
$order_count = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$revenue = $pdo->query("SELECT SUM(total_amount) FROM orders WHERE status = 'completed'")->fetchColumn() ?? 0;

// Fetch role distribution
$roles = $pdo->query("SELECT role, COUNT(*) as count FROM users WHERE role != 'admin' GROUP BY role")->fetchAll();
$role_labels = [];
$role_data = [];
foreach ($roles as $r) {
    $role_labels[] = ucfirst($r['role']);
    $role_data[] = $r['count'];
}

// Fetch monthly revenue for platform
$stmt = $pdo->query("SELECT DATE_FORMAT(created_at, '%b') as month, SUM(total_amount) as earnings FROM orders WHERE status = 'completed' GROUP BY MONTH(created_at) ORDER BY created_at ASC");
$monthly_data = $stmt->fetchAll();

$months = [];
$revenue_data = [];
foreach ($monthly_data as $row) {
    $months[] = $row['month'];
    $revenue_data[] = (float)$row['earnings'];
}

if (empty($months)) {
    $months = ["Empty"];
    $revenue_data = [0];
}
?>

<div class="d-flex">
    <?php include 'includes/sidebar.php'; ?>

    <div class="main-content w-100">
        <?php include 'includes/topbar.php'; ?>

        <div class="container-fluid p-0 animate-fade">
            <h3 class="fw-bold mb-4">System Analytics</h3>

            <!-- Overview Cards -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="stats-card bg-primary bg-gradient">
                        <h6 class="opacity-75">Total Users</h6>
                        <h3><?php echo $user_count; ?></h3>
                        <small>Active Marketplace</small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stats-card bg-success bg-gradient">
                        <h6 class="opacity-75">Products</h6>
                        <h3><?php echo $product_count; ?></h3>
                        <small>Listed items</small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stats-card bg-info bg-gradient">
                        <h6 class="opacity-75">Orders</h6>
                        <h3><?php echo $order_count; ?></h3>
                        <small>All transactions</small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stats-card bg-warning bg-gradient text-dark">
                        <h6 class="opacity-75">Total Revenue</h6>
                        <h3>K <?php echo number_format($revenue, 2); ?></h3>
                        <small>Completed Sales</small>
                    </div>
                </div>
            </div>

            <div class="row">
                <!-- Revenue Trend -->
                <div class="col-lg-8 mb-4">
                    <div class="card border-0 shadow-sm rounded-5">
                        <div class="card-header bg-white border-0 p-4">
                            <h5 class="fw-bold mb-0">Platform Revenue Trend</h5>
                        </div>
                        <div class="card-body p-4">
                            <div style="position: relative; height: 300px;">
                                <canvas id="systemRevenueChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- User Distribution -->
                <div class="col-lg-4 mb-4">
                    <div class="card border-0 shadow-sm rounded-5">
                        <div class="card-header bg-white border-0 p-4">
                            <h5 class="fw-bold mb-0">User Roles</h5>
                        </div>
                        <div class="card-body p-4">
                            <div style="position: relative; height: 300px;">
                                <canvas id="userDistChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // System Revenue Chart
    const ctxRevenue = document.getElementById('systemRevenueChart');
    if (ctxRevenue) {
        new Chart(ctxRevenue, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($months); ?>,
                datasets: [{
                    label: 'Revenue (K)',
                    data: <?php echo json_encode($revenue_data); ?>,
                    backgroundColor: '#e67e22',
                    borderRadius: 10
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.05)' } },
                    x: { grid: { display: false } }
                }
            }
        });
    }

    // User Distribution Chart
    const ctxUserDist = document.getElementById('userDistChart');
    if (ctxUserDist) {
        new Chart(ctxUserDist, {
            type: 'pie',
            data: {
                labels: <?php echo json_encode($role_labels); ?>,
                datasets: [{
                    data: <?php echo json_encode($role_data); ?>,
                    backgroundColor: ['#1e3a3a', '#e67e22', '#2ecc71']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom' } }
            }
        });
    }
});
</script>
</body>
</html>
