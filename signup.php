<?php
session_start();
include 'includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullname = $_POST['fullname'] ?? '';
    $username = $_POST['username'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Password validation
    $password_errors = [];
    
    if (strlen($password) < 8) {
        $password_errors[] = "Password must be at least 8 characters long";
    }
    if (!preg_match('/[A-Z]/', $password)) {
        $password_errors[] = "Password must contain at least one uppercase letter";
    }
    if (!preg_match('/[a-z]/', $password)) {
        $password_errors[] = "Password must contain at least one lowercase letter";
    }
    if (!preg_match('/[0-9]/', $password)) {
        $password_errors[] = "Password must contain at least one number";
    }
    if (!preg_match('/[!@#$%^&*()\-_=+{};:,<.>]/', $password)) {
        $password_errors[] = "Password must contain at least one special character (!@#$%^&*()-_=+{};:,<.>)";
    }

    if (!empty($password_errors)) {
        $error_message = "Password requirements not met:<br>" . implode("<br>", $password_errors);
        header("Location: signup.php?message=" . urlencode($error_message) . "&type=error");
        exit();
    }

    if ($password !== $confirm_password) {
        header("Location: signup.php?message=" . urlencode("Passwords do not match") . "&type=error");
        exit();
    }

    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
    $stmt->execute([$username, $email]);

    if ($stmt->rowCount() > 0) {
        header("Location: signup.php?message=" . urlencode("Username or email already exists!") . "&type=error");
        exit();
    }

    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $insert_stmt = $pdo->prepare("INSERT INTO users (fullname, username, email, password) VALUES (?, ?, ?, ?)");

    if ($insert_stmt->execute([$fullname, $username, $email, $hashed_password])) {
        header("Location: login.php?message=" . urlencode("Account created successfully!") . "&type=success");
        exit();
    } else {
        header("Location: signup.php?message=" . urlencode("Sign up failed. Please try again.") . "&type=error");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Sign Up - Yoona</title>
    <link rel="icon" href="yoona.png">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        * { 
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            margin: 0;
            padding: 0;
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .signup-container {
            width: 400px;
            min-height: 500px;
            padding: 14px;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 18px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.08);
            text-align: center;
            backdrop-filter: blur(8px);
            transition: transform 0.3s ease;
        }

        .signup-container:hover {
            transform: translateY(-5px);
        }

        .signup-container img {
            width: 44px;
            height: 44px;
            margin-bottom: 2px;
            transition: transform 0.3s ease;
        }

        .signup-container img:hover {
            transform: scale(1.05);
        }

        .signup-container h2 {
            margin-bottom: 10px;
            font-size: 18px;
            color: #333;
            font-weight: 600;
        }

        input[type="text"], input[type="password"], input[type="email"] {
            width: 100%;
            padding: 10px;
            margin: 8px 0;
            border: 2px solid #e1e1e1;
            border-radius: 10px;
            font-size: 13px;
            transition: all 0.3s ease;
            background: #f8f9fa;
        }

        input[type="text"]:focus, input[type="password"]:focus, input[type="email"]:focus {
            border-color: #5928a7;
            outline: none;
            box-shadow: 0 0 0 3px rgba(89, 40, 167, 0.1);
            background: #fff;
        }

        button {
            width: 100%;
            padding: 10px;
            background: linear-gradient(135deg, #5928a7 0%, #441f84 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 18px;
            margin-bottom: 28px;
        }

        button:hover {
            background: linear-gradient(135deg, #441f84 0%, #5928a7 100%);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(89, 40, 167, 0.3);
        }

        .extra-links {
            margin-top: 0;
            font-size: 12px;
            color: #666;
        }

        .extra-links a {
            color: #5928a7;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .extra-links a:hover {
            color: #441f84;
            text-decoration: underline;
        }

        .simple-alert {
            margin: 7px auto;
            padding: 7px 10px;
            border-radius: 10px;
            width: 100%;
            text-align: left;
            font-size: 11px;
            animation: slideIn 0.3s ease;
        }

        @keyframes slideIn {
            from {
                transform: translateY(-10px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
        }
        }

        .simple-alert.error {
            background: #fff5f5;
            color: #e53e3e;
            border-left: 4px solid #fc8181;
        }

        .simple-alert.success {
            background: #f0fff4;
            color: #38a169;
            border-left: 4px solid #68d391;
        }

        /* Password hint smaller */
        .signup-container ul {
            font-size: 11px;
            margin: 3px 0 0 18px;
        }
        .signup-container div[style*='Password must contain'] {
            font-size: 11px !important;
            margin: 2px 0 10px 0 !important;
        }

        /* Responsive Design */
        @media (max-width: 480px) {
            .signup-container {
                width: 90%;
                padding: 30px 20px;
            }
        }
    </style>
</head>
<body>
    <div class="signup-container">
        <img src="images/yoona.png" alt="Yoona Logo">
        <h2>Create Your Account</h2>
        <?php if (isset($_GET['message']) && isset($_GET['type'])): ?>
            <div class="simple-alert <?= htmlspecialchars($_GET['type']) ?>" style="max-height: 90px; overflow-y: auto; font-size: 12px;">
                <?= nl2br($_GET['message']) ?>
            </div>
        <?php endif; ?>
        <form action="signup.php" method="post">
            <input type="text" name="fullname" placeholder="Full Name" required>
            <input type="text" name="username" placeholder="Username" required>
            <input type="email" name="email" placeholder="Email Address" required>
            <input type="password" name="password" placeholder="Password" required>
            <input type="password" name="confirm_password" placeholder="Confirm Password" required>
            <div style="text-align: left; font-size: 12px; color: #666; margin: 5px 0 15px 0;">
                Password must contain:
                <ul style="margin: 5px 0 0 20px;">
                    <li>At least 8 characters</li>
                    <li>At least one uppercase letter</li>
                    <li>At least one lowercase letter</li>
                    <li>At least one number</li>
                    <li>At least one special character (!@#$%^&*()-_=+{};:,<.>)</li>
                </ul>
            </div>
            <button type="submit">Sign Up</button>
        </form>
        <div class="extra-links">
            <p>Already have an account? <a href="login.php">Login</a></p>
        </div>
    </div>
</body>
</html>
