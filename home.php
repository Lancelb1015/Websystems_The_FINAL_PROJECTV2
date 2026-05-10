<?php
session_start();
require __DIR__ . '/db_connect.php';
require_once __DIR__ . '/auth.php';

alphatech_try_remember_login($conn);

if (!isset($_SESSION['user_id'])) {
    header("Location: Login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <!-- Added smooth scroll behavior -->
  <style>
    html { scroll-behavior: smooth; }
  </style>
  <link rel="stylesheet" href="dashboars.css?v=5">
  <title>Shop Online | AlphaTech</title>
  <link rel="icon" type="image/png" href="logo.png"/>
</head>
<body>

<header class="navbar">
  <div class="logo">ALPHA TECH</div>
  <nav>
    <a href="home.php">Home</a>
    <a href="Contact.html">Contact</a>
    <a href="About.html">About</a>
    <a href="logout.php">Log Out</a>
  </nav>
  <input type="text" placeholder="What are you looking for?">
</header>

<section class="hero">
  <div class="hero-inner">
    <div class="hero-text">
      <h1>ALPHA TECH</h1>
      <p>
        Discover premium computer hardware from top brands,
        from processors to graphic card, we have everything
        here you need to build a perfect system.
      </p>
      <div class="hero-actions">
        <!-- Modified: Directs to home.php and scrolls to the products ID -->
        <button class="btn-primary" onclick="window.location.href='home.php#products'">Shop now</button>
        <input class="hero-search" type="text" placeholder="">
      </div>
    </div>
    <div class="hero-image">
      <img src="RAm.jpg" alt="RAM">
    </div>
  </div>

  <div class="hero-divider"></div>
  <div class="hero-stats">
    <div class="hero-stat">
      <div class="value">5000+</div>
      <div class="label">Products</div>
    </div>
    <div class="hero-stat">
      <div class="value">98%</div>
      <div class="label">Customer satisfaction</div>
    </div>
    <div class="hero-stat">
      <div class="value">24/7</div>
      <div class="label">Support</div>
    </div>
  </div>
</section>

<section class="categories">
  <h2>Shop by category</h2>
  <p>Find exactly what you need. Browse our extensive selection of computer hardware organize by component type</p>
  <div class="category-grid">
    <a href="server.html"><div class="card"><img src="categorize_server.jpg" alt="Server"><span>Server</span></div></a>
    <a href="workstation.html"><div class="card"><img src="categorize_workstation.jpg" alt="Workstation"><span>Workstation</span></div></a>
    <a href="router.html"><div class="card"><img src="categorize_Router.webp" alt="Router"><span>Router</span></div></a>
    <a href="switch.html"><div class="card"><img src="categorize_Switches.jpg" alt="Switches"><span>Switches</span></div></a>
    <a href="gpu.html"><div class="card"><img src="categorize_GPU.jpg" alt="GPU"><span>GPU</span></div></a>
  </div>
</section>

<!-- Added id="products" to allow the Shop Now button to scroll here -->
<section id="products" class="products">
  <div class="products-header">
    <h2>Best Selling Products</h2>
    <button class="view-all">
      <a href="cart.html">View Cart</a>
      <span id="cart-badge" class="cart-badge" style="display:none;">0</span>
    </button>
  </div>

  <div class="product-grid">
    <div class="product-card">
      <img src="Router.png" alt="Tenda AC6 Router">
      <h4>Tenda AC6 Smart WiFi Router 1200Mbps High Speed Dual Band 2.4Ghz&5Ghz Wireless Internet Router</h4>
      <p class="price">₱ 200,000</p>
      <button class="my-button"
        data-id="1" data-name="Tenda AC6 Smart WiFi Router"
        data-sku="RT-TENDA-AC6" data-price="200000"
        onclick="addToCart(this)">Add to Cart</button>
    </div>

    <div class="product-card">
      <img src="Server.png" alt="Dell PowerEdge R760 Server">
      <h4>PowerEdge R760</h4>
      <p class="price">₱ 100,000</p>
      <button class="my-button"
        data-id="2" data-name="Dell PowerEdge R760 Server"
        data-sku="SRV-DELL-R760" data-price="100000"
        onclick="addToCart(this)">Add to Cart</button>
    </div>

    <div class="product-card">
      <img src="Monitor.webp" alt="IPS LCD Gaming Monitor">
      <h4>IPS LCD Gaming Monitor</h4>
      <p class="price">₱ 75,000</p>
      <button class="my-button"
        data-id="3" data-name="IPS LCD Gaming Monitor"
        data-sku="MON-IPS-GAMING" data-price="75000"
        onclick="addToCart(this)">Add to Cart</button>
    </div>

    <div class="product-card">
      <img src="GPU.webp" alt="Asus ROG RTX 3050 GPU">
      <h4>Asus ROG-STRIX-RTX3050-O8G-GAMING NVIDIA GeForce RTX 3050 8GB GDDR6</h4>
      <p class="price">₱ 50,000</p>
      <button class="my-button"
        data-id="4" data-name="Asus ROG RTX 3050 Graphics Card"
        data-sku="GPU-ASUS-3050" data-price="50000"
        onclick="addToCart(this)">Add to Cart</button>
    </div>

    <div class="product-card">
      <img src="categorize_GPU.jpg" alt="Intel Core i7 Processor">
      <h4>Intel Core i7 Processor</h4>
      <p class="price">₱ 28,000</p>
      <button class="my-button"
        data-id="5" data-name="Intel Core i7 Processor"
        data-sku="CPU-INTEL-I7" data-price="28000"
        onclick="addToCart(this)">Add to Cart</button>
    </div>

    <div class="product-card">
      <img src="Switches.webp" alt="Cisco Managed Switch 24-Port">
      <h4>Cisco Managed Switch 24-Port</h4>
      <p class="price">₱ 12,500</p>
      <button class="my-button"
        data-id="6" data-name="Cisco Managed Switch 24-Port"
        data-sku="SWT-CISCO-24" data-price="12500"
        onclick="addToCart(this)">Add to Cart</button>
    </div>

    <div class="product-card">
      <img src="cart.jpg" alt="Gaming Mouse">
      <h4>Gaming Mouse</h4>
      <p class="price">₱ 1,250</p>
      <button class="my-button"
        data-id="7" data-name="Gaming Mouse"
        data-sku="ACC-MOUSE-GAME" data-price="1250"
        onclick="addToCart(this)">Add to Cart</button>
    </div>

    <div class="product-card">
      <img src="Server.png" alt="Rack Mount Server">
      <h4>Rack Mount Server</h4>
      <p class="price">₱ 75,000</p>
      <button class="my-button"
        data-id="8" data-name="Rack Mount Server"
        data-sku="SRV-RACK-2U" data-price="75000"
        onclick="addToCart(this)">Add to Cart</button>
    </div>

  </div>
</section>

<div id="toast" style="
  display:none; position:fixed; bottom:30px; right:30px;
  background:#222; color:#fff; padding:14px 22px;
  border-radius:8px; font-size:14px; z-index:9999;
  box-shadow:0 4px 16px rgba(0,0,0,0.3);
"></div>

<script>
  let __alphatechLoggedIn = false;
  fetch("session_status.php", { credentials: "same-origin" })
    .then(r => r.json())
    .then(d => { __alphatechLoggedIn = !!d.logged_in; })
    .catch(() => { __alphatechLoggedIn = false; });

  function getCart() { return JSON.parse(localStorage.getItem("alphatech_cart") || "[]"); }
  function saveCart(cart) { localStorage.setItem("alphatech_cart", JSON.stringify(cart)); }

  function addToCart(btn) {
    if (!__alphatechLoggedIn) {
      const next = encodeURIComponent("home.php");
      window.location.href = `Login.php?next=${next}`;
      return;
    }
    const id = parseInt(btn.dataset.id), name = btn.dataset.name,
          sku = btn.dataset.sku, price = parseInt(btn.dataset.price);
    let cart = getCart();
    const existing = cart.find(i => i.id === id);
    if (existing) { existing.qty += 1; } else { cart.push({ id, name, sku, price, qty: 1 }); }
    saveCart(cart); updateBadge(); showToast(`"${name}" added to cart!`);
  }

  function updateBadge() {
    const total = getCart().reduce((s, i) => s + i.qty, 0);
    const badge = document.getElementById("cart-badge");
    badge.textContent = total;
    badge.style.display = total > 0 ? "inline-block" : "none";
  }

  function showToast(msg) {
    const toast = document.getElementById("toast");
    toast.textContent = msg; toast.style.display = "block";
    clearTimeout(toast._timer);
    toast._timer = setTimeout(() => { toast.style.display = "none"; }, 2500);
  }

  updateBadge();
</script>

<script src="cookie_consent.js"></script>

</body>
</html>
