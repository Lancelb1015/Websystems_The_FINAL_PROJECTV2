<?php

function alphatech_log_sms(string $message): void
{
    $file = __DIR__ . '/_dev_sms.log';
    $line = '[' . date('Y-m-d H:i:s') . '] ' . $message . PHP_EOL;
    @file_put_contents($file, $line, FILE_APPEND);
}

function alphatech_send_sms(string $toNumber, string $message, ?string &$error = null): bool
{
    $sid   = getenv('TWILIO_ACCOUNT_SID') ?: '';
    $token = getenv('TWILIO_AUTH_TOKEN') ?: '';
    $from  = getenv('TWILIO_FROM_NUMBER') ?: '';

    if ($sid === '' || $token === '' || $from === '') {
        $error = 'SMS not configured.';
        alphatech_log_sms("TO={$toNumber} MSG={$message}");
        return getenv('ALPHATECH_DEV_SHOW_SMS_CODE') === '1';
    }

    $url = "https://api.twilio.com/2010-04-01/Accounts/{$sid}/Messages.json";
    $post = http_build_query([
        'From' => $from,
        'To'   => $toNumber,
        'Body' => $message,
    ]);

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
    curl_setopt($ch, CURLOPT_USERPWD, $sid . ':' . $token);
    curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
    $resp = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr = curl_error($ch);
    curl_close($ch);

    if ($resp === false) {
        $error = $curlErr ?: 'SMS send failed.';
        return false;
    }

    if ($code < 200 || $code >= 300) {
        $error = "SMS provider error (HTTP {$code}).";
        alphatech_log_sms("HTTP={$code} RESP={$resp}");
        return false;
    }

    return true;
}

