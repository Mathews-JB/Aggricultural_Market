<?php
include 'includes/header.inc.php';
include 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}
?>

<div class="d-flex">
    <?php include 'includes/sidebar.php'; ?>

    <div class="main-content w-100">
        <?php include 'includes/topbar.php'; ?>

        <div class="container-fluid p-0 animate-fade">
            <h3 class="fw-bold mb-4">Account Settings</h3>

            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show rounded-4 mb-4" role="alert">
                    Password changed successfully!
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="row">
                <div class="col-lg-6">
                    <div class="card border-0 shadow-sm rounded-5 p-5">
                        <h5 class="fw-bold mb-4"><i class="fas fa-lock me-2 text-primary"></i> Change Password</h5>
                        <form action="api/users.php" method="POST">
                            <input type="hidden" name="action" value="update_password">
                            <div class="mb-3">
                                <label class="form-label">Current Password</label>
                                <input type="password" name="current_password" class="form-control rounded-pill" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">New Password</label>
                                <input type="password" name="new_password" class="form-control rounded-pill" required>
                            </div>
                            <div class="mb-4">
                                <label class="form-label">Confirm New Password</label>
                                <input type="password" name="confirm_password" class="form-control rounded-pill" required>
                            </div>
                            <button type="submit" class="btn btn-primary rounded-pill px-5">Update Password</button>
                        </form>
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="card border-0 shadow-sm rounded-5 p-5 bg-white">
                        <h5 class="fw-bold mb-4"><i class="fas fa-bell me-2 text-warning"></i> Notifications</h5>
                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" id="emailNotif" checked>
                            <label class="form-check-label" for="emailNotif">Email notifications for new orders</label>
                        </div>
                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" id="msgNotif" checked>
                            <label class="form-check-label" for="msgNotif">Real-time message alerts</label>
                        </div>
                        <div class="form-check form-switch mb-4">
                            <input class="form-check-input" type="checkbox" id="promoNotif">
                            <label class="form-check-label" for="promoNotif">Exclusive member offers & promos</label>
                        </div>
                        <hr>
                        <h5 class="fw-bold mb-4 text-danger"><i class="fas fa-exclamation-triangle me-2"></i> Danger Zone</h5>
                        <p class="text-muted small">Once you delete your account, there is no going back. Please be certain.</p>
                        <button class="btn btn-outline-danger rounded-pill px-4" onclick="alert('Please contact support to delete your account.')">Delete Account</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
