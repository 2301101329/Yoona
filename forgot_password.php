<?php
session_start();
require 'includes/db.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';

ob_start(); // Prevent header errors

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        $reset_code = rand(100000, 999999);

        // Make sure 'reset_code' column exists in your database
        $update = $pdo->prepare("UPDATE users SET reset_code = ? WHERE email = ?");
        $update->execute([$reset_code, $email]);

        $_SESSION['reset_email'] = $email;
        $_SESSION['reset_code'] = $reset_code;

        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = '2301101329@student.buksu.edu.ph';
            $mail->Password = 'pqun abnr djrc afwm'; // Use your Gmail App Password
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;

            $mail->setFrom('2301101329@student.buksu.edu.ph', 'Yoona Computer Trading');
            $mail->addAddress($email, 'Client');

            $mail->isHTML(true);
            $mail->Subject = "Password Reset Code";
            $mail->Body = "<p>Hello, this is your password reset code: <strong>{$reset_code}</strong></p>";
            $mail->AltBody = "Hello, use this code to reset your password: {$reset_code}";

            $mail->send();

            $_SESSION['success'] = "A verification code has been sent to your email.";
            header('Location: enter_code.php');
            exit();
        } catch (Exception $e) {
            $_SESSION['error'] = "Mailer Error: " . $mail->ErrorInfo;
            header('Location: forgot_password.php');
            exit();
        }
    } else {
        $_SESSION['error'] = "No user found with that email.";
        header('Location: forgot_password.php');
        exit();
    }
}

ob_end_flush();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Forgot Password - Yoona</title>
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

        h3 {
            margin-bottom: 25px;
            font-size: 26px;
            color: #333;
            font-weight: 600;
        }

        input[type="email"] {
            width: 100%;
            padding: 15px;
            margin: 12px 0;
            border: 2px solid #e1e1e1;
            border-radius: 12px;
            font-size: 15px;
            transition: all 0.3s ease;
            background: #f8f9fa;
        }

        input[type="email"]:focus {
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

        .text-center {
            margin-top: 25px;
            font-size: 14px;
            color: #666;
        }

        .text-center a {
            color: #5928a7;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .text-center a:hover {
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

        .alert-success {
            background: #f0fff4;
            color: #38a169;
            border-left: 4px solid #68d391;
        }

        .alert-danger {
            background: #fff5f5;
            color: #e53e3e;
            border-left: 4px solid #fc8181;
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
        <h3>Forgot Password</h3>

        <?php
        if (isset($_SESSION['success'])) {
            echo '<div class="alert alert-success">' . $_SESSION['success'] . '</div>';
            unset($_SESSION['success']);
        }
        if (isset($_SESSION['error'])) {
            echo '<div class="alert alert-danger">' . $_SESSION['error'] . '</div>';
            unset($_SESSION['error']);
        }
        ?>

        <form action="forgot_password.php" method="POST">
            <input required type="email" name="email" placeholder="Enter your email address">
            <button type="submit">Send Reset Code</button>
        </form>

        <div class="text-center">
            <p>Remember your password? <a href="login.php">Login</a></p>
        </div>
    </div>
</body>
</html>
