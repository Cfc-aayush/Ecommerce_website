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
    $otpExpiryTime = $_SESSION['signup_data']['otp_time'] ?? time();

    if (time() > $otpExpiryTime) {
        $error = "OTP has expired!";
    } elseif ($enteredOtp == $_SESSION['signup_data']['otp']) {
        // Retrieve signup data from session
        $data = $_SESSION['signup_data'];
        
        // Insert user into the database
        $sql = 'INSERT INTO users (FIRST_NAME, LAST_NAME, EMAIL, PASSWORD, PHN_NO, DOB, ROLE, FK1_SHOP_ID, FK2_DISCOUNT_ID, USER_IMAGE, STATUS) VALUES (:first_name, :last_name, :email, :password, :phone_number, TO_DATE(:date_of_birth, \'YYYY-MM-DD\'), :role, :shop_id, :discount_id, :user_image, 1)';
        $stid = oci_parse($conn, $sql);
        
        oci_bind_by_name($stid, ':first_name', $data['firstName']);
        oci_bind_by_name($stid, ':last_name', $data['lastName']);
        oci_bind_by_name($stid, ':email', $data['email']);
        oci_bind_by_name($stid, ':password', $data['password']);
        oci_bind_by_name($stid, ':phone_number', $data['phoneNumber']);
        oci_bind_by_name($stid, ':date_of_birth', $data['dateOfBirth']);
        oci_bind_by_name($stid, ':role', $data['role']);
        oci_bind_by_name($stid, ':shop_id', $data['shopId']);
        oci_bind_by_name($stid, ':discount_id', $data['discountId']);
        oci_bind_by_name($stid, ':user_image', $data['profilePicturePath']);
        
        $result = oci_execute($stid);
        
        if ($result) {
            // Get the newly created user ID
            $sql_get_user_id = "SELECT increase_userid.CURRVAL AS USER_ID FROM DUAL"; // Using the correct sequence name
            $stid_get_user_id = oci_parse($conn, $sql_get_user_id);
            oci_execute($stid_get_user_id);

            if ($row = oci_fetch_assoc($stid_get_user_id)) {
                $user_id = $row['USER_ID'];

                // Create a cart for the user
                $sql_create_cart = "INSERT INTO CART (FK2_USER_ID) VALUES (:user_id)";
                $stid_create_cart = oci_parse($conn, $sql_create_cart);
                oci_bind_by_name($stid_create_cart, ':user_id', $user_id);
                oci_execute($stid_create_cart);

                // Create a wishlist for the user
                $sql_create_wishlist = "INSERT INTO WISHLIST (FK1_USER_ID) VALUES (:user_id)";
                $stid_create_wishlist = oci_parse($conn, $sql_create_wishlist);
                oci_bind_by_name($stid_create_wishlist, ':user_id', $user_id);
                oci_execute($stid_create_wishlist);

                oci_commit($conn); // Commit all changes

                unset($_SESSION['signup_data']); // Clear session data
                header('Location: login.php');
                exit;
            } else {
                echo "Error retrieving user ID.";
                exit;
            }
        } else {
            $e = oci_error($stid);
            error_log("Database Error: " . htmlentities($e['message'], ENT_QUOTES), 0);
            echo "Sorry, we encountered an error. Please try again later.";
        }
        
        oci_free_statement($stid);
    } else {
        $error = "Invalid OTP!";
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
    <img src="image/LogoDark.png" alt="Logo" class="logo">
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
