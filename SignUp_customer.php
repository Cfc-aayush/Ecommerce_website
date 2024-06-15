<?php
session_start();

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

$conn = oci_connect('AAYUSH', 'Goodluck#123', '//localhost/xe');

if (!$conn) {
    $e = oci_error();
    error_log($e['message'], 0);
    die("Sorry, we're experiencing technical difficulties. Please try again later.");
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['signup'])) {
    $firstName = trim($_POST['FirstName']);
    $lastName = trim($_POST['LastName']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $phoneNumber = trim($_POST['phone_number']);
    $dateOfBirth = trim($_POST['date_of_birth']);
    $role = 'customer';
    $shopId = null;
    $discountId = null;

    // Check if email already exists
    $stmt = oci_parse($conn, "SELECT COUNT(*) FROM users WHERE email = :email");
    oci_bind_by_name($stmt, ":email", $email);
    oci_execute($stmt);
    $row = oci_fetch_array($stmt, OCI_ASSOC+OCI_RETURN_NULLS);
    if ($row['COUNT(*)'] > 0) {
        echo '<script>alert("Email already exists. Please use a different email."); window.location.replace("Signup_customer.php");</script>';
        exit;
    }
    
    // Server-side validation
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo '<script>alert("Invalid email format."); window.location.replace("Signup_customer.php");</script>';
        exit;
    }
    
    if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/', $password)) {
        echo '<script>alert("Password must be at least 8 characters long, including one uppercase letter, one lowercase letter, one number, and one special character."); window.location.replace("Signup_customer.php");</script>';
        exit;
    }
    
    if (!preg_match('/^[0-9-]{10,15}$/', $phoneNumber)) {
        echo '<script>alert("Invalid phone number."); window.location.replace("Signup_customer.php");</script>';
        exit;
    }
    
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] == UPLOAD_ERR_OK) {
        $profilePicture = $_FILES['profile_picture'];
        $uploadDir = 'uploads/';
        $uploadFile = $uploadDir . basename($profilePicture['name']);
    
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        if (in_array($profilePicture['type'], $allowedTypes) && $profilePicture['size'] <= 5000000) { // 5MB limit
            if (move_uploaded_file($profilePicture['tmp_name'], $uploadFile)) {
                $profilePicturePath = $uploadFile;
    
                // Hash the password
                $hashedPassword = md5($password);
    
                // Generate OTP
                $otp = rand(100000, 999999);
    
                // Store signup data and OTP in session
                $_SESSION['signup_data'] = [
                    'firstName' => $firstName,
                    'lastName' => $lastName,
                    'email' => $email,
                    'password' => $hashedPassword,
                    'phoneNumber' => $phoneNumber,
                    'dateOfBirth' => $dateOfBirth,
                    'role' => $role,
                    'shopId' => $shopId,
                    'discountId' => $discountId,
                    'profilePicturePath' => $profilePicturePath,
                    'otp' => $otp
                ];
    
                // Send OTP to email
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
                    $mail->addAddress($email);
    
                    $mail->isHTML(true);
                    $mail->Subject = 'Your OTP Code';
                    $mail->Body    = 'Your OTP code is: ' . $otp;
    
                    $mail->send();
                    echo '<script>alert("Signup successful! Check your email for OTP."); window.location.replace("verify_otp.php");</script>';
                    exit;
                } catch (Exception $e) {
                    error_log("Mailer Error: {$mail->ErrorInfo}", 0);
                    echo '<script>alert("Sorry, we\'re experiencing technical difficulties. Please try again later."); window.location.replace("Signup_customer.php");</script>';
                }
            } else {
                echo '<script>alert("Failed to upload profile picture."); window.location.replace("Signup_customer.php");</script>';
            }
        } else {
            echo '<script>alert("Invalid file type or size too large."); window.location.replace("Signup_customer.php");</script>';
        }
    } else {
        echo '<script>alert("Profile picture upload error: ' . $_FILES['profile_picture']['error'] . '"); window.location.replace("Signup_customer.php");</script>';
    }
    
}
oci_close($conn);
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Signup as Customer</title>
    <link rel="stylesheet" href="customer.css">
</head>
<body>
    <div class="login-container">
        <a href = "home.php"><img src="image/LogoDark.png" alt="Logo" class="logo"></a>
        <form class="login-form" method="post" enctype="multipart/form-data">
            <h2>Signup as Customer</h2>
            <div class="input-group">
                <label for="first-name">First Name</label>
                <input type="text" id="first-name" name="FirstName" required>
            </div>
            <div class="input-group">
                <label for="last-name">Last Name</label>
                <input type="text" id="last-name" name="LastName" required>
            </div>
            <div class="input-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required>
            </div>
            <div class="input-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            <div class="input-group">
                <label for="phone_number">Phone Number</label>
                <input type="tel" id="phone_number" name="phone_number" required>
            </div>
            <div class="input-group">
                <label for="date-of-birth">Date of Birth</label>
                <input type="date" id="date-of-birth" name="date_of_birth" required>
            </div>
            <div class="input-group">
                <label for="profile-picture">Profile Picture</label>
                <input type="file" id="profile-picture" name="profile_picture" accept="image/*" required>
            </div>
            <button type="submit" name="signup">Sign Up</button>
            <p>Already have an account? <a href="Login.php">Login</a></p>
        </form>
    </div>
</body>
</html>
