<?php
session_start();
require __DIR__ . '/db_connect.php';
require_once __DIR__ . '/auth.php';

function reset_fail(string $msg): void
{
    header("Location: ForgotPassword.php?error=" . urlencode($msg));
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $selector = trim($_POST['selector'] ?? '');
    $token = trim($_POST['token'] ?? '');
    $password = (string)($_POST['password'] ?? '');
    $confirm = (string)($_POST['confirm_password'] ?? '');

    if ($selector === '' || $token === '') reset_fail('Invalid or expired reset link.');
    if ($password === '' || strlen($password) < 6) reset_fail('Password must be at least 6 characters.');
    if ($password !== $confirm) reset_fail('Passwords do not match.');

    $tokenBytes = alphatech_b64url_decode($token);
    if ($tokenBytes === false) reset_fail('Invalid or expired reset link.');
    $tokenHash = hash('sha256', $tokenBytes, true);

    $stmt = $conn->prepare("SELECT id, user_id, token_hash, expires_at, used_at FROM password_resets WHERE selector = ? LIMIT 1");
    if (!$stmt) reset_fail('Server error. Please try again.');
    $stmt->bind_param("s", $selector);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res ? $res->fetch_assoc() : null;
    $stmt->close();

    if (!$row) reset_fail('Invalid or expired reset link.');
    if (!empty($row['used_at'])) reset_fail('This reset link has already been used.');
    if (strtotime($row['expires_at']) < time()) reset_fail('This reset link has expired.');

    if (!hash_equals($row['token_hash'], $tokenHash)) reset_fail('Invalid or expired reset link.');

    $userId = (int)$row['user_id'];
    $hashed = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
    if (!$stmt) reset_fail('Server error. Please try again.');
    $stmt->bind_param("si", $hashed, $userId);
    $ok = $stmt->execute();
    $stmt->close();
    if (!$ok) reset_fail('Server error. Please try again.');

    $stmt = $conn->prepare("UPDATE password_resets SET used_at = NOW() WHERE id = ?");
    if ($stmt) {
        $id = (int)$row['id'];
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
    }

    // Invalidate remember tokens after password reset.
    $stmt = $conn->prepare("DELETE FROM remember_tokens WHERE user_id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $stmt->close();
    }

    header("Location: Login.php?success=" . urlencode('Password updated. Please log in.'));
    exit();
}

// GET: validate token and show form.
$selector = trim($_GET['selector'] ?? '');
$token = trim($_GET['token'] ?? '');
if ($selector === '' || $token === '') reset_fail('Invalid or expired reset link.');

$tokenBytes = alphatech_b64url_decode($token);
if ($tokenBytes === false) reset_fail('Invalid or expired reset link.');
$tokenHash = hash('sha256', $tokenBytes, true);

$stmt = $conn->prepare("SELECT token_hash, expires_at, used_at FROM password_resets WHERE selector = ? LIMIT 1");
if (!$stmt) reset_fail('Server error. Please try again.');
$stmt->bind_param("s", $selector);
$stmt->execute();
$res = $stmt->get_result();
$row = $res ? $res->fetch_assoc() : null;
$stmt->close();

if (!$row) reset_fail('Invalid or expired reset link.');
if (!empty($row['used_at'])) reset_fail('This reset link has already been used.');
if (strtotime($row['expires_at']) < time()) reset_fail('This reset link has expired.');
if (!hash_equals($row['token_hash'], $tokenHash)) reset_fail('Invalid or expired reset link.');

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Reset Password | AlphaTech</title>
  <link rel="stylesheet" href="signup.css">
  <link rel="icon" type="image/png" href="logo.png"/>
  <style>
    .msg { font-size: 13px; text-align: center; margin-bottom: 10px; color:#444; }
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
    <h1>Reset Password</h1>
    <p class="sub">Choose a new password</p>

    <form action="ResetPassword.php" method="POST" class="login-form">
      <input type="hidden" name="selector" value="<?php echo htmlspecialchars($selector); ?>">
      <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">

      <div class="field">
        <input type="password" name="password" placeholder="New Password (min. 6 characters)" required minlength="6">
      </div>
      <div class="field">
        <input type="password" name="confirm_password" placeholder="Confirm New Password" required minlength="6">
      </div>
      <div class="actions">
        <button type="submit" class="btn-login">Update Password</button>
        <a href="Login.php" class="forgot">Back to Log In</a>
      </div>
      <p class="msg">This link expires in 30 minutes.</p>
    </form>
  </div>
</section>

<script src="cookie_consent.js"></script>

</body>
</html>
