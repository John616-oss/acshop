
<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/mail_config.php';

function send_verification_email(
    string $email,
    string $name,
    string $token
): bool {

    $verifyUrl = BASE_URL . '/auth/verify_email.php?token='
        . urlencode($token);

    $mail = new PHPMailer(true);

    try {
        // Configure these variables in Apache/PHP; never commit mail credentials.
        $host = MAIL_HOST;
    $username = MAIL_USERNAME;
    $password = MAIL_PASSWORD;
    $from = MAIL_FROM;
    $port = MAIL_PORT;
        if ($host === '' || $username === '' || $password === '' || $from === '') {
            error_log('Verification email not sent: SMTP environment is not configured.');
            return false;
        }

        $mail->isSMTP();
        $mail->Host = $host;
        $mail->SMTPAuth = true;
        $mail->Username = $username;
        $mail->Password = $password;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = $port;
        $mail->CharSet = 'UTF-8';
        $mail->setFrom($from, APP_NAME);

        $mail->addAddress($email, $name);

        $mail->isHTML(true);
        $mail->Subject = APP_NAME . ' - Verify your email';

        $safeName = htmlspecialchars(
            $name,
            ENT_QUOTES | ENT_SUBSTITUTE,
            'UTF-8'
        );

        $safeUrl = htmlspecialchars(
            $verifyUrl,
            ENT_QUOTES | ENT_SUBSTITUTE,
            'UTF-8'
        );

        $mail->Body = "
            <h2>Hello {$safeName}!</h2>
            <p>Please click the link below to verify your account.</p>
            <p><a href=\"{$safeUrl}\">Verify My Email</a></p>
            <p>This link expires in 30 minutes.</p>
            <p>If you did not create this account, ignore this email.</p>
        ";

        $mail->AltBody =
            "Hello {$name}!\n\n"
            . "Verify your account using this link:\n"
            . $verifyUrl
            . "\n\nThis link expires in 30 minutes.";

        $mail->send();

        return true;

    } catch (Exception $e) {
        // Log the error without exposing credentials to users
        error_log('Verification email failed: ' . $mail->ErrorInfo);

        return false;
    }
}

function send_admin_inactive_account_notice(
    string $userEmail,
    string $userName
): bool {

    if (!defined('ADMIN_EMAIL') || ADMIN_EMAIL === '') {
        error_log('Admin notification failed: ADMIN_EMAIL is not configured.');
        return false;
    }

    $adminUrl = BASE_URL . '/admin/users.php';

    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host = MAIL_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = MAIL_USERNAME;
        $mail->Password = MAIL_PASSWORD;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = MAIL_PORT;
        $mail->CharSet = 'UTF-8';

        $mail->setFrom(MAIL_FROM, APP_NAME);
        $mail->addAddress(ADMIN_EMAIL);

        $safeName = htmlspecialchars(
            $userName,
            ENT_QUOTES | ENT_SUBSTITUTE,
            'UTF-8'
        );

        $safeEmail = htmlspecialchars(
            $userEmail,
            ENT_QUOTES | ENT_SUBSTITUTE,
            'UTF-8'
        );

        $safeAdminUrl = htmlspecialchars(
            $adminUrl,
            ENT_QUOTES | ENT_SUBSTITUTE,
            'UTF-8'
        );

        $mail->isHTML(true);
        $mail->Subject = APP_NAME . ' - Inactive Account Request';

        $mail->Body = "
            <h2>Inactive Account Request</h2>
            <p>A user with an inactive account requested assistance.</p>
            <p><strong>Name:</strong> {$safeName}</p>
            <p><strong>Email:</strong> {$safeEmail}</p>
            <p>Please sign in to the admin panel and review this account.</p>
            <p><a href=\"{$safeAdminUrl}\">Open User Management</a></p>
            <p>This notification does not activate the account.</p>
        ";

        $mail->AltBody =
            "Inactive Account Request\n\n"
            . "Name: {$userName}\n"
            . "Email: {$userEmail}\n\n"
            . "Sign in to the admin panel to review this account:\n"
            . $adminUrl
            . "\n\nThis notification does not activate the account.";

        $mail->send();
        return true;

    } catch (Exception $e) {
        error_log(
            'Admin notification failed: ' . $mail->ErrorInfo
        );
        return false;
    }
}

