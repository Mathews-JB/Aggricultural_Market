<?php
// Initialize error log in a way that doesn't cause issues
$log_file = '../app_error.log';
function log_debug($msg) {
    global $log_file;
    @file_put_contents($log_file, date('[Y-m-d H:i:s] ') . $msg . PHP_EOL, FILE_APPEND);
}

include '../config.php';
session_start();

// Enable all error reporting for now to see what's happening
error_reporting(E_ALL);
ini_set('display_errors', 1);

// CRITICAL CHECK: If POST is empty but content-length is high, post_max_size was exceeded.
if ($_SERVER['REQUEST_METHOD'] == 'POST' && empty($_POST) && $_SERVER['CONTENT_LENGTH'] > 0) {
    $msg = "ERROR: The uploaded file is too large. Your server limit is likely " . ini_get('post_max_size') . ". Please try a smaller file.";
    log_debug($msg);
    die($msg);
}

log_debug("Request started. Action: " . ($_POST['action'] ?? $_GET['action'] ?? 'none'));

if (!isset($_SESSION['user_id'])) {
    log_debug("Unauthorized access.");
    die("Unauthorized access.");
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

if ($action == 'create' && $role == 'farmer') {
    $name = htmlspecialchars($_POST['name'] ?? '', ENT_QUOTES, 'UTF-8');
    log_debug("Creating product: $name");
    
    $category_id = (int)($_POST['category_id'] ?? 0);
    $price = (float)($_POST['price'] ?? 0);
    $quantity = (int)($_POST['quantity'] ?? 1);
    $unit = htmlspecialchars($_POST['unit'] ?? '', ENT_QUOTES, 'UTF-8');
    $description = htmlspecialchars($_POST['description'] ?? '', ENT_QUOTES, 'UTF-8');
    $is_auction = isset($_POST['is_auction']) ? 1 : 0;
    $auction_duration = (int)($_POST['auction_duration'] ?? 0);
    $location_lat = ($_POST['location_lat'] ?? '') !== '' ? (float)$_POST['location_lat'] : null;
    $location_lng = ($_POST['location_lng'] ?? '') !== '' ? (float)$_POST['location_lng'] : null;

    $auction_end = null;
    if ($is_auction && $auction_duration > 0) {
        $auction_end = date('Y-m-d H:i:s', strtotime("+$auction_duration hours"));
    }

    log_debug("Processing files...");
    
    // Image Upload
    $image_name = '';
    if (isset($_FILES['image'])) {
        log_debug("Image found. Error code: " . $_FILES['image']['error']);
        if ($_FILES['image']['error'] == 0) {
            $target_dir = "../uploads/products/";
            if (!file_exists($target_dir)) {
                @mkdir($target_dir, 0777, true);
            }
            $extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $image_name = time() . "_" . uniqid() . "." . $extension;
            $target_file = $target_dir . $image_name;

            if (!move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
                log_debug("Failed to move image.");
                die("Error: Could not move uploaded image. Check folder permissions.");
            }
        } elseif ($_FILES['image']['error'] != 4) {
            log_debug("Image upload error: " . $_FILES['image']['error']);
            die("Error uploading image. Code: " . $_FILES['image']['error']);
        }
    }

    // Video Upload
    $video_name = '';
    if (isset($_FILES['video'])) {
        log_debug("Video found. Error code: " . $_FILES['video']['error']);
        if ($_FILES['video']['error'] == 0) {
            $target_dir = "../uploads/videos/";
            if (!file_exists($target_dir)) {
                @mkdir($target_dir, 0777, true);
            }
            $extension = pathinfo($_FILES['video']['name'], PATHINFO_EXTENSION);
            $video_name = time() . "_" . uniqid() . "." . $extension;
            $target_file = $target_dir . $video_name;

            if (!move_uploaded_file($_FILES['video']['tmp_name'], $target_file)) {
                log_debug("Failed to move video.");
                die("Error: Could not move uploaded video. Check folder permissions.");
            }
        } elseif ($_FILES['video']['error'] != 4) {
            log_debug("Video upload error: " . $_FILES['video']['error']);
            die("Error uploading video. Code: " . $_FILES['video']['error']);
        }
    }

    try {
        log_debug("Inserting into DB...");
        $stmt = $pdo->prepare("INSERT INTO products (farmer_id, category_id, name, description, price, starting_bid, current_bid, quantity, unit, image, video_url, is_auction, auction_end, location_lat, location_lng) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        if ($stmt->execute([$user_id, $category_id, $name, $description, $price, $price, $price, $quantity, $unit, $image_name, $video_name, $is_auction, $auction_end, $location_lat, $location_lng])) {
            log_debug("Success!");
            header("Location: ../products.php?success=created");
            exit();
        }
    } catch (PDOException $e) {
        log_debug("DB Error: " . $e->getMessage());
        die("Database Error: " . $e->getMessage());
    }

} elseif ($action == 'update' && $role == 'farmer') {
    $product_id = (int)$_POST['product_id'];
    log_debug("Updating product: $product_id");
    
    $name = htmlspecialchars($_POST['name'] ?? '', ENT_QUOTES, 'UTF-8');
    $category_id = (int)$_POST['category_id'];
    $price = (float)$_POST['price'];
    $quantity = (int)$_POST['quantity'];
    $unit = htmlspecialchars($_POST['unit'] ?? '', ENT_QUOTES, 'UTF-8');
    $description = htmlspecialchars($_POST['description'] ?? '', ENT_QUOTES, 'UTF-8');
    $is_auction = isset($_POST['is_auction']) ? 1 : 0;
    $auction_duration = (int)($_POST['auction_duration'] ?? 0);
    $location_lat = ($_POST['location_lat'] ?? '') !== '' ? (float)$_POST['location_lat'] : null;
    $location_lng = ($_POST['location_lng'] ?? '') !== '' ? (float)$_POST['location_lng'] : null;

    try {
        $stmt = $pdo->prepare("SELECT image, video_url FROM products WHERE id = ? AND farmer_id = ?");
        $stmt->execute([$product_id, $user_id]);
        $product = $stmt->fetch();

        if ($product) {
            $image_name = $product['image'];
            if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
                $target_dir = "../uploads/products/";
                $extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                $new_image_name = time() . "_" . uniqid() . "." . $extension;
                if (move_uploaded_file($_FILES['image']['tmp_name'], $target_dir . $new_image_name)) {
                    if ($image_name && file_exists($target_dir . $image_name)) { @unlink($target_dir . $image_name); }
                    $image_name = $new_image_name;
                }
            }

            $video_name = $product['video_url'];
            if (isset($_FILES['video']) && $_FILES['video']['error'] == 0) {
                $target_dir = "../uploads/videos/";
                $extension = pathinfo($_FILES['video']['name'], PATHINFO_EXTENSION);
                $new_video_name = time() . "_" . uniqid() . "." . $extension;
                if (move_uploaded_file($_FILES['video']['tmp_name'], $target_dir . $new_video_name)) {
                    if ($video_name && file_exists($target_dir . $video_name)) { @unlink($target_dir . $video_name); }
                    $video_name = $new_video_name;
                }
            }

            $stmt = $pdo->prepare("UPDATE products SET name = ?, category_id = ?, price = ?, quantity = ?, unit = ?, description = ?, image = ?, video_url = ?, is_auction = ?, location_lat = ?, location_lng = ? WHERE id = ?");
            if ($stmt->execute([$name, $category_id, $price, $quantity, $unit, $description, $image_name, $video_name, $is_auction, $location_lat, $location_lng, $product_id])) {
                header("Location: ../products.php?success=updated");
                exit();
            }
        }
    } catch (PDOException $e) {
        log_debug("Update Error: " . $e->getMessage());
        die("Update Error: " . $e->getMessage());
    }

} elseif ($action == 'delete' && $role == 'farmer') {
    $product_id = (int)$_GET['id'];
    try {
        $stmt = $pdo->prepare("SELECT image FROM products WHERE id = ? AND farmer_id = ?");
        $stmt->execute([$product_id, $user_id]);
        $product = $stmt->fetch();
        if ($product) {
            if ($product['image'] && file_exists("../uploads/products/" . $product['image'])) { @unlink("../uploads/products/" . $product['image']); }
            $pdo->prepare("DELETE FROM products WHERE id = ?")->execute([$product_id]);
            header("Location: ../products.php?success=deleted");
            exit();
        }
    } catch (PDOException $e) { log_debug("Delete Error: " . $e->getMessage()); }
} else {
    header("Location: ../products.php");
}
?>
