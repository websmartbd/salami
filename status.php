<?php
/**
 * SalamiPay - Toggle Salami Status (AJAX Endpoint)
 */
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';

header('Content-Type: application/json');

// Must be logged in
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid method']);
    exit();
}

$input     = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    echo json_encode(['success' => false, 'message' => 'Invalid JSON']);
    exit();
}

$salamiId  = intval($input['id'] ?? 0);
$newStatus = $input['status'] ?? '';

if ($salamiId <= 0 || !in_array($newStatus, ['pending', 'verified'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid data']);
    exit();
}

// Rate limiting: max 30 status changes per minute
$rateKey = 'status_rate_' . $_SESSION['user_id'];
if (!isset($_SESSION[$rateKey])) {
    $_SESSION[$rateKey] = ['count' => 0, 'window' => time()];
}
if (time() - $_SESSION[$rateKey]['window'] > 60) {
    $_SESSION[$rateKey] = ['count' => 0, 'window' => time()];
}
$_SESSION[$rateKey]['count']++;
if ($_SESSION[$rateKey]['count'] > 30) {
    echo json_encode(['success' => false, 'message' => 'Too many requests. Please wait.']);
    exit();
}

// Verify salami log ownership
$stmt = $pdo->prepare("SELECT id FROM salami_logs WHERE id = ? AND receiver_id = ?");
$stmt->execute([$salamiId, $_SESSION['user_id']]);

if (!$stmt->fetch()) {
    echo json_encode(['success' => false, 'message' => 'Not found or unauthorized']);
    exit();
}

// Update status (scoped with receiver_id for double safety)
$update = $pdo->prepare("UPDATE salami_logs SET status = ? WHERE id = ? AND receiver_id = ?");
$update->execute([$newStatus, $salamiId, $_SESSION['user_id']]);

echo json_encode(['success' => true, 'status' => $newStatus]);
