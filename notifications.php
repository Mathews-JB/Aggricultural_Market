<?php
include 'includes/header.inc.php';
include 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Mark all as read if requested
if (isset($_GET['mark_read'])) {
    $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
    $stmt->execute([$user_id]);
    header("Location: notifications.php");
    exit();
}

// Fetch all notifications
$stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$user_id]);
$notifications = $stmt->fetchAll();
?>

<div class="d-flex">
    <?php include 'includes/sidebar.php'; ?>

    <div class="main-content w-100">
        <?php include 'includes/topbar.php'; ?>

        <div class="container-fluid p-0 animate-fade">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h3 class="fw-bold m-0">Recent Notifications</h3>
                <?php if (!empty($notifications)): ?>
                    <a href="notifications.php?mark_read=1" class="btn btn-outline-primary btn-sm rounded-pill px-3">Mark all as read</a>
                <?php endif; ?>
            </div>

            <div class="row">
                <div class="col-lg-10 col-xl-8">
                    <?php if (empty($notifications)): ?>
                        <div class="card border-0 shadow-sm rounded-4 p-5 text-center">
                            <i class="fas fa-bell-slash fa-4x text-muted mb-3"></i>
                            <h5 class="text-muted">No notifications yet.</h5>
                            <p class="text-muted small">We'll alert you here when something important happens.</p>
                        </div>
                    <?php else: ?>
                        <div class="list-group list-group-flush shadow-sm rounded-4 overflow-hidden border-0">
                            <?php foreach ($notifications as $notif): ?>
                                <div class="list-group-item list-group-item-action border-0 p-4 mb-2 rounded-4 <?php echo !$notif['is_read'] ? 'bg-light border-start border-4 border-primary' : ''; ?>" style="transition: all 0.3s ease;">
                                    <div class="d-flex align-items-start">
                                        <div class="flex-shrink-0 me-4">
                                            <?php 
                                                $bg = 'primary';
                                                $icon = 'bell';
                                                if ($notif['type'] == 'order') { $bg = 'success'; $icon = 'shopping-bag'; }
                                                if ($notif['type'] == 'bid') { $bg = 'danger'; $icon = 'gavel'; }
                                                if ($notif['type'] == 'message') { $bg = 'info'; $icon = 'envelope'; }
                                            ?>
                                            <div class="bg-<?php echo $bg; ?> bg-opacity-10 text-<?php echo $bg; ?> rounded-circle p-3 shadow-sm">
                                                <i class="fas fa-<?php echo $icon; ?> fa-lg"></i>
                                            </div>
                                        </div>
                                        <div class="flex-grow-1">
                                            <div class="d-flex justify-content-between align-items-center mb-1">
                                                <h6 class="fw-bold m-0"><?php echo htmlspecialchars($notif['title']); ?></h6>
                                                <small class="text-muted"><?php echo date('M d, Y • H:i', strtotime($notif['created_at'])); ?></small>
                                            </div>
                                            <p class="text-muted mb-0"><?php echo htmlspecialchars($notif['message']); ?></p>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
