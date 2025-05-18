<?php
session_start();
require 'includes/db.php';
date_default_timezone_set('Asia/Manila');

if (!isset($_SESSION['user_id'])) {
    echo "You must be logged in to access the checkout page.";
    exit;
}

$user_id = $_SESSION['user_id'];
$email = $_SESSION['email'] ?? '';
$stmt = $pdo->prepare("SELECT fullname AS name, email FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo "User not found in the database.";
    exit;
}

$product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
$quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;

$stmt = $pdo->prepare("SELECT name, price, quantity, img FROM products WHERE id = ?");
$stmt->execute([$product_id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    echo "Product not found.";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['address'], $_POST['phone'])) {
    $address = trim($_POST['address']);
    $phone = trim($_POST['phone']);

    // Server-side validation for phone and address
    $phone_valid = preg_match('/^(09|\+639)\d{9}$/', $phone) || preg_match('/^\d{10,13}$/', $phone);
    $address_valid = strlen($address) >= 8;
    if (!$phone_valid || !$address_valid) {
        echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>";
        echo "<script>document.addEventListener('DOMContentLoaded', function() { Swal.fire({ icon: 'error', title: 'Invalid Input', text: '" .
            (!$phone_valid ? 'Please enter a valid phone number. ' : '') .
            (!$address_valid ? 'Please enter a valid address (at least 8 characters).' : '') .
            "', confirmButtonColor: '#18315B' }); });</script>";
        exit;
    }

    if ($quantity > $product['quantity']) {
        echo "<script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    icon: 'error',
                    title: 'Checkout Failed',
                    text: 'Quantity exceeds available stock!',
                    confirmButtonColor: '#18315B'
                }).then(() => {
                    window.history.back();
                });
            });
        </script>";
        echo '<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>';
        exit;
    }

    $total_price = $product['price'] * $quantity;
    $insert = $pdo->prepare("INSERT INTO orders (user_id, product_id, quantity, address, phone, total_price, created_at)
                             VALUES (?, ?, ?, ?, ?, ?, NOW())");
    $insert->execute([$user_id, $product_id, $quantity, $address, $phone, $total_price]);

    // Update product quantity
    $new_quantity = $product['quantity'] - $quantity;
    $update = $pdo->prepare("UPDATE products SET quantity = ? WHERE id = ?");
    $update->execute([$new_quantity, $product_id]);

    $orderDate = date("F j, Y - g:i A");

    $imagePath = 'images/default.png';
    if (!empty($product['img'])) {
        if (strpos($product['img'], 'uploads/') === 0 || strpos($product['img'], 'images/') === 0) {
            $imagePath = $product['img'];
        } else {
            $imagePath = 'images/' . $product['img'];
        }
    }

    echo '
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>Receipt - Yoona Shop</title>
        <link rel="icon" href="images/yoona.png">
        <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
        <style>
            :root {
                --primary: #18315B;
                --accent: #FFD166;
                --main-bg: #F3E9D2;
                --card-bg: #fff;
                --text-primary: #374151;
                --text-secondary: #4b5563;
            }
            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
                font-family: "Poppins", sans-serif;
            }
            body {
                background: var(--main-bg);
                min-height: 100vh;
                display: flex;
                justify-content: center;
                align-items: center;
                padding: 40px 20px;
            }
            .receipt-card {
                background: var(--card-bg);
                border-radius: 16px;
                box-shadow: 0 8px 20px rgba(0,0,0,0.1);
                padding: 40px;
                max-width: 600px;
                width: 100%;
            }
            .receipt-header {
                text-align: center;
                margin-bottom: 30px;
            }
            .receipt-header img {
                width: 80px;
                height: 80px;
                margin-bottom: 15px;
            }
            .receipt-header h2 {
                color: var(--primary);
                font-size: 24px;
                margin-bottom: 5px;
            }
            .receipt-header p {
                color: var(--text-secondary);
                font-size: 14px;
            }
            .section {
                background: #f8fafc;
                border-radius: 12px;
                padding: 20px;
                margin-bottom: 20px;
            }
            .section h3 {
                color: var(--primary);
                font-size: 18px;
                margin-bottom: 15px;
                border-bottom: 2px solid var(--accent);
                padding-bottom: 8px;
            }
            .section p {
                margin: 8px 0;
                color: var(--text-secondary);
                display: flex;
                justify-content: space-between;
            }
            .section p strong {
                color: var(--text-primary);
            }
            .product-img {
                width: 100%;
                max-height: 300px;
                object-fit: contain;
                border-radius: 12px;
                margin-bottom: 20px;
                background: #fff;
                padding: 10px;
            }
            .total {
                font-size: 24px;
                font-weight: 600;
                color: var(--primary);
                margin-top: 15px;
                padding-top: 15px;
                border-top: 2px solid var(--accent);
            }
            .back-btn {
                display: block;
                text-align: center;
                margin-top: 30px;
                background: var(--primary);
                color: white;
                padding: 14px 24px;
                border-radius: 8px;
                text-decoration: none;
                font-weight: 500;
                transition: background 0.3s;
            }
            .back-btn:hover {
                background: #142a4d;
            }
        </style>
    </head>
    <body>
    <div class="receipt-card">
        <div class="receipt-header">
            <img src="images/yoona.png" alt="Yoona Logo">
            <h2>Order Receipt</h2>
            <p>Thank you for your purchase!</p>
        </div>
        <div class="section">
            <img src="' . htmlspecialchars($imagePath) . '" alt="' . htmlspecialchars($product['name']) . '" class="product-img" />
            <h3>Product Details</h3>
            <p><strong>Product Name:</strong> ' . htmlspecialchars($product['name']) . '</p>
            <p><strong>Unit Price:</strong> ₱' . number_format($product['price'], 2) . '</p>
            <p><strong>Quantity:</strong> ' . $quantity . '</p>
            <p class="total"><strong>Total Amount:</strong> ₱' . number_format($total_price, 2) . '</p>
        </div>
        <div class="section">
            <h3>Customer Information</h3>
            <p><strong>Name:</strong> ' . htmlspecialchars($user['name']) . '</p>
            <p><strong>Email:</strong> ' . htmlspecialchars($user['email']) . '</p>
            <p><strong>Phone:</strong> ' . htmlspecialchars($phone) . '</p>
            <p><strong>Delivery Address:</strong> ' . htmlspecialchars($address) . '</p>
            <p><strong>Order Date:</strong> ' . $orderDate . '</p>
        </div>
        <a href="products.php" class="back-btn">Return to Shop</a>
    </div>
    </body>
    </html>';
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Checkout - Yoona Shop</title>
    <link rel="icon" href="images/yoona.png">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root {
            --primary: #18315B;
            --accent: #FFD166;
            --main-bg: #F3E9D2;
            --card-bg: #fff;
            --text-primary: #374151;
            --text-secondary: #4b5563;
            --error: #dc2626;
            --success: #059669;
        }
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: "Poppins", sans-serif;
        }
        body {
            background: var(--main-bg);
            min-height: 100vh;
            padding: 40px 20px;
        }
        .container {
            max-width: 1000px;
            margin: 0 auto;
            background: var(--card-bg);
            border-radius: 16px;
            box-shadow: 0 8px 20px rgba(0,0,0,0.1);
            padding: 40px;
            position: relative;
        }
        .return-btn {
            position: absolute;
            top: 20px;
            left: 20px;
            text-decoration: none;
            color: var(--primary);
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: color 0.3s;
        }
        .return-btn:hover {
            color: var(--accent);
        }
        .checkout-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            margin-top: 40px;
        }
        .product-card {
            background: #f8fafc;
            border-radius: 12px;
            padding: 30px;
            text-align: center;
        }
        .logo {
            width: 100px;
            height: 100px;
            margin-bottom: 15px;
        }
        .brand-name {
            font-size: 24px;
            font-weight: 600;
            color: var(--primary);
            margin-bottom: 30px;
        }
        .product-img {
            width: 100%;
            max-height: 300px;
            object-fit: contain;
            border-radius: 12px;
            margin-bottom: 20px;
            background: #fff;
            padding: 10px;
        }
        .product-info {
            text-align: left;
        }
        .product-info h3 {
            color: var(--primary);
            font-size: 20px;
            margin-bottom: 10px;
        }
        .product-info p {
            color: var(--text-secondary);
            margin-bottom: 8px;
        }
        .price {
            font-size: 24px;
            font-weight: 600;
            color: var(--primary);
            margin: 15px 0;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            color: var(--text-primary);
            margin-bottom: 8px;
            font-weight: 500;
        }
        .form-control {
            width: 100%;
            padding: 12px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s;
        }
        .form-control:focus {
            outline: none;
            border-color: var(--primary);
        }
        .quantity-control {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 20px;
        }
        .quantity-btn {
            background: var(--primary);
            color: white;
            border: none;
            width: 36px;
            height: 36px;
            border-radius: 8px;
            font-size: 18px;
            cursor: pointer;
            transition: background 0.3s;
        }
        .quantity-btn:hover {
            background: #142a4d;
        }
        .quantity-input {
            width: 60px;
            text-align: center;
            font-size: 18px;
            font-weight: 500;
            color: var(--primary);
        }
        .submit-btn {
            background: var(--primary);
            color: white;
            border: none;
            padding: 14px 24px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            width: 100%;
            transition: background 0.3s;
        }
        .submit-btn:hover {
            background: #142a4d;
        }
        .total-section {
            background: #f8fafc;
            border-radius: 12px;
            padding: 20px;
            margin-top: 20px;
        }
        .total-section p {
            display: flex;
            justify-content: space-between;
            margin: 10px 0;
            color: var(--text-secondary);
        }
        .total-section .final-total {
            font-size: 24px;
            font-weight: 600;
            color: var(--primary);
            margin-top: 15px;
            padding-top: 15px;
            border-top: 2px solid var(--accent);
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="products.php" class="return-btn">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M19 12H5M12 19l-7-7 7-7"/>
            </svg>
            Return to Products
        </a>

        <div class="checkout-grid">
            <div class="product-card">
                <img src="images/yoona.png" alt="Yoona Logo" class="logo">
                <p class="brand-name">Yoona Computer Trading</p>
                
                <?php
                $imagePath = 'images/default.png';
                if (!empty($product['img'])) {
                    if (strpos($product['img'], 'uploads/') === 0 || strpos($product['img'], 'images/') === 0) {
                        $imagePath = $product['img'];
                    } else {
                        $imagePath = 'images/' . $product['img'];
                    }
                }
                ?>
                <img src="<?php echo htmlspecialchars($imagePath); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="product-img">
                
                <div class="product-info">
                    <h3><?php echo htmlspecialchars($product['name']); ?></h3>
                    <p class="price">₱<?php echo number_format($product['price'], 2); ?></p>
                    <p>Available Stock: <?php echo $product['quantity']; ?></p>
                </div>
            </div>

            <div class="checkout-form">
                <h2 style="color: var(--primary); margin-bottom: 30px;">Checkout Information</h2>
                <form method="POST" action="" onsubmit="return validateCheckoutForm();">
                    <input type="hidden" name="product_id" value="<?php echo $product_id; ?>">
                    
                    <div class="form-group">
                        <label for="quantity">Quantity</label>
                        <div class="quantity-control">
                            <button type="button" class="quantity-btn" onclick="decrementQuantity()">-</button>
                            <input type="number" id="quantity" name="quantity" class="quantity-input" value="<?php echo $quantity; ?>" min="1" max="<?php echo $product['quantity']; ?>" readonly>
                            <button type="button" class="quantity-btn" onclick="incrementQuantity()">+</button>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="phone">Phone Number</label>
                        <input type="tel" id="phone" name="phone" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label for="address">Delivery Address</label>
                        <textarea id="address" name="address" class="form-control" rows="3" required></textarea>
                    </div>

                    <div class="total-section">
                        <p><strong>Subtotal:</strong> ₱<?php echo number_format($product['price'] * $quantity, 2); ?></p>
                        <p><strong>Shipping:</strong> Free</p>
                        <p class="final-total"><strong>Total:</strong> ₱<?php echo number_format($product['price'] * $quantity, 2); ?></p>
                    </div>

                    <button type="submit" class="submit-btn">Place Order</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        function incrementQuantity() {
            const input = document.getElementById('quantity');
            const max = parseInt(input.getAttribute('max'));
            const currentValue = parseInt(input.value);
            if (currentValue < max) {
                input.value = currentValue + 1;
            }
        }

        function decrementQuantity() {
            const input = document.getElementById('quantity');
            const currentValue = parseInt(input.value);
            if (currentValue > 1) {
                input.value = currentValue - 1;
            }
        }

        function validateCheckoutForm() {
            const phone = document.getElementById('phone').value.trim();
            const address = document.getElementById('address').value.trim();
            let phoneValid = /^(09|\+639)\d{9}$/.test(phone) || /^\d{10,13}$/.test(phone);
            let addressValid = address.length >= 8;
            let errorMsg = '';
            if (!phoneValid) errorMsg += 'Please enter a valid phone number. ';
            if (!addressValid) errorMsg += 'Please enter a valid address (at least 8 characters).';
            if (errorMsg) {
                Swal.fire({
                    icon: 'error',
                    title: 'Invalid Input',
                    text: errorMsg,
                    confirmButtonColor: '#18315B'
                });
                return false;
            }
            return true;
        }
    </script>
</body>
</html>
