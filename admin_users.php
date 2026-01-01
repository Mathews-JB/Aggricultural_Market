<?php
include 'includes/header.inc.php';
include 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: dashboard.php");
    exit();
}

// Fetch all users
$stmt = $pdo->query("SELECT * FROM users WHERE role != 'admin' ORDER BY created_at DESC");
$users = $stmt->fetchAll();

// Handle status updates
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = $_GET['id'];
    $new_status = ($_GET['action'] == 'suspend') ? 'suspended' : 'active';
    if ($_GET['action'] == 'delete') {
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$id]);
    } else {
        $stmt = $pdo->prepare("UPDATE users SET status = ? WHERE id = ?");
        $stmt->execute([$new_status, $id]);
    }
    header("Location: admin_users.php?success=1");
    exit();
}
?>

<div class="d-flex">
    <?php include 'includes/sidebar.php'; ?>

    <div class="main-content w-100">
        <?php include 'includes/topbar.php'; ?>

        <div class="container-fluid p-0 animate-fade">
            <h3 class="fw-bold mb-4">User Management</h3>

            <div class="stats-card">
                <div class="table-responsive">
                    <table class="table table-dark table-hover mb-0" style="background: transparent;">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Joined</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                                <tr style="border-bottom: 1px solid rgba(255,255,255,0.1);">
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <img src="assets/img/<?php echo $user['profile_image']; ?>" class="rounded-circle me-3" style="width: 40px; height: 40px; object-fit: cover;">
                                            <div class="fw-bold"><?php echo $user['full_name']; ?></div>
                                        </div>
                                    </td>
                                    <td><?php echo $user['email']; ?></td>
                                    <td><span class="badge bg-light text-dark rounded-pill"><?php echo ucfirst($user['role']); ?></span></td>
                                    <td>
                                        <span class="badge bg-<?php echo $user['status'] == 'active' ? 'success' : 'danger'; ?> rounded-pill">
                                            <?php echo ucfirst($user['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                                    <td>
                                        <div class="btn-group">
                                            <a href="admin_users.php?action=<?php echo $user['status'] == 'active' ? 'suspend' : 'activate'; ?>&id=<?php echo $user['id']; ?>" 
                                               class="btn btn-sm btn-outline-<?php echo $user['status'] == 'active' ? 'warning' : 'success'; ?> rounded-pill px-3 me-2">
                                                <?php echo $user['status'] == 'active' ? 'Suspend' : 'Activate'; ?>
                                            </a>
                                            <a href="admin_users.php?action=delete&id=<?php echo $user['id']; ?>" 
                                               class="btn btn-sm btn-outline-danger rounded-pill px-3" 
                                               onclick="return confirm('Are you sure?')">
                                                Delete
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
