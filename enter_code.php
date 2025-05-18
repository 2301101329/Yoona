<?php
session_start();
require 'includes/db.php'; // Ensure this contains $pdo

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $enteredCode = trim($_POST['code']);

    // Check if session contains the email
    if (!isset($_SESSION['reset_email'])) {
        $_SESSION['error'] = "No email session found. Please try again.";
        header('Location: forgot_password.php');
        exit();
    }

    // Retrieve email from session
    $email = $_SESSION['reset_email'];

    // Check the entered code against the stored reset code in the database
    $stmt = $pdo->prepare("SELECT reset_code FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        // If codes match, proceed to reset password
        if ($enteredCode == $user['reset_code']) {
            $_SESSION['reset_code_verified'] = true;
            header('Location: reset_password.php');
            exit();
        } else {
            // Code doesn't match
            $_SESSION['error'] = "Invalid code. Please try again.";
        }
    } else {
        // No user found with that email
        $_SESSION['error'] = "No user found with that email.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Verify Code - Yoona</title>
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

        input[type="number"] {
            width: 100%;
            padding: 15px;
            margin: 12px 0;
            border: 2px solid #e1e1e1;
            border-radius: 12px;
            font-size: 15px;
            transition: all 0.3s ease;
            background: #f8f9fa;
        }

        input[type="number"]:focus {
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
        <h4>Enter Verification Code</h4>

        <?php
        if (isset($_SESSION['error'])) {
            echo '<div class="alert alert-danger">' . $_SESSION['error'] . '</div>';
            unset($_SESSION['error']);
        }
        if (isset($_SESSION['success'])) {
            echo '<div class="alert alert-success">' . $_SESSION['success'] . '</div>';
            unset($_SESSION['success']);
        }
        ?>

        <form action="enter_code.php" method="POST">
            <input type="number" name="code" placeholder="Enter your 6-digit code" required>
            <button type="submit">Verify Code</button>
        </form>

        <div class="mt-3">
            <a href="forgot_password.php">Back to Forgot Password</a>
        </div>
    </div>
</body>
</html>
