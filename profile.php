<?php
include 'includes/header.inc.php';
include 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();
?>

<div class="d-flex">
    <?php include 'includes/sidebar.php'; ?>

    <div class="main-content w-100">
        <?php include 'includes/topbar.php'; ?>

        <div class="container-fluid p-0 animate-fade">
            <h3 class="fw-bold mb-4">My Profile</h3>

            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show rounded-4 mb-4" role="alert">
                    Profile updated successfully!
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="row g-4">
                <div class="col-12 col-lg-4">
                    <div class="card border-0 shadow-sm rounded-5 text-center p-4 p-lg-5 h-100">
                        <div class="position-relative d-inline-block mx-auto mb-4">
                            <img src="assets/img/<?php echo $user['profile_image']; ?>" class="rounded-circle border border-5 border-white shadow" style="width: 150px; height: 150px; object-fit: cover;">
                            <button class="btn btn-primary btn-sm rounded-circle position-absolute bottom-0 end-0 p-2" onclick="document.getElementById('profile_upload').click()">
                                <i class="fas fa-camera"></i>
                            </button>
                        </div>
                        <h4 class="fw-bold mb-1"><?php echo $user['full_name']; ?></h4>
                        <p class="text-muted"><?php echo ucfirst($user['role']); ?></p>
                        <div class="badge bg-light text-dark rounded-pill px-3 py-2">Member since <?php echo date('M Y', strtotime($user['created_at'])); ?></div>
                        
                        <form action="api/users.php" method="POST" enctype="multipart/form-data" id="profile_image_form" class="d-none">
                            <input type="hidden" name="action" value="update_image">
                            <input type="file" name="profile_image" id="profile_upload" onchange="this.form.submit()">
                        </form>
                    </div>
                </div>

                <div class="col-12 col-lg-8">
                    <div class="card border-0 shadow-sm rounded-5 p-4 p-lg-5 h-100">
                        <h5 class="fw-bold mb-4">Personal Information</h5>
                        <form action="api/users.php" method="POST">
                            <input type="hidden" name="action" value="update_info">
                            <div class="row mb-3 g-3">
                                <div class="col-12 col-md-6">
                                    <label class="form-label">Full Name</label>
                                    <input type="text" name="full_name" class="form-control rounded-pill" value="<?php echo $user['full_name']; ?>" required>
                                </div>
                                <div class="col-12 col-md-6">
                                    <label class="form-label">Email Address</label>
                                    <input type="email" name="email" class="form-control rounded-pill" value="<?php echo $user['email']; ?>" required>
                                </div>
                            </div>
                            <div class="mb-4">
                                <label class="form-label">Role</label>
                                <input type="text" class="form-control rounded-pill bg-light" value="<?php echo ucfirst($user['role']); ?>" disabled>
                                <small class="text-muted">Contact admin to change your role.</small>
                            </div>
                            <button type="submit" class="btn btn-primary rounded-pill px-5 w-100 w-md-auto">Save Changes</button>
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
