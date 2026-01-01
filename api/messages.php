<?php
// Prevent any HTML output/warnings from breaking JSON
error_reporting(E_ALL);
ini_set('display_errors', 0); 
ob_start(); // Start output buffering

include '../config.php';
session_start();

// Clear any accidental whitespace/output from includes
ob_clean();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$user_id = $_SESSION['user_id'];
$action = $_GET['action'] ?? '';

// AUTO-MIGRATION: Ensure public_key column exists
try {
    $pdo->query("SELECT public_key FROM users LIMIT 1");
} catch (Exception $e) {
    try {
        $pdo->query("ALTER TABLE users ADD COLUMN public_key TEXT NULL");
    } catch (Exception $e2) {
        // Silently fail if we can't alter table, but at least we tried
    }
}

if ($action == 'send') {
    $receiver_id = $_POST['receiver_id'];
    $message = filter_var($_POST['message'], FILTER_SANITIZE_STRING);

    try {
        $stmt = $pdo->prepare("INSERT INTO messages (sender_id, receiver_id, message) VALUES (?, ?, ?)");
        $stmt->execute([$user_id, $receiver_id, $message]);
        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        echo json_encode(['error' => $e->getMessage()]);
    }

} elseif ($action == 'fetch') {
    $other_id = $_GET['other_id'];
    try {
        $stmt = $pdo->prepare("SELECT * FROM messages 
                               WHERE (sender_id = ? AND receiver_id = ?) 
                               OR (sender_id = ? AND receiver_id = ?) 
                               ORDER BY created_at ASC");
        $stmt->execute([$user_id, $other_id, $other_id, $user_id]);
        $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Mark as read
        $stmt_read = $pdo->prepare("UPDATE messages SET is_read = TRUE WHERE receiver_id = ? AND sender_id = ?");
        $stmt_read->execute([$user_id, $other_id]);

        echo json_encode($messages);
    } catch (PDOException $e) {
        echo json_encode(['error' => $e->getMessage()]);
    }

} elseif ($action == 'list_contacts') {
    try {
        // 1. Get existing contacts (people you have messaged)
        $stmt = $pdo->prepare("SELECT DISTINCT u.id, u.full_name, u.profile_image, u.role, u.public_key 
                               FROM users u 
                               JOIN messages m ON (u.id = m.sender_id OR u.id = m.receiver_id)
                               WHERE (m.sender_id = ? OR m.receiver_id = ?) AND u.id != ?");
        $stmt->execute([$user_id, $user_id, $user_id]);
        $existing_contacts = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // SMART DISCOVERY: Suggest people based on roles
        $my_role = $_SESSION['role'] ?? '';
        $suggested_contacts = [];
        
        if ($my_role == 'buyer') {
            $stmt = $pdo->prepare("SELECT id, full_name, profile_image, role, public_key FROM users WHERE role = 'farmer' AND id != ? LIMIT 10");
        } elseif ($my_role == 'farmer') {
            $stmt = $pdo->prepare("SELECT id, full_name, profile_image, role, public_key FROM users WHERE role = 'buyer' AND id != ? LIMIT 10");
        } else {
            $stmt = $pdo->prepare("SELECT id, full_name, profile_image, role, public_key FROM users WHERE id != ? LIMIT 10");
        }
        $stmt->execute([$user_id]);
        $suggested_contacts = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // FALLBACK: If discovery yielded nothing (e.g. no farmers yet), show anyone else
        if (empty($suggested_contacts) && empty($existing_contacts)) {
            $stmt = $pdo->prepare("SELECT id, full_name, profile_image, role, public_key FROM users WHERE id != ? LIMIT 10");
            $stmt->execute([$user_id]);
            $suggested_contacts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        // Merge and ensure uniqueness by ID
        $all_contacts = $existing_contacts;
        $existing_ids = array_column($existing_contacts, 'id');

        foreach ($suggested_contacts as $s) {
            if (!in_array($s['id'], $existing_ids)) {
                $all_contacts[] = $s;
            }
        }

        // If a specific contact is requested via product page, ensure they are at the top
        if (isset($_GET['force_user_id'])) {
            $force_id = (int)$_GET['force_user_id'];
            $found_idx = -1;
            foreach ($all_contacts as $idx => $c) {
                if ($c['id'] == $force_id) { $found_idx = $idx; break; }
            }
            
            if ($found_idx !== -1) {
                $contact = $all_contacts[$found_idx];
                unset($all_contacts[$found_idx]);
                array_unshift($all_contacts, $contact);
            } else {
                $stmt = $pdo->prepare("SELECT id, full_name, profile_image, role, public_key FROM users WHERE id = ?");
                $stmt->execute([$force_id]);
                $force_contact = $stmt->fetch();
                if ($force_contact) array_unshift($all_contacts, $force_contact);
            }
        }

        echo json_encode(array_values($all_contacts));
    } catch (PDOException $e) {
        echo json_encode(['error' => $e->getMessage()]);
    }
}
?>
