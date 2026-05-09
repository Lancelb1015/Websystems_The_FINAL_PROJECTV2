<?php
session_start();
require __DIR__ . '/db_connect.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/notify.php';
require_once __DIR__ . '/sms.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $method = ($_POST['method'] ?? 'email') === 'sms' ? 'sms' : 'email';

    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        header("Location: ForgotPassword.php?error=Please+enter+a+valid+email+address");
        exit();
    }

    $stmt = $conn->prepare("SELECT id, email, phone FROM users WHERE email = ? LIMIT 1");
    $user = null;
    if ($stmt) {
        $stmt->bind_param("s", $email);
        if ($stmt->execute()) {
            $res = $stmt->get_result();
            $user = $res ? $res->fetch_assoc() : null;
        }
        $stmt->close();
    }

    // Always show the same result to prevent account enumeration.
    $successMsg = "If an account exists for that email, we've sent reset instructions.";

    if ($user) {
        $userId = (int)$user['id'];

        if ($method === 'sms') {
            $phone = trim((string)($user['phone'] ?? ''));
            if ($phone !== '') {
                $code = (string)random_int(100000, 999999);
                $codeHash = hash('sha256', $code, true);
                $expiresAt = (new DateTimeImmutable('now'))->modify('+10 minutes')->format('Y-m-d H:i:s');

                $stmt = $conn->prepare("INSERT INTO password_otps (user_id, code_hash, expires_at) VALUES (?, ?, ?)");
                if ($stmt) {
                    $stmt->bind_param("iss", $userId, $codeHash, $expiresAt);
                    if ($stmt->execute()) {
                        $err = null;
                        alphatech_send_sms($phone, "AlphaTech reset code: {$code} (valid 10 min)", $err);
                    }
                    $stmt->close();
                }
            }
        } else {
            $selector = alphatech_b64url_encode(random_bytes(18)); // 24 chars
            $tokenBytes = random_bytes(32);
            $token = alphatech_b64url_encode($tokenBytes);
            $tokenHash = hash('sha256', $tokenBytes, true);
            $expiresAt = (new DateTimeImmutable('now'))->modify('+30 minutes')->format('Y-m-d H:i:s');

            $stmt = $conn->prepare("INSERT INTO password_resets (user_id, selector, token_hash, expires_at) VALUES (?, ?, ?, ?)");
            if ($stmt) {
                $stmt->bind_param("isss", $userId, $selector, $tokenHash, $expiresAt);
                if ($stmt->execute()) {
                    $resetLink = alphatech_base_url() . "/ResetPassword.php?selector=" . urlencode($selector) . "&token=" . urlencode($token);
                    alphatech_send_password_reset_email($email, $resetLink);
                }
                $stmt->close();
            }
        }
    }

    header("Location: ForgotPassword.php?success=" . urlencode($successMsg));
    exit();
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Forgot Password | AlphaTech</title>
  <link rel="stylesheet" href="signup.css">
  <link rel="icon" type="image/png" href="logo.png"/>
  <style>
    .msg { font-size: 13px; text-align: center; margin-bottom: 10px; }
    .msg.error { color: #e74c3c; }
    .msg.success { color: #27ae60; }
    .method { display:flex; gap:14px; justify-content:center; margin: 10px 0 16px; font-size: 13px; color:#444; }
    .method label { display:flex; gap:8px; align-items:center; cursor:pointer; }
  </style>
</head>
<body>

<header class="navbar">
  <div class="logo">ALPHA TECH</div>
  <nav>
    <a href="Contact.html">Contact</a>
    <a href="About.html">About</a>
    <a href="Signup.php">Sign Up</a>
    <a href="Login.php">Log In</a>
  </nav>
</header>

<section class="hero">
  <div class="hero-image">
    <img src="cart.jpg" alt="Shopping cart with phone and bags">
  </div>
  <div class="login-panel">
    <h1>Forgot Password</h1>
    <p class="sub">Enter your email to receive reset instructions</p>

    <?php if (!empty($_GET['error'])): ?>
      <p class="msg error"><?php echo htmlspecialchars($_GET['error']); ?></p>
    <?php endif; ?>

    <?php if (!empty($_GET['success'])): ?>
      <p class="msg success"><?php echo htmlspecialchars($_GET['success']); ?></p>
    <?php endif; ?>

    <form action="ForgotPassword.php" method="POST" class="login-form">
      <div class="field">
        <input type="email" name="email" placeholder="Email" required>
      </div>

      <div class="method">
        <label><input type="radio" name="method" value="email" checked> Email link</label>
        <label><input type="radio" name="method" value="sms"> SMS code</label>
      </div>

      <div class="actions">
        <button type="submit" class="btn-login">Send Reset</button>
        <a href="Login.php" class="forgot">Back to Log In</a>
      </div>
      <p style="text-align:center; margin-top:10px; font-size:12px; color:#666;">
        SMS requires a phone number on your account and SMS provider setup.
      </p>
      <p style="text-align:center; margin-top:10px; font-size:12px; color:#666;">
        Got an SMS code? <a href="VerifyOtp.php">Verify here</a>
      </p>
    </form>
  </div>
</section>

<script src="cookie_consent.js"></script>

</body>
</html>
