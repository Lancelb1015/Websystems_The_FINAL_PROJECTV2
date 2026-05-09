<?php

function alphatech_cookie_options(int $maxAgeSeconds): array
{
    $isHttps = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    return [
        'expires'  => time() + $maxAgeSeconds,
        'path'     => '/',
        'secure'   => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax',
    ];
}

function alphatech_base_url(): string
{
    $isHttps = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    $scheme = $isHttps ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $dir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
    return $scheme . '://' . $host . ($dir === '' ? '' : $dir);
}

function alphatech_b64url_encode(string $bytes): string
{
    return rtrim(strtr(base64_encode($bytes), '+/', '-_'), '=');
}

function alphatech_b64url_decode(string $str): string|false
{
    $b64 = strtr($str, '-_', '+/');
    $pad = strlen($b64) % 4;
    if ($pad) $b64 .= str_repeat('=', 4 - $pad);
    return base64_decode($b64, true);
}

function alphatech_user_name_column(mysqli $conn): ?string
{
    $res = $conn->query("SHOW COLUMNS FROM users LIKE 'fullname'");
    if ($res && $res->num_rows > 0) return 'fullname';
    $res = $conn->query("SHOW COLUMNS FROM users LIKE 'name'");
    if ($res && $res->num_rows > 0) return 'name';
    return null;
}

function alphatech_set_remember_cookie(mysqli $conn, int $userId, int $days = 30): bool
{
    $selector = alphatech_b64url_encode(random_bytes(18)); // 24 chars
    $validatorBytes = random_bytes(32);
    $validator = alphatech_b64url_encode($validatorBytes);
    $hash = hash('sha256', $validatorBytes, true);

    $expiresAt = (new DateTimeImmutable('now'))->modify("+{$days} days")->format('Y-m-d H:i:s');
    $stmt = $conn->prepare("INSERT INTO remember_tokens (user_id, selector, token_hash, expires_at) VALUES (?, ?, ?, ?)");
    if (!$stmt) return false;
    $stmt->bind_param("isss", $userId, $selector, $hash, $expiresAt);
    $ok = $stmt->execute();
    $stmt->close();
    if (!$ok) return false;

    $cookieValue = $selector . ':' . $validator;
    return setcookie('alphatech_remember', $cookieValue, alphatech_cookie_options($days * 86400));
}

function alphatech_clear_remember_cookie(mysqli $conn): void
{
    if (!empty($_COOKIE['alphatech_remember'])) {
        $parts = explode(':', $_COOKIE['alphatech_remember'], 2);
        $selector = $parts[0] ?? '';
        if ($selector !== '') {
            $stmt = $conn->prepare("DELETE FROM remember_tokens WHERE selector = ?");
            if ($stmt) {
                $stmt->bind_param("s", $selector);
                $stmt->execute();
                $stmt->close();
            }
        }
    }
    setcookie('alphatech_remember', '', alphatech_cookie_options(-3600));
}

function alphatech_try_remember_login(mysqli $conn): bool
{
    if (!empty($_SESSION['user_id'])) return true;
    if (empty($_COOKIE['alphatech_remember'])) return false;

    $parts = explode(':', $_COOKIE['alphatech_remember'], 2);
    if (count($parts) !== 2) return false;
    [$selector, $validator] = $parts;
    if ($selector === '' || $validator === '') return false;

    $validatorBytes = alphatech_b64url_decode($validator);
    if ($validatorBytes === false) return false;

    $stmt = $conn->prepare("SELECT user_id, token_hash, expires_at FROM remember_tokens WHERE selector = ? LIMIT 1");
    if (!$stmt) return false;
    $stmt->bind_param("s", $selector);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res ? $res->fetch_assoc() : null;
    $stmt->close();
    if (!$row) return false;

    if (strtotime($row['expires_at']) < time()) {
        $stmt = $conn->prepare("DELETE FROM remember_tokens WHERE selector = ?");
        if ($stmt) {
            $stmt->bind_param("s", $selector);
            $stmt->execute();
            $stmt->close();
        }
        return false;
    }

    $expected = $row['token_hash'];
    $actual = hash('sha256', $validatorBytes, true);
    if (!is_string($expected) || !hash_equals($expected, $actual)) {
        return false;
    }

    $nameCol = alphatech_user_name_column($conn);
    if (!$nameCol) return false;

    $userId = (int)$row['user_id'];
    $sql = "SELECT id, {$nameCol} AS display_name FROM users WHERE id = ? LIMIT 1";
    $stmt = $conn->prepare($sql);
    if (!$stmt) return false;
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $res = $stmt->get_result();
    $user = $res ? $res->fetch_assoc() : null;
    $stmt->close();
    if (!$user) return false;

    $_SESSION['user_id'] = (int)$user['id'];
    $_SESSION['user_name'] = $user['display_name'] ?? '';

    // Rotate token on use.
    $stmt = $conn->prepare("DELETE FROM remember_tokens WHERE selector = ?");
    if ($stmt) {
        $stmt->bind_param("s", $selector);
        $stmt->execute();
        $stmt->close();
    }
    alphatech_set_remember_cookie($conn, $_SESSION['user_id']);

    return true;
}

