<?php
session_start();

$conn = oci_connect('AAYUSH', 'Goodluck#123', '//localhost/xe');

if (!$conn) {
    $e = oci_error();
    error_log($e['message'], 0);
    die("Sorry, we're experiencing technical difficulties. Please try again later.");
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['otp'])) {
    $enteredOtp = $_POST['otp'];
    
    if (isset($_SESSION['signup_data']['otp']) && isset($_SESSION['signup_data']['otp_time']) && time() <= $_SESSION['signup_data']['otp_time']) {
        if ($enteredOtp == $_SESSION['signup_data']['otp']) {
            // Retrieve signup data from session
            $data = $_SESSION['signup_data'];

            // Insert user into the database
            $sql = 'INSERT INTO users (FIRST_NAME, PHN_NO, EMAIL, PASSWORD, LAST_NAME, ROLE, DOB, FK1_SHOP_ID, FK2_DISCOUNT_ID, USER_IMAGE, STATUS) 
                    VALUES (:first_name, :phone_number, :email, :password, :last_name, :role, TO_DATE(:date_of_birth, \'YYYY-MM-DD\'), :shop_id, :discount_id, :user_image, :status)';
            $stid = oci_parse($conn, $sql);

            $role = 'trader';
            $status = 0;

            oci_bind_by_name($stid, ':first_name', $data['traderName']);
            oci_bind_by_name($stid, ':phone_number', $data['contact']);
            oci_bind_by_name($stid, ':email', $data['email']);
            oci_bind_by_name($stid, ':password', $data['password']);
            oci_bind_by_name($stid, ':last_name', $data['description']);
            oci_bind_by_name($stid, ':role', $role);
            oci_bind_by_name($stid, ':date_of_birth', $data['dateOfBirth']);
            oci_bind_by_name($stid, ':shop_id', $data['shopId']);
            oci_bind_by_name($stid, ':discount_id', $data['discountId']);
            oci_bind_by_name($stid, ':user_image', $data['profilePicturePath']);
            oci_bind_by_name($stid, ':status', $status);

            $result = oci_execute($stid);
            
            if ($result) {
                unset($_SESSION['signup_data']); // Clear session data
                echo "Account created successfully!";
                header('Location: login.php');
                exit;
            } else {
                $e = oci_error($stid);
                echo "Error: " . htmlentities($e['message'], ENT_QUOTES);
            }
            
            oci_free_statement($stid);
        } else {
            $error = "Invalid OTP!";
        }
    } else {
        $error = "OTP has expired or is invalid!";
    }
}

oci_close($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>OTP Verification</title>
  <link rel="stylesheet" href="OTP.css"> <!-- Use the same stylesheet as signup page -->
</head>
<body>
  <div class="login-container">
    <img src="image/Cleckbuy_dark.png" alt="Logo" class="logo">
    <form class="login-form" action="" method="post">
      <h2>OTP Verification</h2>
      <p>OTP has been sent to your email</p>
      <div class="input-group">
        <label for="otp">Enter your OTP</label>
        <input type="text" id="otp" name="otp" required>
      </div>
      <?php if (isset($error)): ?>
        <p class="error"><?php echo $error; ?></p>
      <?php endif; ?>
      <p>Didn't receive a code? <a href="resend_otp.php">Resend</a></p>
      <button type="submit">Continue</button>
    </form>
  </div>
</body>
</html>
