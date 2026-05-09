<?php

require_once __DIR__ . '/auth.php';

function alphatech_log_dev(string $message): void
{
    $file = __DIR__ . '/_dev_mail.log';
    $line = '[' . date('Y-m-d H:i:s') . '] ' . $message . PHP_EOL;
    @file_put_contents($file, $line, FILE_APPEND);
}

function alphatech_send_mail(string $to, string $subject, string $html, ?string &$error = null): bool
{
    $fromEmail = getenv('ALPHATECH_FROM_EMAIL') ?: 'no-reply@localhost';
    $fromName  = getenv('ALPHATECH_FROM_NAME') ?: 'AlphaTech';

    $headers = [];
    $headers[] = 'MIME-Version: 1.0';
    $headers[] = 'Content-type: text/html; charset=UTF-8';
    $headers[] = 'From: ' . $fromName . ' <' . $fromEmail . '>';

    $ok = @mail($to, $subject, $html, implode("\r\n", $headers));
    if ($ok) return true;

    $error = 'mail() failed. Configure XAMPP sendmail or SMTP.';
    alphatech_log_dev("TO={$to} SUBJECT={$subject}\n{$html}\n---");
    return false;
}

function alphatech_send_password_reset_email(string $toEmail, string $resetLink): void
{
    $subject = 'Reset your AlphaTech password';
    $safeLink = htmlspecialchars($resetLink, ENT_QUOTES, 'UTF-8');
    $html = <<<HTML
<p>We received a request to reset your password.</p>
<p>Click this link to reset it (valid for 30 minutes):</p>
<p><a href="{$safeLink}">Reset Password</a></p>
<p>If you did not request this, you can ignore this email.</p>
HTML;

    $err = null;
    $sent = alphatech_send_mail($toEmail, $subject, $html, $err);
    if (!$sent && (getenv('ALPHATECH_DEV_SHOW_RESET_LINK') === '1')) {
        // In local/dev, allow continuing without real email.
        alphatech_log_dev("RESET_LINK={$resetLink}");
    }
}

