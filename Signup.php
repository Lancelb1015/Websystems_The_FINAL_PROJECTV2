<?php
session_start();
require 'db_connect.php';
require_once __DIR__ . '/auth.php';

function user_column_exists(mysqli $conn, string $column): bool
{
    $safe = $conn->real_escape_string($column);
    $res = $conn->query("SHOW COLUMNS FROM users LIKE '{$safe}'");
    return $res && $res->num_rows > 0;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $fullname = trim($_POST['fullname']);
    $email    = trim($_POST['email']);
    $phone    = trim($_POST['phone'] ?? '');
    $password = $_POST['password'];
    $next     = trim($_POST['next'] ?? '');

    $nextQuery = $next !== '' ? ('&next=' . urlencode($next)) : '';

    if (empty($fullname) || empty($email) || empty($password)) {
        header("Location: Signup.php?error=Please+fill+in+all+required+fields{$nextQuery}");
        exit();
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        header("Location: Signup.php?error=Invalid+email+address{$nextQuery}");
        exit();
    }

    if (strlen($password) < 6) {
        header("Location: Signup.php?error=Password+must+be+at+least+6+characters{$nextQuery}");
        exit();
    }

    if ($phone !== '' && !preg_match('/^[0-9+\\-\\s]{7,30}$/', $phone)) {
        header("Location: Signup.php?error=Invalid+phone+number{$nextQuery}");
        exit();
    }

    $phoneVal = ($phone === '') ? null : $phone;

    // Check if email already exists
    $check = $conn->prepare("SELECT id FROM users WHERE email = ?");
    if (!$check) {
        header("Location: Signup.php?error=Server+error.+Please+try+again.{$nextQuery}");
        exit();
    }
    $check->bind_param("s", $email);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
        header("Location: Signup.php?error=Email+already+registered.+Please+log+in.{$nextQuery}");
        exit();
    }
    $check->close();

    $hashed = password_hash($password, PASSWORD_DEFAULT);

    $hasNameCol = user_column_exists($conn, 'name');
    $hasFullnameCol = user_column_exists($conn, 'fullname');
    $hasPhoneCol = user_column_exists($conn, 'phone');

    if ($hasNameCol && $hasFullnameCol && $hasPhoneCol) {
        $stmt = $conn->prepare("INSERT INTO users (name, fullname, phone, email, password) VALUES (?, ?, ?, ?, ?)");
    } elseif ($hasNameCol && $hasFullnameCol) {
        $stmt = $conn->prepare("INSERT INTO users (name, fullname, email, password) VALUES (?, ?, ?, ?)");
    } elseif ($hasNameCol && $hasPhoneCol) {
        $stmt = $conn->prepare("INSERT INTO users (name, phone, email, password) VALUES (?, ?, ?, ?)");
    } elseif ($hasNameCol) {
        $stmt = $conn->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
    } elseif ($hasPhoneCol) {
        $stmt = $conn->prepare("INSERT INTO users (fullname, phone, email, password) VALUES (?, ?, ?, ?)");
    } else {
        $stmt = $conn->prepare("INSERT INTO users (fullname, email, password) VALUES (?, ?, ?)");
    }
    if (!$stmt) {
        header("Location: Signup.php?error=Server+error.+Please+try+again.{$nextQuery}");
        exit();
    }

    if ($hasNameCol && $hasFullnameCol && $hasPhoneCol) {
        $stmt->bind_param("sssss", $fullname, $fullname, $phoneVal, $email, $hashed);
    } elseif ($hasNameCol && $hasFullnameCol) {
        $stmt->bind_param("ssss", $fullname, $fullname, $email, $hashed);
    } elseif ($hasNameCol && $hasPhoneCol) {
        $stmt->bind_param("ssss", $fullname, $phoneVal, $email, $hashed);
    } elseif ($hasNameCol) {
        $stmt->bind_param("sss", $fullname, $email, $hashed);
    } elseif ($hasPhoneCol) {
        $stmt->bind_param("ssss", $fullname, $phoneVal, $email, $hashed);
    } else {
        $stmt->bind_param("sss", $fullname, $email, $hashed);
    }

    if ($stmt->execute()) {
        $_SESSION['user_id'] = (int)$stmt->insert_id;
        $_SESSION['user_name'] = $fullname;

        $dest = 'index.php';
        if ($next !== '') {
            $next = ltrim($next, "/\\");
            if (preg_match('/^[a-zA-Z0-9_\\-\\/\\.]+$/', $next) && !str_contains($next, '..') && !str_contains($next, ':')) {
                $dest = $next;
            }
        }
        header("Location: {$dest}");
        exit();
    } else {
        header("Location: Signup.php?error=Registration+failed.+Please+try+again.{$nextQuery}");
        exit();
    }

    $stmt->close();
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sign Up | AlphaTech</title>
  <link rel="stylesheet" href="signup.css">
  <link rel="icon" type="image/png" href="logo.png"/>
  <style>
    .error   { color: #e74c3c; font-size: 13px; text-align: center; margin-bottom: 10px; }
  </style>
</head>
<body>

<header class="navbar">
  <div class="logo">ALPHA TECH</div>
  <nav>
    <a href="Contact.html">Contact</a>
    <a href="About.html">About</a>
    <a href="Login.php">Log In</a>
  </nav>
</header>

<section class="hero">
  <div class="hero-image">
    <img src="cart.jpg" alt="Shopping cart with phone and bags">
  </div>
  <div class="login-panel">
    <h1>Create Account</h1>
    <p class="sub">Enter your details below</p>

    <?php if (!empty($_GET['error'])): ?>
      <p class="error"><?php echo htmlspecialchars($_GET['error']); ?></p>
    <?php endif; ?>

    <form action="Signup.php" method="POST" class="login-form">
      <?php if (!empty($_GET['next'])): ?>
        <input type="hidden" name="next" value="<?php echo htmlspecialchars($_GET['next']); ?>">
      <?php endif; ?>
      <div class="field">
        <input type="text" name="fullname" placeholder="Full Name" required>
      </div>
      <div class="field">
        <input type="email" name="email" placeholder="Email Address" required>
      </div>
      <div class="field">
        <input type="text" name="phone" placeholder="Mobile Number (optional)">
      </div>
      <div class="field">
        <input type="password" name="password" placeholder="Password (min. 6 characters)" required minlength="6">
      </div>
      <div class="actions">
        <button type="submit" class="btn-login">Create Account</button>
      </div>
    </form>

    <p style="text-align:center; margin-top:14px; font-size:13px;">
      Already have an account?
      <a href="Login.php<?php echo !empty($_GET['next']) ? ('?next=' . urlencode($_GET['next'])) : ''; ?>">Log In</a>
    </p>
  </div>
</section>

<script src="cookie_consent.js"></script>

</body>
</html>
