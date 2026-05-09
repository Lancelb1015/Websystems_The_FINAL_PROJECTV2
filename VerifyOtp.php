<?php
session_start();
require __DIR__ . '/db_connect.php';

function otp_error(string $msg): void
{
    header("Location: VerifyOtp.php?error=" . urlencode($msg));
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $code = trim($_POST['code'] ?? '');
    $password = (string)($_POST['password'] ?? '');
    $confirm = (string)($_POST['confirm_password'] ?? '');

    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) otp_error('Please enter a valid email.');
    if (!preg_match('/^[0-9]{6}$/', $code)) otp_error('Invalid code.');
    if ($password === '' || strlen($password) < 6) otp_error('Password must be at least 6 characters.');
    if ($password !== $confirm) otp_error('Passwords do not match.');

    $stmt = $conn->prepare("SELECT id, phone FROM users WHERE email = ? LIMIT 1");
    if (!$stmt) otp_error('Server error. Please try again.');
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $res = $stmt->get_result();
    $user = $res ? $res->fetch_assoc() : null;
    $stmt->close();
    if (!$user) otp_error('Invalid code or expired.');

    $userId = (int)$user['id'];
    $codeHash = hash('sha256', $code, true);

    $stmt = $conn->prepare("SELECT id, code_hash, expires_at FROM password_otps WHERE user_id = ? AND consumed_at IS NULL ORDER BY created_at DESC LIMIT 1");
    if (!$stmt) otp_error('Server error. Please try again.');
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $res = $stmt->get_result();
    $otp = $res ? $res->fetch_assoc() : null;
    $stmt->close();
    if (!$otp) otp_error('Invalid code or expired.');
    if (strtotime($otp['expires_at']) < time()) otp_error('Invalid code or expired.');
    if (!hash_equals($otp['code_hash'], $codeHash)) otp_error('Invalid code or expired.');

    $hashed = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
    if (!$stmt) otp_error('Server error. Please try again.');
    $stmt->bind_param("si", $hashed, $userId);
    $ok = $stmt->execute();
    $stmt->close();
    if (!$ok) otp_error('Server error. Please try again.');

    $stmt = $conn->prepare("UPDATE password_otps SET consumed_at = NOW() WHERE id = ?");
    if ($stmt) {
        $otpId = (int)$otp['id'];
        $stmt->bind_param("i", $otpId);
        $stmt->execute();
        $stmt->close();
    }

    $stmt = $conn->prepare("DELETE FROM remember_tokens WHERE user_id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $stmt->close();
    }

    header("Location: Login.php?success=" . urlencode('Password updated. Please log in.'));
    exit();
}

$prefillEmail = trim($_GET['email'] ?? '');
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Verify SMS Code | AlphaTech</title>
  <link rel="stylesheet" href="signup.css">
  <link rel="icon" type="image/png" href="logo.png"/>
  <style>
    .msg { font-size: 13px; text-align: center; margin-bottom: 10px; }
    .msg.error { color: #e74c3c; }
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
    <h1>Verify SMS Code</h1>
    <p class="sub">Enter the code sent to your phone</p>

    <?php if (!empty($_GET['error'])): ?>
      <p class="msg error"><?php echo htmlspecialchars($_GET['error']); ?></p>
    <?php endif; ?>

    <form action="VerifyOtp.php" method="POST" class="login-form">
      <div class="field">
        <input type="email" name="email" placeholder="Email" required value="<?php echo htmlspecialchars($prefillEmail); ?>">
      </div>
      <div class="field">
        <input type="text" name="code" placeholder="6-digit code" inputmode="numeric" pattern="[0-9]{6}" required>
      </div>
      <div class="field">
        <input type="password" name="password" placeholder="New Password (min. 6 characters)" required minlength="6">
      </div>
      <div class="field">
        <input type="password" name="confirm_password" placeholder="Confirm New Password" required minlength="6">
      </div>
      <div class="actions">
        <button type="submit" class="btn-login">Reset Password</button>
        <a href="ForgotPassword.php" class="forgot">Back</a>
      </div>
    </form>
  </div>
</section>

<script src="cookie_consent.js"></script>

</body>
</html>
