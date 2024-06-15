<?php
// Establish connection to the Oracle database
$conn = oci_connect('AAYUSH', 'Goodluck#123', '//localhost/xe');

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_id = $_POST['user_id'];
    $full_name = $_POST['user-name'];
    $phone = $_POST['phone'];
    $email = $_POST['email'];
    $dob = $_POST['dob'];

    // Split full name into first and last name
    $name_parts = explode(' ', $full_name);
    $first_name = array_shift($name_parts);
    $last_name = implode(' ', $name_parts);

    // Handle file upload
    if (isset($_FILES['profile-pic']) && $_FILES['profile-pic']['error'] == UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/';
        $profile_pic = $upload_dir . basename($_FILES['profile-pic']['name']);
        move_uploaded_file($_FILES['profile-pic']['tmp_name'], $profile_pic);
    } else {
        $profile_pic = null; // or keep the old image
    }

    // Update user data
    $sql = "UPDATE USERS 
            SET FIRST_NAME = :first_name, LAST_NAME = :last_name, PHN_NO = :phone, EMAIL = :email, DOB = TO_DATE(:dob, 'YYYY-MM-DD')";
    
    if ($profile_pic) {
        $sql .= ", USER_IMAGE = :profile_pic";
    }

    $sql .= " WHERE USER_ID = :user_id";

    $stid = oci_parse($conn, $sql);

    oci_bind_by_name($stid, ':first_name', $first_name);
    oci_bind_by_name($stid, ':last_name', $last_name);
    oci_bind_by_name($stid, ':phone', $phone);
    oci_bind_by_name($stid, ':email', $email);
    oci_bind_by_name($stid, ':dob', $dob);

    if ($profile_pic) {
        oci_bind_by_name($stid, ':profile_pic', $profile_pic);
    }

    oci_bind_by_name($stid, ':user_id', $user_id);

    $result = oci_execute($stid);

    if ($result) {
        echo "Profile updated successfully.";
    } else {
        $e = oci_error($stid);
        echo "Error updating profile: " . $e['message'];
    }
}
?>
