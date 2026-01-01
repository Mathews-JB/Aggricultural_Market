<?php
// Fetch unread notifications for the current user
$notif_stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
$notif_stmt->execute([$_SESSION['user_id']]);
$notifications = $notif_stmt->fetchAll();

$notif_count_stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
$notif_count_stmt->execute([$_SESSION['user_id']]);
$unread_count = $notif_count_stmt->fetchColumn();
?>
<div class="topbar">
    <div class="welcome-msg">
        <h2>Welcome Back, <?php echo $_SESSION['full_name'] ?? 'User'; ?>!</h2>
        <p>Exclusive Agricultural Deals Await!</p>
    </div>
    <div class="topbar-actions">
        <form action="products.php" method="GET" class="d-none d-md-flex align-items-center me-2">
            <div class="input-group search-bar-top">
                <input type="text" name="search" class="form-control border-0 bg-light rounded-start-pill ps-4" placeholder="Search markets..." style="width: 250px;">
                <button class="btn btn-light border-0 rounded-end-pill pe-3" type="submit">
                    <i class="fas fa-search text-muted"></i>
                </button>
            </div>
        </form>

        <div class="icon-btn d-md-none" onclick="window.location.href='products.php'">
            <i class="fas fa-search"></i>
        </div>

        <!-- Theme Palette Dropdown -->
        <div class="dropdown">
            <div class="icon-btn" data-bs-toggle="dropdown" title="Change Theme">
                <i class="fas fa-palette"></i>
            </div>
            <ul class="dropdown-menu dropdown-menu-end p-2 shadow border-0 rounded-4 theme-palette-menu" style="min-width: 200px;">
                <li><h6 class="dropdown-header fw-bold">Select Theme</h6></li>
                <li>
                    <button class="dropdown-item rounded-3 d-flex align-items-center gap-2 mb-1" onclick="setTheme('green')">
                        <div class="theme-preview" style="background: #1e3a3a; width: 20px; height: 20px; border-radius: 4px;"></div>
                        <span>Green Nature</span>
                    </button>
                </li>
                <li>
                    <button class="dropdown-item rounded-3 d-flex align-items-center gap-2 mb-1" onclick="setTheme('blue')">
                        <div class="theme-preview" style="background: #3b82f6; width: 20px; height: 20px; border-radius: 4px;"></div>
                        <span>Midnight Ocean</span>
                    </button>
                </li>
                <li>
                    <button class="dropdown-item rounded-3 d-flex align-items-center gap-2 mb-1" onclick="setTheme('purple')">
                        <div class="theme-preview" style="background: #9333ea; width: 20px; height: 20px; border-radius: 4px;"></div>
                        <span>Royal Plum</span>
                    </button>
                </li>
                <li>
                    <button class="dropdown-item rounded-3 d-flex align-items-center gap-2 mb-1" onclick="setTheme('sunset')">
                        <div class="theme-preview" style="background: #ea580c; width: 20px; height: 20px; border-radius: 4px;"></div>
                        <span>Sunset Glow</span>
                    </button>
                </li>
                <li>
                    <button class="dropdown-item rounded-3 d-flex align-items-center gap-2 mb-1" onclick="setTheme('forest')">
                        <div class="theme-preview" style="background: #065f46; width: 20px; height: 20px; border-radius: 4px;"></div>
                        <span>Forest Green</span>
                    </button>
                </li>
                <li>
                    <button class="dropdown-item rounded-3 d-flex align-items-center gap-2 mb-1" onclick="setTheme('cyber')">
                        <div class="theme-preview" style="background: #f472b6; width: 20px; height: 20px; border-radius: 4px;"></div>
                        <span>Cyberpunk Neon</span>
                    </button>
                </li>
                <li>
                    <button class="dropdown-item rounded-3 d-flex align-items-center gap-2" onclick="setTheme('dark')">
                        <div class="theme-preview" style="background: #1a1a1a; width: 20px; height: 20px; border-radius: 4px;"></div>
                        <span>Charcoal Night</span>
                    </button>
                </li>
            </ul>
        </div>
        
        <?php if ($_SESSION['role'] == 'buyer'): ?>
            <?php $cart_count = 0; if(isset($_SESSION['cart'])) { foreach($_SESSION['cart'] as $qty) { $cart_count += $qty; } } ?>
            <a href="cart.php" class="icon-btn position-relative text-decoration-none text-dark">
                <i class="fas fa-shopping-cart"></i>
                <?php if ($cart_count > 0): ?>
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-success" style="font-size: 0.6rem;"><?php echo $cart_count; ?></span>
                <?php endif; ?>
            </a>
        <?php endif; ?>

        <!-- Notifications Dropdown -->
        <div class="dropdown">
            <div class="icon-btn position-relative" data-bs-toggle="dropdown">
                <i class="fas fa-bell"></i>
                <?php if ($unread_count > 0): ?>
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size: 0.6rem;"><?php echo $unread_count; ?></span>
                <?php endif; ?>
            </div>
            <ul class="dropdown-menu dropdown-menu-end p-2 shadow border-0 rounded-4" style="width: 320px;">
                <li><h6 class="dropdown-header fw-bold">Notifications</h6></li>
                <?php if (empty($notifications)): ?>
                    <li><div class="dropdown-item text-center py-3 text-muted">No notifications yet.</div></li>
                <?php else: ?>
                    <?php foreach ($notifications as $notif): ?>
                        <li>
                            <a class="dropdown-item rounded-3 mb-1 p-3 <?php echo !$notif['is_read'] ? 'bg-light' : ''; ?>" href="notifications.php">
                                <div class="d-flex align-items-center">
                                    <div class="flex-shrink-0 me-3">
                                        <?php 
                                            $icon = 'bell text-primary';
                                            if ($notif['type'] == 'order') $icon = 'shopping-bag text-success';
                                            if ($notif['type'] == 'bid') $icon = 'gavel text-danger';
                                            if ($notif['type'] == 'message') $icon = 'envelope text-info';
                                        ?>
                                        <div class="bg-white rounded-circle p-2 shadow-sm">
                                            <i class="fas fa-<?php echo $icon; ?>"></i>
                                        </div>
                                    </div>
                                    <div>
                                        <small class="fw-bold d-block"><?php echo htmlspecialchars($notif['title']); ?></small>
                                        <span class="text-muted d-block text-truncate" style="font-size: 0.75rem; max-width: 200px;"><?php echo htmlspecialchars($notif['message']); ?></span>
                                        <small class="text-muted" style="font-size: 0.65rem;"><?php echo date('M d, H:i', strtotime($notif['created_at'])); ?></small>
                                    </div>
                                </div>
                            </a>
                        </li>
                    <?php endforeach; ?>
                <?php endif; ?>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item text-center text-primary fw-bold" href="notifications.php">View All</a></li>
            </ul>
        </div>

        <!-- User Dropdown -->
        <div class="dropdown">
            <div class="profile-card" data-bs-toggle="dropdown">
                <img src="assets/img/<?php echo $_SESSION['profile_image'] ?? 'default_profile.png'; ?>" alt="Profile" class="profile-img">
                <div class="d-none d-md-flex align-items-center">
                    <span class="me-1"><?php echo $_SESSION['full_name'] ?? 'Guest'; ?></span>
                    <?php if (isset($_SESSION['membership_type']) && $_SESSION['membership_type'] == 'vvip'): ?>
                        <span class="vvip-tag">VVIP</span>
                    <?php endif; ?>
                </div>
                <i class="fas fa-chevron-down ms-2"></i>
            </div>
            <ul class="dropdown-menu dropdown-menu-end">
                <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user me-2"></i> Profile</a></li>
                <li><a class="dropdown-item" href="settings.php"><i class="fas fa-cog me-2"></i> Settings</a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item text-danger" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i> Logout</a></li>
            </ul>
        </div>

        <!-- Sidebar Toggle -->
        <div class="icon-btn d-lg-none" id="sidebarToggle">
            <i class="fas fa-bars"></i>
        </div>
    </div>
</div>
<!-- Mobile Sidebar Overlay -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>
