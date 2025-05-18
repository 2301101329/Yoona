<?php
require 'includes/db.php';

try {
    $sql = "SELECT id, name, price, img, description, quantity FROM products";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
    die();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Yoona Shop - Products</title>
  <link rel="icon" href="images/yoona.png">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
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
    /* Sidebar */
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
    .sidebar .icon-links {
      display: flex;
      flex-direction: row;
      gap: 18px;
      margin-bottom: 30px;
      justify-content: center;
      width: 100%;
    }
    .sidebar .icon-link {
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
    .sidebar .icon-link:hover {
      color: var(--primary);
      background: var(--accent);
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
    /* Main Content */
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
    .topbar .shop-title {
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
    .topbar .icon-link:hover {
      color: var(--primary);
      background: var(--accent);
    }
    /* Product Grid */
    .products-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(270px, 1fr));
      gap: 32px;
      padding: 0 32px;
    }
    .product-card {
      background-color: var(--card-bg);
      border-radius: 18px;
      box-shadow: var(--card-shadow);
      padding: 24px 18px 18px 18px;
      display: flex;
      flex-direction: column;
      align-items: center;
      transition: transform 0.3s, box-shadow 0.3s;
      position: relative;
      min-height: 420px;
    }
    .product-card:hover {
      transform: scale(1.04) translateY(-4px);
      box-shadow: 0 8px 24px rgba(24,49,91,0.13);
      background: #eef2ff;
    }
    .product-card img {
      width: 180px;
      height: 180px;
      object-fit: contain;
      margin-bottom: 15px;
      border-radius: 12px;
      background: #f8f9fc;
      box-shadow: 0 2px 8px rgba(0,0,0,0.04);
    }
    .product-card .name {
      font-size: 1.2rem;
      font-weight: 600;
      color: var(--primary);
      margin-bottom: 8px;
      text-align: center;
    }
    .product-card .price {
      font-size: 1.1rem;
      font-weight: bold;
      color: #374151;
      margin-bottom: 8px;
    }
    .product-card .stock {
      font-size: 14px;
      color: #6b7280;
      margin-bottom: 4px;
    }
    .product-card .desc {
      font-size: 14px;
      color: #6b7280;
      text-align: center;
      margin-bottom: 10px;
      min-height: 38px;
    }
    .actions {
      display: flex;
      align-items: center;
      gap: 12px;
      margin-top: auto;
    }
    .add-cart-btn {
      background-color: var(--primary);
      border: none;
      font-size: 14px;
      color: #fff;
      cursor: pointer;
      padding: 8px 18px;
      border-radius: 8px;
      font-weight: 500;
      transition: background 0.2s;
    }
    .add-cart-btn:hover {
      background: var(--accent);
      color: var(--primary);
    }
    .checkout-btn {
      background-color: var(--accent);
      color: var(--primary);
      border: none;
      padding: 8px 18px;
      border-radius: 8px;
      cursor: pointer;
      font-size: 14px;
      font-weight: 500;
      transition: background 0.2s;
    }
    .checkout-btn:hover {
      background: var(--primary);
      color: #fff;
    }
    /* Modal */
    .modal {
      display: none;
      position: fixed;
      z-index: 2000;
      left: 0;
      top: 0;
      width: 100%;
      height: 100%;
      overflow: auto;
      background-color: rgba(0,0,0,0.5);
    }
    .modal-content {
      background-color: #fff;
      margin: 5% auto;
      padding: 30px;
      border-radius: 15px;
      width: 400px;
      text-align: center;
      position: relative;
      box-shadow: 0 4px 20px rgba(0,0,0,0.1);
    }
    .modal-content img {
      width: 150px;
      height: 150px;
      object-fit: contain;
      margin-bottom: 15px;
    }
    .close {
      color: #aaa;
      position: absolute;
      right: 20px;
      top: 10px;
      font-size: 24px;
      font-weight: bold;
      cursor: pointer;
    }
    .modal-price {
      font-size: 1.4rem;
      font-weight: bold;
      color: var(--primary);
      margin: 10px 0;
    }
    .modal-stock {
      font-size: 0.9rem;
      color: #666;
      margin: 5px 0;
    }
    .modal-desc {
      font-size: 0.95rem;
      color: #555;
      margin: 15px 0;
      line-height: 1.4;
    }
    .modal-actions {
      display: flex;
      gap: 14px;
      justify-content: center;
      margin-top: 20px;
    }
    .modal-actions .checkout-btn {
      background-color: var(--accent);
      color: var(--primary);
      border: none;
      padding: 12px 0;
      border-radius: 8px;
      font-size: 1.1rem;
      font-weight: 600;
      width: 100%;
      transition: background 0.2s, color 0.2s;
      box-shadow: 0 2px 8px rgba(24,49,91,0.07);
      letter-spacing: 0.5px;
    }
    .modal-actions .checkout-btn:hover, .modal-actions .checkout-btn:focus {
      background: var(--primary);
      color: #fff;
    }
    .quantity-control {
      display: flex;
      justify-content: center;
      align-items: center;
      gap: 15px;
      margin: 20px 0;
      background: #f8f9fc;
      padding: 10px;
      border-radius: 10px;
    }
    .quantity-control button {
      background-color: var(--primary);
      color: white;
      border: none;
      width: 35px;
      height: 35px;
      border-radius: 50%;
      font-size: 18px;
      cursor: pointer;
      transition: all 0.2s;
    }
    .quantity-control button:hover {
      background: var(--accent);
      color: var(--primary);
    }
    .quantity-control button:disabled {
      background: #ccc;
      cursor: not-allowed;
    }
    .quantity-control span {
      font-size: 1.2rem;
      font-weight: bold;
      width: 40px;
      text-align: center;
    }
    @media (max-width: 1100px) {
      .products-grid {
        grid-template-columns: 1fr 1fr;
      }
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
      .products-grid {
        grid-template-columns: 1fr;
        padding: 0 8px;
      }
      .main-content {
        padding: 0;
      }
    }
    /* Modal Add to Cart Button Styling */
    .modal-add-cart-btn {
      background-color: var(--primary);
      color: #fff;
      border: none;
      padding: 12px 0;
      border-radius: 8px;
      font-size: 1.1rem;
      font-weight: 600;
      width: 100%;
      margin-bottom: 0;
      transition: background 0.2s, color 0.2s;
      box-shadow: 0 2px 8px rgba(24,49,91,0.07);
      letter-spacing: 0.5px;
    }
    .modal-add-cart-btn:hover, .modal-add-cart-btn:focus {
      background: var(--accent);
      color: var(--primary);
    }
    @media (max-width: 500px) {
      .modal-content {
        width: 95vw;
        padding: 10px;
      }
      .modal-actions {
        flex-direction: column;
        gap: 10px;
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
            <li><a href="products.php" class="active"><img src="images/shop.png" alt=""> <span>Shop</span></a></li>
            <li><a href="schedule.php"><img src="images/services.png" alt=""> <span>Services</span></a></li>
            <li><a href="view_order_history.php"><img src="images/view-order.png" alt=""> <span>View Orders</span></a></li>
            <li><a href="view_appointments.php"><img src="images/appointments.png" alt=""> <span>View Appointments</span></a></li>
            <li><a href="settings.php"><img src="images/settings.png" alt=""> <span>Settings</span></a></li>
    </ul>
    <div class="logout">
      <a href="login.php">Logout</a>
    </div>
  </div>
  <div class="main-content">
    <div class="topbar">
      <div class="shop-title">Shop Products</div>
      <div class="topbar-icons">
        <a href="cart.php" class="icon-link" title="Cart">🛒</a>
        <a href="delivery.php" class="icon-link" title="Delivery">🚚</a>
      </div>
    </div>
    <div class="products-grid">
      <?php
      if ($products) {
        foreach ($products as $p) {
          $imgPath = 'images/default.png';
          if (isset($p['img']) && is_string($p['img'])) {
            if (strpos($p['img'], 'uploads/') === 0 || strpos($p['img'], 'images/') === 0) {
              $imgPath = $p['img'];
            } else {
              $imgPath = 'images/' . $p['img'];
            }
          }

          $name = htmlspecialchars($p['name']);
          $price = htmlspecialchars($p['price']);
          $description = htmlspecialchars($p['description']);
          $id = htmlspecialchars($p['id']);
          $quantity = (int)$p['quantity'];

          echo "
          <div class='product-card'>
            <img src='" . htmlspecialchars($imgPath) . "' alt='{$name}' />
            <div class='name'>{$name}</div>
            <div class='price'>₱{$price}</div>
            <div class='stock'>Available Stock: {$quantity}</div>
            <div class='desc'>{$description}</div>
            <div class='actions'>
              <button class='add-cart-btn' 
                data-id='{$id}' 
                data-name='{$name}' 
                data-price='{$price}' 
                data-img='" . htmlspecialchars($imgPath) . "'
                data-stock='{$quantity}'
                data-desc='" . htmlspecialchars($description) . "'>
                Add to Cart
              </button>
              <form method='POST' action='checkout.php' style='margin:0;'>
                <input type='hidden' name='product_id' value='" . $id . "'>
                <input type='hidden' name='quantity' value='1'>
                <button type='submit' class='checkout-btn'>Checkout</button>
              </form>
            </div>
          </div>
          ";
        }
      } else {
        echo "No products available.";
      }
      ?>
    </div>
  </div>
</div>
<!-- Modal -->
<div id="productModal" class="modal">
  <div class="modal-content">
    <span class="close">&times;</span>
    <img id="modalImg" src="" alt="">
    <h3 id="modalName"></h3>
    <p id="modalPrice" class="modal-price"></p>
    <p id="modalStock" class="modal-stock"></p>
    <p id="modalDesc" class="modal-desc"></p>
    <div class="quantity-control">
      <button id="decrease">-</button>
      <span id="quantity">1</span>
      <button id="increase">+</button>
    </div>
    <div class="modal-actions">
      <button id="finalAddBtn" class="modal-add-cart-btn">Add to Cart</button>
      <form method='POST' action='checkout.php' style='margin:0; display:inline-block; width:100%;'>
        <input type='hidden' name='product_id' id='modalProductId'>
        <input type='hidden' name='quantity' id='modalQuantity'>
        <button type='submit' class='checkout-btn' style='width:100%;'>Checkout Now</button>
      </form>
    </div>
  </div>
</div>
<script>
  var currentProduct = {};

  $(document).on('click', '.add-cart-btn', function() {
    var productData = $(this).data();
    currentProduct = productData;

    $('#modalImg').attr('src', productData.img);
    $('#modalName').text(productData.name);
    $('#modalPrice').text('₱' + productData.price);
    $('#modalStock').text('Available Stock: ' + productData.stock);
    $('#modalDesc').text(productData.desc);
    $('#modalProductId').val(productData.id);
    $('#quantity').text(1);
    $('#modalQuantity').val(1);

    // Reset button states
    $('#decrease').prop('disabled', true);
    $('#increase').prop('disabled', productData.stock <= 1);

    $('#productModal').fadeIn();
  });

  $('#increase').click(function() {
    var qty = parseInt($('#quantity').text());
    var stock = currentProduct.stock;
    
    if (qty < stock) {
      $('#quantity').text(qty + 1);
      $('#modalQuantity').val(qty + 1);
      $('#decrease').prop('disabled', false);
      
      if (qty + 1 >= stock) {
        $(this).prop('disabled', true);
      }
    }
  });

  $('#decrease').click(function() {
    var qty = parseInt($('#quantity').text());
    if (qty > 1) {
      $('#quantity').text(qty - 1);
      $('#modalQuantity').val(qty - 1);
      $('#increase').prop('disabled', false);
      
      if (qty - 1 <= 1) {
        $(this).prop('disabled', true);
      }
    }
  });

  $('.close').click(function() {
    $('#productModal').fadeOut();
  });

  $('#finalAddBtn').click(function() {
    var finalQuantity = parseInt($('#quantity').text());
    
    if (finalQuantity > currentProduct.stock) {
      Swal.fire({
        title: 'Error!',
        text: 'Cannot add more items than available stock.',
        icon: 'error',
        showConfirmButton: true
      });
      return;
    }

    $.ajax({
      url: 'add_to_cart.php',
      type: 'POST',
      data: {
        product_id: currentProduct.id,
        product_name: currentProduct.name,
        price: currentProduct.price,
        img: currentProduct.img,
        quantity: finalQuantity
      },
      success: function(response) {
        Swal.fire({
          title: 'Added to Cart!',
          text: `${currentProduct.name} (x${finalQuantity}) has been added successfully.`,
          icon: 'success',
          showConfirmButton: false,
          timer: 2000,
          toast: true,
          position: 'top'
        });

        $('#productModal').fadeOut();
      },
      error: function() {
        Swal.fire({
          title: 'Error!',
          text: 'Something went wrong. Please try again.',
          icon: 'error',
          showConfirmButton: true
        });
      }
    });
  });
</script>
</body>
</html>

