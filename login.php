<?php
session_start();
require 'includes/db.php';
require 'vendor/autoload.php'; // You'll need to install Google Client Library via Composer
ob_start(); // Start output buffering

// Google OAuth Configuration
$clientID = '446223619409-1186p9mjp6ji60oe5h4podbm9gojhpcr.apps.googleusercontent.com'; // Replace with your Google Client ID
$clientSecret = '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'; // Replace with your Google Client Secret
$redirectUri = 'http://localhost/web_development/login.php'; // Update with your actual domain

// Create Client Request to access Google API
$client = new Google_Client();
$client->setClientId($clientID);
$client->setClientSecret($clientSecret);
$client->setRedirectUri($redirectUri);
$client->addScope("email");
$client->addScope("profile");

// Handle Google OAuth Callback
if (isset($_GET['code'])) {
    $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
    $client->setAccessToken($token['access_token']);
    
    // Get user profile data from Google
    $google_oauth = new Google_Service_Oauth2($client);
    $google_account_info = $google_oauth->userinfo->get();
    $email = $google_account_info->email;
    
    // Check if email exists in database
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email LIMIT 1");
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        // User exists, log them in
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['fullname'] = $user['fullname'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['role'] = $user['role'];
        
        if ($user['role'] === 'admin') {
            header("Location: admin_main.php");
        } else {
            header("Location: main.php");
        }
        exit();
    } else {
        // Email doesn't exist in database
        header("Location: login.php?message=" . urlencode("This Google account is not registered in our system") . "&type=error");
        exit();
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $usernameOrEmail = $_POST['username'];
    $password = $_POST['password'];
    $captchaResponse = $_POST['g-recaptcha-response']; // Get the captcha response

    // Verify reCAPTCHA
    $secretKey = "6LeAqSgrAAAAAMz4HDi2WGOhWHceSkzr0cFVfEir"; // Secret Key
    $verifyUrl = "https://www.google.com/recaptcha/api/siteverify?secret=$secretKey&response=$captchaResponse";
    $verifyResponse = file_get_contents($verifyUrl);
    $responseKeys = json_decode($verifyResponse, true);

    // If CAPTCHA is not solved
    if (intval($responseKeys["success"]) !== 1) {
        header("Location: login.php?message=" . urlencode("Please verify that you are not a robot") . "&type=error");
        exit();
    }

    // If CAPTCHA is solved, check credentials
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = :user OR email = :user LIMIT 1");
    $stmt->execute(['user' => $usernameOrEmail]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['fullname'] = $user['fullname'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['role'] = $user['role'];

        if ($user['role'] === 'admin') {
            header("Location: admin_main.php");
        } else {
            header("Location: main.php");
        }
        exit();
    } else {
        header("Location: login.php?message=" . urlencode("Invalid username or password") . "&type=error");
        exit();
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login - Yoona</title>
    <link rel="icon" href="yoona.png">
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    <script src="https://accounts.google.com/gsi/client" async defer></script>
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

        .login-container {
            width: 900px;
            height: 700px;
            display: flex;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .login-left {
            flex: 1;
            background: linear-gradient(135deg, #5928a7 0%, #441f84 100%);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 40px;
            color: white;
            text-align: center;
        }

        .login-right {
            flex: 1;
            padding: 50px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            min-width: 320px;
        }

        .login-left img {
            width: 150px;
            height: 150px;
            margin-bottom: 20px;
            transition: transform 0.3s ease;
        }

        .login-left img:hover {
            transform: scale(1.05);
        }

        .login-left h2 {
            font-size: 32px;
            margin-bottom: 15px;
            font-weight: 600;
        }

        .login-left p {
            font-size: 16px;
            opacity: 0.9;
        }

        .login-right h2 {
            margin-bottom: 30px;
            font-size: 28px;
            color: #333;
            font-weight: 600;
        }

        input[type="text"], input[type="password"] {
            width: 100%;
            padding: 15px;
            margin: 12px 0;
            border: 2px solid #e1e1e1;
            border-radius: 12px;
            font-size: 15px;
            transition: all 0.3s ease;
            background: #f8f9fa;
        }

        input[type="text"]:focus, input[type="password"]:focus {
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

        .extra-links {
            margin-top: 25px;
            font-size: 14px;
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

        .popup {
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

        .popup.error {
            background: #fff5f5;
            color: #e53e3e;
            border-left: 4px solid #fc8181;
        }

        .popup.success {
            background: #f0fff4;
            color: #38a169;
            border-left: 4px solid #68d391;
        }

        form .g-recaptcha {
            display: flex;
            justify-content: center;
            margin: 20px auto;
            transform: scale(1.05);
        }

        /* Responsive Design */
        @media (max-width: 900px) {
            .login-container {
                width: 90%;
                flex-direction: column;
                height: auto;
            }
            .login-left {
                padding: 30px;
            }
            .login-left img {
                width: 100px;
                height: 100px;
            }
        }

        .google-btn {
            width: 100%;
            padding: 15px;
            background: #fff;
            color: #757575;
            border: 2px solid #e1e1e1;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            text-decoration: none;
        }

        .google-btn:hover {
            background: #f8f9fa;
            border-color: #d1d1d1;
        }

        .google-btn img {
            width: 20px;
            height: 20px;
        }

        .divider {
            display: flex;
            align-items: center;
            text-align: center;
            margin: 20px 0;
            color: #666;
        }

        .divider::before,
        .divider::after {
            content: '';
            flex: 1;
            border-bottom: 1px solid #e1e1e1;
        }

        .divider span {
            padding: 0 10px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-left">
            <img src="images/yoona.png" alt="Yoona Logo">
            <h2>Yoona Computer Trading</h2>
            <p>Welcome back! Please enter your details to sign in.</p>
        </div>

        <div class="login-right">
            <h2>Login Your Account</h2>

            <?php if (isset($_GET['message'])): ?>
                <div class="popup <?= htmlspecialchars($_GET['type']) ?>" id="popup-msg">
                    <?= htmlspecialchars($_GET['message']) ?>
                </div>
            <?php endif; ?>

            <form method="post">
                <input type="text" name="username" placeholder="Username" required>
                <input type="password" name="password" placeholder="Password" required>

                <div class="g-recaptcha" data-sitekey="6LeAqSgrAAAAAKIVyhlctl2z_tZqj4OOiyiCdg37"></div>

                <button type="submit">Log In</button>
            </form>

            <a href="<?= $client->createAuthUrl() ?>" class="google-btn">
                <img src="https://www.google.com/favicon.ico" alt="Google">
                <span style="text-decoration:none;">Sign in with Google</span>
            </a>

            <div class="extra-links">
                <p>Don't have an account? <a href="signup.php">Sign up</a></p>
                <p><a href="forgot_password.php">Forgot password?</a></p>
            </div>
        </div>
    </div>
</body>
</html>
