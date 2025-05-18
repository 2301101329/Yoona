<?php
// Start session only if it's not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require 'includes/db.php'; // Ensure this contains $pdo

// If the reset_code has been verified, allow access to the page, otherwise redirect
if (!isset($_SESSION['reset_code_verified']) || !$_SESSION['reset_code_verified']) {
    header('Location: forgot_password.php');
    exit();
}

// Handle the password reset form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newPassword = $_POST['password'];
    $confirmPassword = $_POST['confirm_password'];

    // Password validation
    $password_errors = [];
    if (strlen($newPassword) < 8) {
        $password_errors[] = "Password must be at least 8 characters long";
    }
    if (!preg_match('/[A-Z]/', $newPassword)) {
        $password_errors[] = "Password must contain at least one uppercase letter";
    }
    if (!preg_match('/[a-z]/', $newPassword)) {
        $password_errors[] = "Password must contain at least one lowercase letter";
    }
    if (!preg_match('/[0-9]/', $newPassword)) {
        $password_errors[] = "Password must contain at least one number";
    }
    if (!preg_match('/[!@#$%^&*()\-_=+{};:,<.>]/', $newPassword)) {
        $password_errors[] = "Password must contain at least one special character (!@#$%^&*()-_=+{};:,<.>)";
    }
    if (!empty($password_errors)) {
        $_SESSION['error'] = "Password requirements not met:<br>" . implode("<br>", $password_errors);
    } elseif ($newPassword === $confirmPassword) {
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

        // Update the password in the database
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE email = ?");
        $stmt->execute([$hashedPassword, $_SESSION['reset_email']]);

        // Clear the session variables related to password reset
        unset($_SESSION['reset_code_verified']);
        unset($_SESSION['reset_email']);

        // Set success message and redirect
        header("Location: login.php?message=" . urlencode("Password reset successfully! You can now login with your new password.") . "&type=success");
        exit();
    } else {
        $_SESSION['error'] = "Passwords do not match. Please try again.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reset Password - Yoona</title>
    <link rel="icon" href="images/yoona.png">
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

        .container {
            width: 420px;
            padding: 40px;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            text-align: center;
            backdrop-filter: blur(10px);
            transition: transform 0.3s ease;
        }

        .container:hover {
            transform: translateY(-5px);
        }

        .container img {
            width: 90px;
            height: 90px;
            margin-bottom: 15px;
            transition: transform 0.3s ease;
        }

        .container img:hover {
            transform: scale(1.05);
        }

        h4 {
            margin-bottom: 25px;
            font-size: 26px;
            color: #333;
            font-weight: 600;
        }

        .form-group {
            margin-bottom: 20px;
            text-align: left;
        }

        label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
            font-size: 14px;
        }

        input[type="password"] {
            width: 100%;
            padding: 15px;
            border: 2px solid #e1e1e1;
            border-radius: 12px;
            font-size: 15px;
            transition: all 0.3s ease;
            background: #f8f9fa;
        }

        input[type="password"]:focus {
            border-color: #5928a7;
            outline: none;
            box-shadow: 0 0 0 3px rgba(89, 40, 167, 0.1);
            background: #fff;
        }

        button {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #5928a7 0%, #441f84 100%);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 10px;
        }

        button:hover {
            background: linear-gradient(135deg, #441f84 0%, #5928a7 100%);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(89, 40, 167, 0.3);
        }

        .mt-3 {
            margin-top: 25px;
            font-size: 14px;
            color: #666;
        }

        .mt-3 a {
            color: #5928a7;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .mt-3 a:hover {
            color: #441f84;
            text-decoration: underline;
        }

        .alert {
            margin: 15px auto;
            padding: 15px 20px;
            border-radius: 12px;
            width: 100%;
            text-align: left;
            font-size: 14px;
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

        .alert-danger {
            background: #fff5f5;
            color: #e53e3e;
            border-left: 4px solid #fc8181;
        }

        .alert-success {
            background: #f0fff4;
            color: #38a169;
            border-left: 4px solid #68d391;
        }

        /* Responsive Design */
        @media (max-width: 480px) {
            .container {
                width: 90%;
                padding: 30px 20px;
            }
        }
    </style>
</head>
<body>
<div class="container">
        <img src="images/yoona.png" alt="Yoona Logo">
        <h4>Reset Your Password</h4>

        <?php
        if (isset($_SESSION['error'])) {
            echo '<div class="alert alert-danger" style="max-height: 90px; overflow-y: auto; font-size: 13px;">' . $_SESSION['error'] . '</div>';
            unset($_SESSION['error']);
        }
        if (isset($_SESSION['success'])) {
            echo '<div class="alert alert-success">' . $_SESSION['success'] . '</div>';
            unset($_SESSION['success']);
        }
        ?>

        <form action="reset_password.php" method="POST">
            <div class="form-group">
                <label for="password">New Password</label>
                <input type="password" name="password" id="password" placeholder="Enter new password" required>
            </div>
            <div class="form-group">
                <label for="confirm_password">Confirm Password</label>
                <input type="password" name="confirm_password" id="confirm_password" placeholder="Confirm new password" required>
            </div>
            <button type="submit">Reset Password</button>
        </form>

        <div class="mt-3">
            <a href="login.php">Back to Login</a>
    </div>
</div>
</body>
</html>
