<?php
session_start();
require 'includes/db.php';

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id']; // Get the logged-in user ID

// Fetch cart items for the user using PDO
try {
    $sql = "SELECT p.*, c.quantity
            FROM cart c
            JOIN products p ON c.product_id = p.id
            WHERE c.user_id = :user_id";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    
    $cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
    die();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Your Cart</title>
  <link rel="icon" href="images/yoona.png">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
  <style>
    :root {
      --sidebar-bg: #18315B;
      --sidebar-text: #fff;
      --sidebar-active: #FFD166;
      --main-bg: #F3E9D2;
      --card-bg: #fff;
      --card-shadow: 0 2px 8px rgba(0,0,0,0.07);
      --primary: #18315B;
      --accent: #FFD166;
      --danger: #FFE5E5;
      --danger-border: #FFBABA;
      --danger-text: #D7263D;
      --blue-light: #E6F0FF;
      --blue-border: #B3C7E6;
    }
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
      font-family: 'Poppins', sans-serif;
    }
    body {
      background: var(--main-bg);
    }
    .dashboard {
      display: flex;
      min-height: 100vh;
    }
    .sidebar {
      width: 260px;
      background: var(--sidebar-bg);
      color: var(--sidebar-text);
      display: flex;
      flex-direction: column;
      align-items: center;
      padding: 32px 0 16px 0;
      position: fixed;
      height: 100vh;
      z-index: 10;
    }
    .sidebar .logo {
      display: flex;
      flex-direction: column;
      align-items: center;
      margin-bottom: 32px;
    }
    .sidebar .logo img {
      width: 60px;
      height: 60px;
      margin-bottom: 10px;
    }
    .sidebar .logo span {
      font-size: 2rem;
      font-weight: 700;
      letter-spacing: 1px;
    }
    .sidebar .menu {
      list-style: none;
      width: 100%;
      flex: 1;
    }
    .sidebar .menu li {
      margin-bottom: 8px;
    }
    .sidebar .menu li a {
      color: var(--sidebar-text);
      text-decoration: none;
      display: flex;
      align-items: center;
      padding: 12px 32px;
      border-radius: 8px;
      font-size: 1rem;
      transition: background 0.2s;
    }
    .sidebar .menu li a.active, .sidebar .menu li a:hover {
      background: rgba(255, 209, 102, 0.15);
      color: var(--accent);
    }
    .sidebar .menu li img {
      width: 22px;
      height: 22px;
      margin-right: 16px;
    }
    .sidebar .logout {
      margin-top: auto;
      padding: 16px 0 0 0;
      width: 100%;
      text-align: center;
    }
    .sidebar .logout a {
      color: #fff;
      text-decoration: none;
      font-size: 1rem;
      opacity: 0.8;
      transition: color 0.2s, opacity 0.2s;
    }
    .sidebar .logout a:hover {
      color: var(--accent);
      opacity: 1;
    }
    .main-content {
      flex: 1;
      margin-left: 260px;
      background: var(--main-bg);
      min-height: 100vh;
      padding: 0 0 40px 0;
    }
    .topbar {
      background: var(--accent);
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 0 32px;
      height: 80px;
      border-bottom-left-radius: 18px;
      border-bottom-right-radius: 18px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.04);
      margin-bottom: 30px;
    }
    .topbar .cart-title {
      font-size: 2rem;
      font-weight: 700;
      color: var(--primary);
    }
    .topbar-icons {
      display: flex;
      gap: 18px;
    }
    .topbar .icon-link {
      color: var(--sidebar-active);
      font-size: 2rem;
      text-decoration: none;
      transition: color 0.2s;
      display: flex;
      align-items: center;
      justify-content: center;
      background: #fff;
      border-radius: 50%;
      width: 48px;
      height: 48px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.07);
    }
    .topbar .icon-link.active {
      background: var(--accent);
      color: var(--primary);
      border: 2px solid var(--primary);
    }
    .topbar .icon-link:hover {
      color: var(--primary);
      background: var(--accent);
    }
    .cart-container {
      padding: 0 40px 40px 40px;
      margin-top: 30px;
    }
    .cart-item {
      background-color: var(--card-bg);
      border-radius: 18px;
      box-shadow: var(--card-shadow);
      padding: 24px 18px 18px 18px;
      display: flex;
      align-items: center;
      gap: 24px;
      margin-bottom: 24px;
    }
    .cart-item img {
      width: 120px;
      height: 120px;
      object-fit: contain;
      border-radius: 12px;
      background: #f8f9fc;
      box-shadow: 0 2px 8px rgba(0,0,0,0.04);
    }
    .item-details {
      flex-grow: 1;
    }
    .item-name {
      font-size: 1.2rem;
      font-weight: 600;
      color: var(--primary);
    }
    .item-desc {
      font-size: 14px;
      color: #6b7280;
      margin-top: 6px;
    }
    .item-price {
      font-size: 1.1rem;
      font-weight: bold;
      color: #374151;
      margin-top: 10px;
    }
    .quantity-control {
      display: flex;
      align-items: center;
      margin-top: 10px;
    }
    .quantity-btn {
      background-color: var(--primary);
      color: white;
      border: none;
      width: 30px;
      height: 30px;
      border-radius: 8px;
      font-size: 20px;
      cursor: pointer;
      line-height: 1;
      transition: background 0.2s;
    }
    .quantity-btn:hover {
      background: var(--accent);
      color: var(--primary);
    }
    .quantity-number {
      margin: 0 10px;
      font-size: 16px;
      min-width: 20px;
      text-align: center;
    }
    .action-buttons {
      display: flex;
      flex-direction: column;
      align-items: flex-start;
      gap: 10px;
      margin-left: 10px;
    }
    .remove-btn, .checkout-btn {
      border: none;
      padding: 8px 12px;
      border-radius: 8px;
      cursor: pointer;
      font-size: 14px;
      height: 40px;
      width: 110px;
      text-align: center;
      font-weight: 500;
      transition: background 0.2s;
    }
    .remove-btn {
      background-color: var(--danger);
      color: var(--danger-text);
      border: 1.5px solid var(--danger-border);
    }
    .remove-btn:hover {
      background-color: var(--danger-text);
      color: #fff;
    }
    .checkout-btn {
      background-color: var(--accent);
      color: var(--primary);
    }
    .checkout-btn:hover {
      background-color: var(--primary);
      color: #fff;
    }
    .empty-cart {
      text-align: center;
      margin-top: 50px;
      font-size: 20px;
      color: #6b7280;
    }
    .cart-summary {
      background: var(--card-bg);
      border-radius: 18px;
      box-shadow: var(--card-shadow);
      padding: 24px 18px;
      margin: 0 auto 24px auto;
      max-width: 400px;
      text-align: right;
      font-size: 1.2rem;
      color: var(--primary);
      font-weight: 600;
    }
    @media (max-width: 1100px) {
      .main-content {
        margin-left: 0;
      }
      .sidebar {
        width: 70px;
        padding: 16px 0;
      }
      .sidebar .logo span, .sidebar .menu li span, .sidebar .logout {
        display: none;
      }
    }
    @media (max-width: 700px) {
      .cart-container {
        padding: 0 8px 40px 8px;
      }
    }
  </style>
</head>
<body>
<div class="dashboard">
  <div class="sidebar">
    <div class="logo">
      <img src="images/yoona.png" alt="logo">
      <span>Yoona</span>
    </div>
    <ul class="menu">
      <li><a href="main.php"><img src="images/dashboard.png" alt=""> <span>Home</span></a></li>
      <li><a href="products.php"><img src="images/inventory.png" alt=""> <span>Shop</span></a></li>
      <li><a href="schedule.php"><img src="images/appointments.png" alt=""> <span>Services</span></a></li>
      <li><a href="view_order_history.php"><img src="images/users.png" alt=""> <span>View Orders</span></a></li>
      <li><a href="view_appointments.php"><img src="images/users.png" alt=""> <span>View Appointments</span></a></li>
      <li><a href="settings.php"><img src="images/settings.png" alt=""> <span>Settings</span></a></li>
    </ul>
    <div class="logout">
      <a href="login.php">Logout</a>
    </div>
  </div>
  <div class="main-content">
    <div class="topbar">
      <div class="cart-title">Your Cart</div>
      <div class="topbar-icons">
        <a href="cart.php" class="icon-link active" title="Cart">🛒</a>
        <a href="delivery.php" class="icon-link" title="Delivery">🚚</a>
      </div>
    </div>
    <div class="cart-container" id="cartContainer">
      <?php if (empty($cart_items)) { ?>
          <div class="empty-cart">Your cart is empty.</div>
      <?php } else { 
        $total = 0;
        foreach ($cart_items as $item) {
          $imgPath = 'images/default.png';
          if (isset($item['img']) && is_string($item['img'])) {
            if (strpos($item['img'], 'uploads/') === 0 || strpos($item['img'], 'images/') === 0) {
              $imgPath = $item['img'];
            } else {
              $imgPath = 'images/' . $item['img'];
            }
          }
          $itemTotal = $item['price'] * $item['quantity'];
          $total += $itemTotal;
      ?>
        <div class="cart-item">
          <img src="<?= htmlspecialchars($imgPath) ?>" alt="<?= htmlspecialchars($item['name']) ?>">
          <div class="item-details">
            <div class="item-name"><?= htmlspecialchars($item['name']) ?></div>
            <div class="item-desc"><?= htmlspecialchars($item['description']) ?></div>
            <div class="item-price">₱<?= number_format($item['price'], 2) ?></div>
            <div class="quantity-control">
              <button class="quantity-btn minus-btn" data-id="<?= $item['id'] ?>">-</button>
              <span class="quantity-number" id="quantity-<?= $item['id'] ?>"><?= $item['quantity'] ?></span>
              <button class="quantity-btn plus-btn" data-id="<?= $item['id'] ?>">+</button>
            </div>
          </div>
          <div class="action-buttons">
            <button class="remove-btn" data-id="<?= $item['id'] ?>">Remove</button>
            <form method="POST" action="checkout.php">
              <input type="hidden" name="product_id" value="<?= $item['id'] ?>">
              <input type="hidden" name="quantity" value="<?= $item['quantity'] ?>">
              <button class="checkout-btn">Checkout</button>
            </form>
          </div>
        </div>
      <?php } ?>
        <div class="cart-summary">Total: ₱<?= number_format($total, 2) ?></div>
      <?php } ?>
    </div>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.querySelectorAll('.quantity-btn').forEach(btn => {
  btn.addEventListener('click', function(e) {
    e.preventDefault();
    const productId = this.getAttribute('data-id');
    const action = this.classList.contains('plus-btn') ? 'increase' : 'decrease';
    fetch('update_cart.php', {
      method: 'POST',
      headers: {'Content-Type': 'application/x-www-form-urlencoded'},
      body: `product_id=${productId}&action=${action}`
    })
    .then(res => res.json())
    .then(data => {
      if (data.success) {
        document.getElementById('quantity-' + productId).textContent = data.newQuantity;
        location.reload();
      } else {
        alert(data.message || 'Failed to update cart.');
      }
    });
  });
});

document.querySelectorAll('.remove-btn').forEach(btn => {
  btn.addEventListener('click', function(e) {
    e.preventDefault();
    const productId = this.getAttribute('data-id');
    Swal.fire({
      title: 'Remove Item',
      text: 'Remove this item from your cart?',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Yes, remove it',
      cancelButtonText: 'Cancel',
      reverseButtons: true
    }).then((result) => {
      if (result.isConfirmed) {
        fetch('remove_from_cart.php', {
          method: 'POST',
          headers: {'Content-Type': 'application/x-www-form-urlencoded'},
          body: `product_id=${productId}`
        })
        .then(res => res.json())
        .then(data => {
          if (data.success) {
            this.closest('.cart-item').remove();
            location.reload();
          } else {
            Swal.fire('Error', data.message || 'Failed to remove item.', 'error');
          }
        });
      }
    });
  });
});
</script>
</body>
</html>
