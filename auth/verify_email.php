<?php
require_once __DIR__ . '/../includes/functions.php';
$token = $_GET['token'] ?? '';
if (!is_string($token) || !preg_match('/^[a-f0-9]{64}$/', $token)) {
    flash('danger', 'Invalid verification link.'); redirect('auth/login.php');
}
$hash = hash('sha256', $token);
$stmt = $conn->prepare('SELECT id,user_id,expires_at,used_at FROM email_verifications WHERE token_hash=? LIMIT 1');
$stmt->bind_param('s', $hash); $stmt->execute(); $v = $stmt->get_result()->fetch_assoc();
if (!$v || $v['used_at'] !== null || strtotime($v['expires_at']) < time()) {
    flash('danger', 'This verification link is invalid, expired, or already used. Request a new one.'); redirect('auth/login.php');
}
$conn->begin_transaction();
try {
    $stmt = $conn->prepare('UPDATE users SET is_verified=1, verified_at=NOW() WHERE id=?');
    $stmt->bind_param('i', $v['user_id']); $stmt->execute();
    $stmt = $conn->prepare('UPDATE email_verifications SET used_at=NOW() WHERE id=? AND used_at IS NULL');
    $stmt->bind_param('i', $v['id']); $stmt->execute();
    $conn->commit();
    flash('success', 'Email verified successfully. You can now log in.');
} catch(Throwable $e) { $conn->rollback(); flash('danger', 'Verification failed. Please try again.'); }
redirect('auth/login.php');
