<?php
require_once __DIR__ . '/../includes/functions.php';

$token = $_GET['token'] ?? '';

if (!is_string($token) || !preg_match('/^[a-f0-9]{64}$/', $token)) {
    flash('danger', 'Invalid verification link.');
    redirect('auth/login.php');
}

$hash = hash('sha256', $token);

try {
    $conn->begin_transaction();

    // Lock this token row to prevent simultaneous reuse.
    $stmt = $conn->prepare(
        'SELECT id, user_id, expires_at, used_at
         FROM email_verifications
         WHERE token_hash = ?
         LIMIT 1
         FOR UPDATE'
    );
    $stmt->bind_param('s', $hash);
    $stmt->execute();

    $v = $stmt->get_result()->fetch_assoc();

    if (
        !$v ||
        $v['used_at'] !== null ||
        strtotime($v['expires_at']) < time()
    ) {
        $conn->rollback();

        flash(
            'danger',
            'This verification link is invalid, expired, or already used. Request a new one.'
        );
        redirect('auth/login.php');
    }

    // Verify the email only. Do not activate the account here.
    $stmt = $conn->prepare(
        'UPDATE users
         SET is_verified = 1, verified_at = NOW()
         WHERE id = ? AND is_verified = 0'
    );
    $stmt->bind_param('i', $v['user_id']);
    $stmt->execute();

    if ($stmt->affected_rows !== 1) {
        $conn->rollback();

        flash(
            'info',
            'This email may already be verified. Your account still requires admin approval.'
        );
        redirect('auth/login.php');
    }

    // Mark the token as used.
    $stmt = $conn->prepare(
        'UPDATE email_verifications
         SET used_at = NOW()
         WHERE id = ? AND used_at IS NULL'
    );
    $stmt->bind_param('i', $v['id']);
    $stmt->execute();

    if ($stmt->affected_rows !== 1) {
        throw new RuntimeException('Verification token could not be marked as used.');
    }

    $conn->commit();

    flash(
        'success',
        'Email verified successfully! Your account is now waiting for admin approval. You can log in after the admin activates your account.'
    );

} catch (Throwable $e) {
    try {
        $conn->rollback();
    } catch (Throwable $rollbackError) {
        // The transaction may already have ended.
    }

    error_log('Email verification failed: ' . $e->getMessage());

    flash('danger', 'Verification failed. Please try again.');
}

redirect('auth/login.php');