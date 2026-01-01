<?php
include '../config.php';
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$user_id = $_SESSION['user_id'];
$action = $_POST['action'] ?? $_GET['action'] ?? '';

// AUTO-MIGRATION: Ensure calls table exists
try {
    $pdo->query("SELECT 1 FROM calls LIMIT 1");
} catch (Exception $e) {
    try {
        $pdo->query("CREATE TABLE calls (
            id INT AUTO_INCREMENT PRIMARY KEY,
            caller_id INT NOT NULL,
            receiver_id INT NOT NULL,
            status ENUM('ringing', 'active', 'ended', 'rejected', 'missed') DEFAULT 'ringing',
            offer TEXT,
            answer TEXT,
            caller_candidates TEXT,
            receiver_candidates TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (caller_id) REFERENCES users(id),
            FOREIGN KEY (receiver_id) REFERENCES users(id)
        )");
    } catch (Exception $e2) {
        // Silently fail or log
    }
}

if ($action == 'initiate') {
    $receiver_id = $_POST['receiver_id'];
    $offer = $_POST['offer'];
    
    // Clear any old active/ringing calls for this pair
    $pdo->prepare("DELETE FROM calls WHERE (caller_id = ? AND receiver_id = ?) OR (caller_id = ? AND receiver_id = ?)")
        ->execute([$user_id, $receiver_id, $receiver_id, $user_id]);

    $stmt = $pdo->prepare("INSERT INTO calls (caller_id, receiver_id, offer, status) VALUES (?, ?, ?, 'ringing')");
    $stmt->execute([$user_id, $receiver_id, $offer]);
    echo json_encode(['success' => true, 'call_id' => $pdo->lastInsertId()]);

} elseif ($action == 'check_incoming') {
    $stmt = $pdo->prepare("SELECT c.*, u.full_name, u.profile_image FROM calls c 
                           JOIN users u ON c.caller_id = u.id 
                           WHERE c.receiver_id = ? AND c.status = 'ringing' 
                           ORDER BY c.created_at DESC LIMIT 1");
    $stmt->execute([$user_id]);
    echo json_encode($stmt->fetch(PDO::FETCH_ASSOC) ?: ['ringing' => false]);

} elseif ($action == 'accept') {
    $call_id = $_POST['call_id'];
    $answer = $_POST['answer'];
    $stmt = $pdo->prepare("UPDATE calls SET answer = ?, status = 'active' WHERE id = ? AND receiver_id = ?");
    $stmt->execute([$answer, $call_id, $user_id]);
    echo json_encode(['success' => true]);

} elseif ($action == 'reject') {
    $call_id = $_POST['call_id'];
    $stmt = $pdo->prepare("UPDATE calls SET status = 'rejected' WHERE id = ? AND receiver_id = ?");
    $stmt->execute([$call_id, $user_id]);
    echo json_encode(['success' => true]);

} elseif ($action == 'end') {
    $call_id = $_POST['call_id'];
    $stmt = $pdo->prepare("UPDATE calls SET status = 'ended' WHERE id = ? AND (caller_id = ? OR receiver_id = ?)");
    $stmt->execute([$call_id, $user_id, $user_id]);
    echo json_encode(['success' => true]);

} elseif ($action == 'get_status') {
    $call_id = $_GET['call_id'];
    $stmt = $pdo->prepare("SELECT * FROM calls WHERE id = ?");
    $stmt->execute([$call_id]);
    echo json_encode($stmt->fetch(PDO::FETCH_ASSOC));

} elseif ($action == 'add_candidate') {
    $call_id = $_POST['call_id'];
    $candidate = $_POST['candidate'];
    $type = $_POST['type']; // 'caller' or 'receiver'
    
    $column = ($type == 'caller') ? 'caller_candidates' : 'receiver_candidates';
    
    // Get current candidates
    $stmt = $pdo->prepare("SELECT $column FROM calls WHERE id = ?");
    $stmt->execute([$call_id]);
    $current = $stmt->fetchColumn();
    $candidates = $current ? json_decode($current, true) : [];
    $candidates[] = json_decode($candidate, true);
    
    $stmt = $pdo->prepare("UPDATE calls SET $column = ? WHERE id = ?");
    $stmt->execute([json_encode($candidates), $call_id]);
    echo json_encode(['success' => true]);
}
