<?php
session_start();

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

if (!isset($_SESSION['signup_data'])) {
    header('Location: signup.php');
    exit;
}

$otp = rand(100000, 999999);
$_SESSION['signup_data']['otp'] = $otp;

$mail = new PHPMailer(true);
try {
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'clekbuy@gmail.com';
    $mail->Password = 'rgvcnwlrwuhjpysk';
    $mail->SMTPSecure = 'ssl';
    $mail->Port = 465;

    $mail->setFrom('clekbuy@gmail.com', 'Cleckbuy');
    $mail->addAddress($_SESSION['signup_data']['email']);

    $mail->isHTML(true);
    $mail->Subject = 'Your OTP Code';
    $mail->Body    = 'Your OTP code is: ' . $otp;

    $mail->send();
    header('Location: verify_otp.php');
    exit;
} catch (Exception $e) {
    echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
}
?>
