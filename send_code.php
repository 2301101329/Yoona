<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';  // Make sure this path is correct
include 'includes/db.php';      // Make sure this is the correct path for your database connection

session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';

    // Check if the email exists in the database
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    // If email is not found in the database
    if ($result->num_rows === 0) {
        header("Location: forgot_password.php?message=" . urlencode("Email not found!") . "&type=error");
        exit();
    }

    // Generate the 6-digit code
    $code = rand(100000, 999999);
    $_SESSION['reset_code'] = $code;
    $_SESSION['reset_email'] = $email;

    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();  
        $mail->Host = 'smtp.gmail.com';  // Gmail SMTP server
        $mail->SMTPAuth = true;
        $mail->Username = '2301101329@student.buksu.edu.ph';  // Replace with your Gmail address
        $mail->Password = 'ymfp wpwt rtau lbao';    // Use an App Password, not your Gmail password
        $mail->SMTPSecure = 'tls';  // TLS encryption
        $mail->Port = 587;  // Gmail SMTP port

        // Enable debugging
        $mail->SMTPDebug = 2;  // Debug output
        $mail->Debugoutput = 'html';  // Output as HTML (for better readability)

        // Recipients
        $mail->setFrom('2301101329@student.buksu.edu.ph', 'Yoona Computer Trading');
        $mail->addAddress($email);  // Add the recipient's email address

        // Content
        $mail->isHTML(true);  // Set email format to HTML
        $mail->Subject = 'Your Password Reset Code';
        $mail->Body = "<h3>Password Reset Code</h3><p>Your code is: <strong>$code</strong></p>";
        $mail->AltBody = "Your code is: $code";  // Text version for non-HTML mail clients

        // Send the email
        $mail->send();

        // Redirect to verification page
        header("Location: verify_code.php?message=" . urlencode("Code sent to your email.") . "&type=success");
        exit();
    } catch (Exception $e) {
        // Catch any errors and show the error message
        echo "Mailer Error: " . $mail->ErrorInfo;
        header("Location: forgot_password.php?message=" . urlencode("Failed to send email. Try again.") . "&type=error");
        exit();
    }
}
?>
