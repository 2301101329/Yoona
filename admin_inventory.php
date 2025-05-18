<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}
require_once 'includes/db.php';

// Fetch admin full name for topbar
$stmt = $pdo->prepare("SELECT fullname FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$admin_fullname = $stmt->fetchColumn();

// Create uploads folder if not exists
if (!is_dir('uploads')) {
    mkdir('uploads', 0777, true);
}

// Handle delete action
if (isset($_GET['delete'])) {
    $delete_id = intval($_GET['delete']);
    $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
    $stmt->execute([$delete_id]);
    header("Location: admin_inventory.php");
    exit;
}

// Handle add product action
if (isset($_POST['add_product'])) {
    $name = $_POST['name'];
    $price = floatval($_POST['price']);
    $quantity = intval($_POST['quantity']);
    $description = $_POST['description'];

    $img_name = '';

    // Handle image upload
    if (isset($_FILES['img']) && $_FILES['img']['error'] === UPLOAD_ERR_OK) {
        $originalName = $_FILES['img']['name'];
        $tmpName = $_FILES['img']['tmp_name'];
        $extension = pathinfo($originalName, PATHINFO_EXTENSION);

        $newFileName = uniqid() . '.' . $extension;
        $uploadPath = 'uploads/' . $newFileName;

        if (move_uploaded_file($tmpName, $uploadPath)) {
            $img_name = $uploadPath;
        }
    }

    $stmt = $pdo->prepare("INSERT INTO products (name, price, quantity, img, description) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$name, $price, $quantity, $img_name, $description]);

    header("Location: admin_inventory.php?added=1");
    exit;
}

// Fetch all products
$stmt = $pdo->prepare("SELECT * FROM products ORDER BY id ASC");
$stmt->execute();
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Inventory</title>
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
        /* Main Content */
        .main-content {
            flex: 1;
            margin-left: 260px;
            background: var(--main-bg);
            min-height: 100vh;
        }
        /* Topbar */
        .topbar {
            background: var(--accent);
            display: flex;
            align-items: center;
            justify-content: flex-start;
            padding: 0 32px;
            height: 80px;
            border-bottom-left-radius: 18px;
            border-bottom-right-radius: 18px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.04);
        }
        .topbar .admin {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-left: auto;
        }
        .topbar .admin-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: var(--primary);
            box-shadow: 0 1px 4px rgba(0,0,0,0.08);
        }
        .topbar .admin span {
            font-weight: 600;
            color: var(--primary);
        }
        /* Content Area */
        .content {
            padding: 12px 32px 0 32px;
        }
        /* Inventory Table Styles */
        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0 10px;
            background: #fff;
            box-shadow: 0 2px 8px rgba(24,49,91,0.07);
            border-radius: 16px;
            overflow: hidden;
            margin-top: 30px;
        }
        th, td {
            padding: 15px 12px;
            text-align: center;
        }
        th {
            background: #18315B;
            color: #FFD166;
            font-weight: 600;
            font-size: 1rem;
            border-top: 1px solid #18315B;
        }
        td {
            background: #fff;
            border-radius: 8px;
            font-size: 1rem;
            color: #222;
            box-shadow: 0 1px 4px rgba(24,49,91,0.03);
        }
        img {
            width: 50px;
            height: auto;
        }
        .action-btn {
            padding: 8px 18px;
            margin: 2px;
            text-decoration: none;
            background-color: #FFD166;
            color: #18315B;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            border: none;
            box-shadow: 0 2px 8px rgba(24,49,91,0.07);
            transition: background 0.2s, color 0.2s;
        }
        .action-btn.delete {
            background-color: #FFE5E5;
            color: #D7263D;
        }
        .action-btn.delete:hover {
            background-color: #D7263D;
            color: #fff;
        }
        .action-btn:hover {
            background: #18315B;
            color: #FFD166;
        }
        .add-btn {
            margin: 30px auto;
            display: block;
            background-color: #FFD166;
            color: #18315B;
            padding: 15px 30px;
            font-size: 1.1rem;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            box-shadow: 0 2px 8px rgba(24,49,91,0.07);
            transition: background 0.2s, color 0.2s;
        }
        .add-btn:hover {
            background-color: #18315B;
            color: #FFD166;
        }
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.5);
        }
        .modal-content {
            background-color: #fff;
            margin: 10% auto;
            padding: 20px;
            border-radius: 10px;
            width: 90%;
            max-width: 500px;
            position: relative;
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
            text-align: center;
        }
        .close {
            color: #aaa;
            position: absolute;
            top: 10px;
            right: 20px;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }
        .modal-content input[type="text"],
        .modal-content input[type="number"],
        .modal-content input[type="file"],
        .modal-content textarea {
            width: 90%;
            padding: 10px;
            margin: 10px 0;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 16px;
        }
        .modal-content button {
            background: #FFD166;
            color: #18315B;
            padding: 12px 25px;
            font-size: 1.1rem;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            margin-top: 15px;
            box-shadow: 0 2px 8px rgba(24,49,91,0.07);
            transition: background 0.2s, color 0.2s;
        }
        .modal-content form button {
            margin: 10px 5px;
            padding: 10px 20px;
        }
        .modal-content button:hover {
            background: #18315B;
            color: #FFD166;
        }
        @media (max-width: 1100px) {
            .dashboard {
                flex-direction: column;
            }
            .main-content {
                margin-left: 0;
            }
        }
        @media (max-width: 900px) {
            .sidebar {
                width: 70px;
                padding: 16px 0;
            }
            .sidebar .logo span, .sidebar .menu li span, .sidebar .logout {
                display: none;
            }
            .main-content {
                margin-left: 70px;
            }
            .topbar {
                padding: 0 10px;
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
            <li><a href="admin_main.php"><img src="images/dashboard.png" alt=""> <span>Dashboard</span></a></li>
            <li><a href="admin_inventory.php" class="active"><img src="images/inventory.png" alt=""> <span>Inventory</span></a></li>
            <li><a href="admin_orders.php"><img src="images/orders.png" alt=""> <span>Orders</span></a></li>
            <li><a href="admin_appointments.php"><img src="images/appointments.png" alt=""> <span>Appointments</span></a></li>
            <li><a href="admin_users.php"><img src="images/users.png" alt=""> <span>Users</span></a></li>
            <li><a href="admin_settings.php"><img src="images/settings.png" alt=""> <span>Settings</span></a></li>
        </ul>
        <div class="logout">
            <a href="login.php">Logout</a>
        </div>
    </div>
    <div class="main-content">
        <div class="topbar">
            <span style="font-size:1.5rem;font-weight:700;color:var(--primary);margin-right:32px;">Admin Inventory</span>
            <div class="admin">
                <div class="admin-avatar"><span>👤</span></div>
                <span><?php echo htmlspecialchars($admin_fullname); ?></span>
            </div>
        </div>
        <div class="content">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th></th>
                        <th>Product Name</th>
                        <th>Price (₱)</th>
                        <th>Stocks Left</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($products) > 0): ?>
                        <?php foreach ($products as $row): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['id']); ?></td>
                                <td>
                                <?php
                                $img = $row['img'];
                                if (!empty($img)) {
                                    if (strpos($img, 'uploads/') === 0 || strpos($img, 'images/') === 0) {
                                        $img = $img;
                                    } else {
                                        $img = 'images/' . $img;
                                    }
                                } else {
                                    $img = 'images/default.png';
                                }
                                ?>
                                <img src="<?= htmlspecialchars($img); ?>" alt="Product Image">
                                </td>
                                <td><?= htmlspecialchars($row['name']); ?></td>
                                <td><?= number_format($row['price'], 2); ?></td>
                                <td><?= htmlspecialchars($row['quantity']); ?></td>
                                <td>
                                    <a class="action-btn" href="edit_product.php?id=<?= $row['id']; ?>">Edit</a>
                                    <a class="action-btn delete" href="javascript:void(0);" onclick="showDeleteModal(<?= $row['id']; ?>)">Delete</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6">No products found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
            <button class="add-btn" onclick="document.getElementById('addModal').style.display='block'">Add New Product</button>
            <!-- Add Product Modal -->
            <div id="addModal" class="modal">
                <div class="modal-content">
                    <span class="close" onclick="document.getElementById('addModal').style.display='none'">&times;</span>
                    <h2>Add New Product</h2>
                    <form method="POST" enctype="multipart/form-data">
                        <input type="file" name="img" required><br>
                        <input type="text" name="name" placeholder="Product Name" required><br>
                        <input type="number" step="0.01" name="price" placeholder="Price (₱)" required><br>
                        <input type="number" name="quantity" placeholder="Quantity" required><br>
                        <textarea name="description" placeholder="Product Description" rows="4" required></textarea><br>
                        <button type="submit" name="add_product">Add Product</button>
                    </form>
                </div>
            </div>
            <!-- Delete Confirmation Modal -->
            <div id="deleteModal" class="modal">
                <div class="modal-content">
                    <h2>Are you sure you want to delete this item?</h2>
                    <form method="GET" id="deleteForm">
                        <input type="hidden" name="delete" id="deleteId">
                        <button type="submit" style="background-color: #e74c3c;">Yes, Delete</button>
                        <button type="button" onclick="closeDeleteModal()">Cancel</button>
                    </form>
                </div>
            </div>
            <!-- Success Modal -->
            <?php if (isset($_GET['added']) && $_GET['added'] == 1): ?>
            <div id="successModal" class="modal" style="display:block;">
                <div class="modal-content">
                    <h2>Product added successfully!</h2>
                    <button onclick="document.getElementById('successModal').style.display='none'">Close</button>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<script>
function showDeleteModal(productId) {
    document.getElementById('deleteId').value = productId;
    document.getElementById('deleteModal').style.display = 'block';
}
function closeDeleteModal() {
    document.getElementById('deleteModal').style.display = 'none';
}
window.onclick = function(event) {
    const addModal = document.getElementById('addModal');
    const deleteModal = document.getElementById('deleteModal');
    const successModal = document.getElementById('successModal');
    if (event.target === addModal) addModal.style.display = "none";
    if (event.target === deleteModal) deleteModal.style.display = "none";
    if (event.target === successModal) successModal.style.display = "none";
}
</script>
</body>
</html>
