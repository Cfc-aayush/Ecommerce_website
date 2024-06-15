<?php
session_start(); // Start the session

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    // Redirect to login page if not logged in
    header("Location: login.php");
    exit;
}

// Establish connection to the Oracle database
$conn = oci_connect('AAYUSH', 'Goodluck#123', '//localhost/xe');

// Fetch user data
$user_id = $_SESSION['user_id'];
$sql = "SELECT USER_ID, FIRST_NAME, LAST_NAME, PHN_NO, EMAIL, PASSWORD, DOB, USER_IMAGE FROM USERS WHERE USER_ID = :user_id";
$stid = oci_parse($conn, $sql);
oci_bind_by_name($stid, ':user_id', $user_id);
oci_execute($stid);
$user = oci_fetch_array($stid, OCI_ASSOC + OCI_RETURN_NULLS);

// Format DOB to a human-readable format
$dob = date("Y-m-d", strtotime($user['DOB']));

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get the form data
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $phone = $_POST['phone'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $password_confirm = $_POST['password_confirm'];
    $dob = $_POST['dob'];
    $user_image = $user['USER_IMAGE']; // Default to the existing image

    // Validate the form data
    if (empty($first_name) || empty($last_name) || empty($phone) || empty($email) || empty($dob)) {
        $error_message = "All fields except password and image are required.";
    } elseif (!empty($password) && $password !== $password_confirm) {
        $error_message = "Passwords do not match.";
    } else {
        // Handle file upload if a new image is provided
        if (!empty($_FILES['user_image']['name'])) {
            $target_dir = "uploads/";
            $target_file = $target_dir . basename($_FILES['user_image']['name']);
            $image_file_type = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

            // Check if the file is an image
            $check = getimagesize($_FILES['user_image']['tmp_name']);
            if ($check === false) {
                $error_message = "File is not an image.";
            } elseif (file_exists($target_file)) {
                $error_message = "File already exists.";
            } elseif ($_FILES['user_image']['size'] > 5000000) { // Limit file size to 5MB
                $error_message = "File is too large.";
            } elseif (!in_array($image_file_type, ["jpg", "jpeg", "png", "gif"])) {
                $error_message = "Only JPG, JPEG, PNG, and GIF files are allowed.";
            } else {
                if (move_uploaded_file($_FILES['user_image']['tmp_name'], $target_file)) {
                    $user_image = $target_file;
                } else {
                    $error_message = "Error uploading file.";
                }
            }
        }

        if (!isset($error_message)) {
            // Hash the password if it is changed, otherwise use the existing password
            if (!empty($password)) {
                $password_hash = md5($password);
            } else {
                $password_hash = $user['PASSWORD']; // Keep the existing password
            }

            // Update user data in the database
            $update_sql = "UPDATE USERS SET FIRST_NAME = :first_name, LAST_NAME = :last_name, PHN_NO = :phone, EMAIL = :email, PASSWORD = :password, DOB = TO_DATE(:dob, 'YYYY-MM-DD'), USER_IMAGE = :user_image WHERE USER_ID = :user_id";
            $update_stid = oci_parse($conn, $update_sql);
            oci_bind_by_name($update_stid, ':first_name', $first_name);
            oci_bind_by_name($update_stid, ':last_name', $last_name);
            oci_bind_by_name($update_stid, ':phone', $phone);
            oci_bind_by_name($update_stid, ':email', $email);
            oci_bind_by_name($update_stid, ':password', $password_hash);
            oci_bind_by_name($update_stid, ':dob', $dob);
            oci_bind_by_name($update_stid, ':user_image', $user_image);
            oci_bind_by_name($update_stid, ':user_id', $user_id);

            $result = oci_execute($update_stid);

            if ($result) {
                $_SESSION['first_name'] = $first_name;
                $message = "Profile updated successfully.";
                // Fetch updated user data
                oci_execute($stid);
                $user = oci_fetch_array($stid, OCI_ASSOC + OCI_RETURN_NULLS);
                $dob = date("Y-m-d", strtotime($user['DOB']));
            } else {
                $error_message = "Error updating profile.";
            }
        }
    }
}

oci_close($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Edit User Profile</title>
    <link rel="stylesheet" href="edit_user.css" />
</head>
<body>
    <?php include('navbar.php'); ?>
    <div class="container">
        <div class="profile-content">
            <div class="edit-profile-text">
                <h2>Edit your Profile</h2>
            </div>
            <div class="profile-info-container">
                <?php if (isset($message)) { echo "<p class='success'>$message</p>"; } ?>
                <?php if (isset($error_message)) { echo "<p class='error'>$error_message</p>"; } ?>
                <form method="post" enctype="multipart/form-data">
                    <div class="profile-info">
                        <input type="text" id="first_name" name="first_name" value="<?php echo htmlspecialchars($user['FIRST_NAME'], ENT_QUOTES, 'UTF-8'); ?>" placeholder="First Name"/>
                        <input type="text" id="last_name" name="last_name" value="<?php echo htmlspecialchars($user['LAST_NAME'], ENT_QUOTES, 'UTF-8'); ?>" placeholder="Last Name"/>
                        <input type="text" id="phone" name="phone" value="<?php echo htmlspecialchars($user['PHN_NO'], ENT_QUOTES, 'UTF-8'); ?>" placeholder="Phone Number" />
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['EMAIL'], ENT_QUOTES, 'UTF-8'); ?>" placeholder="Email" />
                        <input type="password" id="password" name="password" placeholder="Password" />
                        <input type="password" id="password_confirm" name="password_confirm" placeholder="Confirm Password" />
                        <input type="date" id="dob" name="dob" value="<?php echo htmlspecialchars($dob, ENT_QUOTES, 'UTF-8'); ?>" placeholder="Date of Birth" />
                        <input type="file" id="user_image" name="user_image" accept="image/*" />
                        <div class="button-container">
                            <button type="submit" class="edit-btn">Save Changes</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
