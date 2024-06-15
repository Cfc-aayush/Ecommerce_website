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

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $traderName = trim($_POST['trader_name']);
    $email = trim($_POST['email']);
    $contact = trim($_POST['contact']);
    $password = trim($_POST['password']);
    $description = trim($_POST['description']);
    $gender = trim($_POST['gender']);
    $dateOfBirth = trim($_POST['date_of_birth']);

    $errors = [];

        // Check if email already exists
        $stmt = oci_parse($conn, "SELECT COUNT(*) FROM users WHERE email = :email");
        oci_bind_by_name($stmt, ":email", $email);
        oci_execute($stmt);
        $row = oci_fetch_array($stmt, OCI_ASSOC+OCI_RETURN_NULLS);
        if ($row['COUNT(*)'] > 0) {
            echo '<script>alert("Email already exists. Please use a different email."); window.location.replace("Signup_customer.php");</script>';
            exit;
        }
        
    // Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format.";
    }

    // Validate password: at least 8 characters, one uppercase, one lowercase, one number, one special character
    if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/', $password)) {
        $errors[] = "Password must be at least 8 characters long, including one uppercase letter, one lowercase letter, one number, and one special character.";
    }

    // Validate contact number: allows digits and hyphens, and should be between 10-15 characters
    if (!preg_match('/^[0-9-]{10,15}$/', $contact)) {
        $errors[] = "Invalid contact number. It should contain 10-15 digits or hyphens.";
    }

    // Check for errors before proceeding
    if (empty($errors)) {
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
                    $_SESSION['signup_data'] = [
                        'traderName' => $traderName,
                        'email' => $email,
                        'contact' => $contact,
                        'password' => $hashedPassword,
                        'description' => $description,
                        'gender' => $gender,
                        'dateOfBirth' => $dateOfBirth,
                        'profilePicturePath' => $profilePicturePath,
                        'otp' => $otp,
                        'otp_time' => time() + 300 // OTP valid for 5 minutes
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
                        header('Location: verify_otp_trader.php');
                        exit;
                    } catch (Exception $e) {
                        echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
                    }
                } else {
                    echo "Failed to upload profile picture.";
                }
            } else {
                echo "Invalid file type or file too large.";
            }
        } else {
            echo "Profile picture upload error: " . $_FILES['profile_picture']['error'];
        }
    } else {
        foreach ($errors as $error) {
            echo "<p>Error: $error</p>";
        }
    }
}

oci_close($conn);
?>


<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sign Up as Trader</title>
  <link rel="stylesheet" href="trader.css">
</head>
<body>
  <div class="login-container">
    <img src="image/Cleckbuy_dark.png" alt="Logo" class="logo">
    <form class="login-form" action="" method="post" enctype="multipart/form-data">
      <h2>Sign Up as Trader</h2>
      <div class="input-group">
        <label for="trader-name">Trader Name</label>
        <input type="text" id="trader-name" name="trader_name" required>
      </div>
      <div class="input-group">
        <label for="email">Email</label>
        <input type="email" id="email" name="email" required>
      </div>
      <div class="input-group">
        <label for="contact">Contact</label>
        <input type="tel" id="contact" name="contact" required>
      </div>
      <div class="input-group">
        <label for="password">Password</label>
        <input type="password" id="password" name="password" required>
      </div>
      <div class="input-group">
        <label for="description">Description about the shop</label>
        <textarea id="description" name="description" rows="4" cols="50"></textarea>
      </div>
      <div class="input-group">
        <label for="gender">Gender</label>
        <select name="gender" id="gender">
          <option value="male">Male</option>
          <option value="female">Female</option>
          <option value="other">Other</option>
        </select>
      </div>
      <div class="input-group">
        <label for="profile-picture">Profile Picture</label>
        <input type="file" id="profile-picture" name="profile_picture" accept="image/*" required>
      </div>
      <div class="input-group">
        <label for="date-of-birth">Date of Birth</label>
        <input type="date" id="date-of-birth" name="date_of_birth" required>
      </div>
      <button type="submit">Continue</button>
      <p>Already have an account? <a href="login.php">Login</a></p>
    </form>
  </div>
</body>
</html>
