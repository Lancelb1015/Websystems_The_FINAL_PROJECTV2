<?php
session_start();
require 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email    = trim($_POST['email']);
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        header("Location: Login.php?error=Please+fill+in+all+fields");
        exit();
    }

    $stmt = $conn->prepare("SELECT id, name, password FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        header("Location: Login.php?error=Invalid+email+or+password");
        exit();
    }

    $user = $result->fetch_assoc();

    if (password_verify($password, $user['password'])) {
        $_SESSION['user_id']   = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $next = $_GET['next'] ?? 'home.php';
        header("Location: $next");
        exit();
    } else {
        header("Location: Login.php?error=Invalid+email+or+password");
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
  <title>Log In | AlphaTech</title>
  <link rel="stylesheet" href="signup.css">
   <link rel="icon" type="image/png" href="logo.png"/>
</head>
<body>

<header class="navbar">
  <div class="logo">ALPHA TECH</div>
  <nav>
    <a href="Contact.html">Contact</a>
    <a href="About.html">About</a>
    <a href="Signup.php">Sign Up</a>
  </nav>
</header>

<section class="hero">
  <div class="hero-image">
    <img src="cart.jpg" alt="Shopping cart with phone and bags">
  </div>
  <div class="login-panel">
    <h1>Log in to AlphaTech</h1>
    <p class="sub">Enter your details below</p>
    <?php if (!empty($_GET['error'])): ?>
      <p class="error"><?php echo htmlspecialchars($_GET['error']); ?></p>
    <?php endif; ?>
    <form action="Login.php" method="post" class="login-form">
      <div class="field">
        <input type="email" name="email" placeholder="Email" required>
      </div>
      <div class="field">
        <input type="password" name="password" placeholder="Password" required>
      </div>
      <div class="actions">
        <button type="submit" class="btn-login">Log In</button>
        <a href="#" class="forgot">Forget Password?</a>
      </div>
    </form>
  </div>
</section>

</body>
</html>


































































