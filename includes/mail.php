
<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/database.php';

function send_verification_email(
    string $email,
    string $name,
    string $token
): bool {

    $verifyUrl = BASE_URL . '/auth/verify_email.php?token='
        . urlencode($token);

    $mail = new PHPMailer(true);

    try {
        // Gmail SMTP settings
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;

        // PALITAN ng sarili mong Gmail address
        $mail->Username   = 'johncarloverzosa29@gmail.com';

        // GAMITIN ang Gmail App Password, hindi normal password
        $mail->Password   = 'pikt zjte mzaa vxsn';

        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        $mail->CharSet = 'UTF-8';

        // Sender must match your Gmail account
        $mail->setFrom(
            'johncarloverzosa29@gmail.com',
            APP_NAME
        );

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
