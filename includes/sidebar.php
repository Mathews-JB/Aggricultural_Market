<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>
<div class="sidebar">
    <div class="sidebar-logo">
        <i class="fas fa-leaf"></i> AgriMarket
    </div>
    <ul class="sidebar-nav">
        <li class="nav-item">
            <a href="dashboard.php" class="nav-link <?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>">
                <i class="fas fa-th-large"></i> Dashboard
            </a>
        </li>
        <?php if ($_SESSION['role'] == 'admin'): ?>
        <li class="nav-item">
            <a href="admin_users.php" class="nav-link <?php echo $current_page == 'admin_users.php' ? 'active' : ''; ?>">
                <i class="fas fa-users-cog"></i> Manage Users
            </a>
        </li>
        <li class="nav-item">
            <a href="admin_products.php" class="nav-link <?php echo $current_page == 'admin_products.php' ? 'active' : ''; ?>">
                <i class="fas fa-tasks"></i> Moderation
            </a>
        </li>
        <li class="nav-item">
            <a href="admin_analytics.php" class="nav-link <?php echo $current_page == 'admin_analytics.php' ? 'active' : ''; ?>">
                <i class="fas fa-chart-pie"></i> System Analytics
            </a>
        </li>
        <?php endif; ?>

        <li class="nav-item">
            <a href="products.php" class="nav-link <?php echo $current_page == 'products.php' ? 'active' : ''; ?>">
                <i class="fas fa-box"></i> Products
            </a>
        </li>
        <?php if ($_SESSION['role'] == 'buyer'): ?>
        <li class="nav-item">
            <a href="cart.php" class="nav-link <?php echo $current_page == 'cart.php' ? 'active' : ''; ?>">
                <i class="fas fa-shopping-cart"></i> My Cart
            </a>
        </li>
        <li class="nav-item">
            <a href="wishlist.php" class="nav-link <?php echo $current_page == 'wishlist.php' ? 'active' : ''; ?>">
                <i class="fas fa-heart"></i> Wishlist
            </a>
        </li>
        <?php endif; ?>
        <li class="nav-item">
            <a href="orders.php" class="nav-link <?php echo $current_page == 'orders.php' ? 'active' : ''; ?>">
                <i class="fas fa-receipt"></i> Orders
            </a>
        </li>
        <?php if ($_SESSION['role'] == 'farmer'): ?>
        <li class="nav-item">
            <a href="reports.php" class="nav-link <?php echo $current_page == 'reports.php' ? 'active' : ''; ?>">
                <i class="fas fa-chart-line"></i> Sales Reports
            </a>
        </li>
        <?php endif; ?>
        <li class="nav-item">
            <a href="messages.php" class="nav-link <?php echo $current_page == 'messages.php' ? 'active' : ''; ?>">
                <i class="fas fa-envelope"></i> Messages
            </a>
        </li>
        <li class="nav-item">
            <a href="notifications.php" class="nav-link <?php echo $current_page == 'notifications.php' ? 'active' : ''; ?>">
                <i class="fas fa-bell"></i> Notifications
            </a>
        </li>
        <li class="nav-item mt-5">
            <a href="profile.php" class="nav-link <?php echo $current_page == 'profile.php' ? 'active' : ''; ?>">
                <i class="fas fa-user"></i> Profile
            </a>
        </li>
        <li class="nav-item">
            <a href="settings.php" class="nav-link <?php echo $current_page == 'settings.php' ? 'active' : ''; ?>">
                <i class="fas fa-cog"></i> Settings
            </a>
        </li>
        <li class="nav-item">
            <a href="logout.php" class="nav-link text-danger">
                <i class="fas fa-sign-out-alt"></i> Log Out
            </a>
        </li>
    </ul>
    
    <div class="sidebar-footer mt-auto p-4">
        <a href="#" class="nav-link">
            <i class="fas fa-question-circle"></i> Help
        </a>
    </div>
</div>
