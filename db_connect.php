<?php

$host     = "localhost";
$dbname   = "alphatech_db";
$username = "root";
$password = "";

mysqli_report(MYSQLI_REPORT_OFF);

function alphatech_db_fail(string $title, string $detail = ''): void
{
    http_response_code(500);
    $safeTitle = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
    $safeDetail = htmlspecialchars($detail, ENT_QUOTES, 'UTF-8');
    echo <<<HTML
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>{$safeTitle}</title>
  <style>
    body { font-family: Arial, sans-serif; background:#f6f7fb; margin:0; padding:40px; color:#222; }
    .box { max-width:760px; margin:0 auto; background:#fff; border:1px solid #e5e7eb; border-radius:12px; padding:22px 24px; }
    h1 { font-size:18px; margin:0 0 10px; }
    p { margin:0 0 10px; line-height:1.5; color:#444; }
    code { background:#f3f4f6; padding:2px 6px; border-radius:6px; }
    .hint { margin-top:14px; font-size:13px; color:#555; }
  </style>
</head>
<body>
  <div class="box">
    <h1>{$safeTitle}</h1>
    <p>{$safeDetail}</p>
    <p class="hint">Make sure MySQL is running in XAMPP and the user <code>{$GLOBALS['username']}</code> has access.</p>
  </div>
</body>
</html>
HTML;
    exit();
}

function alphatech_init_schema(mysqli $conn): void
{
    $conn->query("SET SESSION sql_mode = ''");

    $queries = [
        "CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            fullname VARCHAR(100) NOT NULL,
            email VARCHAR(100) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        "CREATE TABLE IF NOT EXISTS categories (
            id INT AUTO_INCREMENT PRIMARY KEY,
            category_name VARCHAR(100) NOT NULL
        )",
        "CREATE TABLE IF NOT EXISTS products (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            description TEXT,
            price DECIMAL(10,2) NOT NULL,
            stock INT DEFAULT 0,
            category_id INT NULL,
            FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
        )",
        "CREATE TABLE IF NOT EXISTS orders (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            order_date DATETIME DEFAULT CURRENT_TIMESTAMP,
            total_amount DECIMAL(10,2) NOT NULL,
            status VARCHAR(80) DEFAULT 'pending',
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )",
        "CREATE TABLE IF NOT EXISTS order_items (
            id INT AUTO_INCREMENT PRIMARY KEY,
            order_id INT NOT NULL,
            product_id INT NOT NULL,
            quantity INT NOT NULL,
            price DECIMAL(10,2) NOT NULL,
            FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
            FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
        )",
        "CREATE TABLE IF NOT EXISTS cart (
            id INT AUTO_INCREMENT PRIMARY KEY,
            order_id INT NULL,
            product_id INT NOT NULL,
            quantity INT NOT NULL,
            FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
            FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
        )",
        "CREATE TABLE IF NOT EXISTS password_resets (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            selector CHAR(24) NOT NULL UNIQUE,
            token_hash BINARY(32) NOT NULL,
            expires_at DATETIME NOT NULL,
            used_at DATETIME NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )",
        "CREATE TABLE IF NOT EXISTS password_otps (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            code_hash BINARY(32) NOT NULL,
            expires_at DATETIME NOT NULL,
            consumed_at DATETIME NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )",
        "CREATE TABLE IF NOT EXISTS remember_tokens (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            selector CHAR(24) NOT NULL UNIQUE,
            token_hash BINARY(32) NOT NULL,
            expires_at DATETIME NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            last_used_at DATETIME NULL,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )",
    ];

    foreach ($queries as $sql) {
        if (!$conn->query($sql)) {
            alphatech_db_fail('Database schema error', $conn->error);
        }
    }

    // Migrations for existing installs (CREATE TABLE IF NOT EXISTS won't add new columns).
    $hasCategoryIdColRes = $conn->query("SHOW COLUMNS FROM products LIKE 'category_id'");
    if ($hasCategoryIdColRes) {
        $hasCategoryIdCol = $hasCategoryIdColRes->num_rows > 0;
        $hasCategoryIdColRes->free();
        if (!$hasCategoryIdCol) {
            if (!$conn->query("ALTER TABLE products ADD COLUMN category_id INT NULL")) {
                alphatech_db_fail('Database schema error', $conn->error);
            }
        }
        // Best-effort FK; ignore errors if it already exists or can't be created.
        $conn->query("ALTER TABLE products ADD CONSTRAINT fk_products_category_id FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL");
    }

    $hasFullnameColRes = $conn->query("SHOW COLUMNS FROM users LIKE 'fullname'");
    if ($hasFullnameColRes) {
        $hasFullnameCol = $hasFullnameColRes->num_rows > 0;
        $hasFullnameColRes->free();
        if (!$hasFullnameCol) {
            if (!$conn->query("ALTER TABLE users ADD COLUMN fullname VARCHAR(100) NULL")) {
                alphatech_db_fail('Database schema error', $conn->error);
            }
            // Backfill from legacy `name` column if present.
            $hasNameColRes = $conn->query("SHOW COLUMNS FROM users LIKE 'name'");
            if ($hasNameColRes) {
                $hasNameCol = $hasNameColRes->num_rows > 0;
                $hasNameColRes->free();
                if ($hasNameCol) {
                    $conn->query("UPDATE users SET fullname = name WHERE fullname IS NULL OR fullname = ''");
                }
            }
        }
    }

    $hasPhoneColRes = $conn->query("SHOW COLUMNS FROM users LIKE 'phone'");
    if ($hasPhoneColRes) {
        $hasPhoneCol = $hasPhoneColRes->num_rows > 0;
        $hasPhoneColRes->free();
        if (!$hasPhoneCol) {
            // Store as string to keep leading zeros.
            if (!$conn->query("ALTER TABLE users ADD COLUMN phone VARCHAR(30) NULL")) {
                alphatech_db_fail('Database schema error', $conn->error);
            }
        }
    }

    $catCountRes = $conn->query("SELECT COUNT(*) AS c FROM categories");
    if (!$catCountRes) {
        alphatech_db_fail('Database error', $conn->error);
    }
    $catCount = (int) $catCountRes->fetch_assoc()['c'];
    $catCountRes->free();

    if ($catCount === 0) {
        $seedCategories = [
            'Router',
            'Server',
            'Workstation',
            'GPU',
            'Switch',
        ];
        $stmt = $conn->prepare("INSERT INTO categories (category_name) VALUES (?)");
        if (!$stmt) {
            alphatech_db_fail('Database error', $conn->error);
        }
        foreach ($seedCategories as $name) {
            $stmt->bind_param("s", $name);
            if (!$stmt->execute()) {
                $stmt->close();
                alphatech_db_fail('Database seed error', $conn->error);
            }
        }
        $stmt->close();

        $seedProducts = [
            ['Tenda AC6 Smart WiFi Router', 'Dual Band 2.4GHz & 5GHz, 1200Mbps', 200000.00, 20, 1],
            ['Dell PowerEdge R760 Server', '2U rack server, Intel Xeon, 32GB RAM', 100000.00, 5, 2],
            ['IPS LCD Gaming Monitor', '27-inch 144Hz IPS panel', 75000.00, 10, 3],
            ['Asus ROG RTX 3050 8GB Graphics Card', 'GDDR6 128-bit, NVIDIA GeForce RTX 3050', 50000.00, 8, 4],
            ['Cisco Managed Switch 24-Port', '24-port gigabit managed switch', 12500.00, 15, 5],
            ['Rack Mount Server', 'Enterprise-grade 2U rack server', 75000.00, 3, 2],
            ['High-End Workstation PC', 'For CAD, 3D rendering, and video editing', 45000.00, 7, 3],
        ];

        $stmt = $conn->prepare("INSERT INTO products (name, description, price, stock, category_id) VALUES (?, ?, ?, ?, ?)");
        if (!$stmt) {
            alphatech_db_fail('Database error', $conn->error);
        }
        foreach ($seedProducts as [$pName, $desc, $price, $stock, $catId]) {
            $stmt->bind_param("ssdii", $pName, $desc, $price, $stock, $catId);
            if (!$stmt->execute()) {
                $stmt->close();
                alphatech_db_fail('Database seed error', $conn->error);
            }
        }
        $stmt->close();
    }
}

$conn = @new mysqli($host, $username, $password, $dbname);
if ($conn->connect_errno) {
    $serverConn = @new mysqli($host, $username, $password);
    if ($serverConn->connect_errno) {
        alphatech_db_fail('MySQL connection failed', $serverConn->connect_error);
    }

    $serverConn->set_charset('utf8mb4');

    $dbSql = "CREATE DATABASE IF NOT EXISTS `{$dbname}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
    if (!$serverConn->query($dbSql)) {
        alphatech_db_fail('Database create failed', $serverConn->error);
    }
    if (!$serverConn->select_db($dbname)) {
        alphatech_db_fail('Database select failed', $serverConn->error);
    }

    alphatech_init_schema($serverConn);
    $conn = $serverConn;
}

$conn->set_charset('utf8mb4');

// Ensure required tables exist even if the DB already existed.
alphatech_init_schema($conn);
?>
