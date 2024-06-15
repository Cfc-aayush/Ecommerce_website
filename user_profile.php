<?php
// Start the session
session_start();

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    echo "You are not logged in.";
    exit;
}

// Get the user ID from the session
$user_id = $_SESSION['user_id'];

// Establish connection to the Oracle database
$conn = oci_connect('AAYUSH', 'Goodluck#123', '//localhost/xe');

// Check if the connection is successful
if (!$conn) {
    $e = oci_error();
    echo "Sorry, a connection error occurred: " . $e['message'];
    exit;
}

// SQL query to fetch data for the specific user
$sql = "SELECT FIRST_NAME, LAST_NAME, PHN_NO, EMAIL, USER_IMAGE, ROLE, DOB FROM USERS WHERE USER_ID = :user_id";
$stid = oci_parse($conn, $sql);

// Bind the user ID to the query
oci_bind_by_name($stid, ':user_id', $user_id);

// Execute the query
oci_execute($stid);

// Fetch the user data
$user = oci_fetch_array($stid, OCI_ASSOC + OCI_RETURN_NULLS);

// Check if user data is fetched successfully
if (!$user) {
    echo "No user found.";
    exit;
}

// Format DOB to a human-readable format
$dob = date("d-m-Y", strtotime($user['DOB']));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>User Profile</title>
    <link rel="stylesheet" href="user_page.css" />
</head>
<body>
    <?php include("navbar.php"); ?>
    <div class="container">
        <div class="profile-content">
            <div class="profile-pic">
                <img src="<?php echo htmlspecialchars($user['USER_IMAGE'], ENT_QUOTES, 'UTF-8'); ?>" alt="Profile Picture" />
            </div>
            <div class="profile-info">
                <input
                    type="text"
                    id="user-name"
                    name="user-name"
                    value="<?php echo htmlspecialchars($user['FIRST_NAME'] . ' ' . $user['LAST_NAME'], ENT_QUOTES, 'UTF-8'); ?>"
                    readonly
                />
                <input
                    type="text"
                    id="phone"
                    name="phone"
                    value="<?php echo htmlspecialchars($user['PHN_NO'], ENT_QUOTES, 'UTF-8'); ?>"
                    readonly
                />
                <input
                    type="email"
                    id="email"
                    name="email"
                    value="<?php echo htmlspecialchars($user['EMAIL'], ENT_QUOTES, 'UTF-8'); ?>"
                    readonly
                />
                <input
                    type="text"
                    id="role"
                    name="role"
                    value="<?php echo htmlspecialchars($user['ROLE'], ENT_QUOTES, 'UTF-8'); ?>"
                    readonly
                />
                <input
                    type="text"
                    id="dob"
                    name="dob"
                    value="<?php echo htmlspecialchars($dob, ENT_QUOTES, 'UTF-8'); ?>"
                    readonly
                />
                <a href="edit_user_profile.php?user_id=<?php echo $user_id; ?>" class="edit-btn">Edit</a>
            </div>
        </div>
    </div>
</body>
</html>
