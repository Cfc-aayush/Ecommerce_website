<?php
session_start();
$conn = oci_connect('AAYUSH', 'Goodluck#123', '//localhost/xe');

if (!$conn) {
    $e = oci_error();
    trigger_error(htmlentities($e['message'], ENT_QUOTES), E_USER_ERROR);
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $shop_name = $_POST['shop_name'];
    $location = $_POST['location'];
    $description = $_POST['description'];
    $verification = 0; // Default value for verification

    if (isset($_FILES['logo']) && $_FILES['logo']['error'] == UPLOAD_ERR_OK) {
        $logo = $_FILES['logo'];
        $uploadDir = 'uploads/';
        $uploadFile = $uploadDir . basename($logo['name']);

        if (move_uploaded_file($logo['tmp_name'], $uploadFile)) {
            $logoPath = $uploadFile;

            // Insert data into the shop table and get the shop_id of the inserted row
            $insertSql = 'INSERT INTO shop (SHOP_NAME, DESCRIPTION, SHOP_LOCATION, SHOP_LOGO, VERIFICATION) 
                          VALUES (:shop_name, :description, :location, :logo, :verification) RETURNING SHOP_ID INTO :shop_id';
            $stid = oci_parse($conn, $insertSql);

            oci_bind_by_name($stid, ':shop_name', $shop_name);
            oci_bind_by_name($stid, ':description', $description);
            oci_bind_by_name($stid, ':location', $location);
            oci_bind_by_name($stid, ':logo', $logoPath);
            oci_bind_by_name($stid, ':verification', $verification);
            oci_bind_by_name($stid, ':shop_id', $shop_id, -1, SQLT_INT);

            $result = oci_execute($stid);

            if ($result) {
                // Update the user table with the shop_id
                $updateSql = 'UPDATE users SET FK1_SHOP_ID = :shop_id WHERE USER_ID = :user_id';
                $updateStid = oci_parse($conn, $updateSql);
                oci_bind_by_name($updateStid, ':shop_id', $shop_id);
                oci_bind_by_name($updateStid, ':user_id', $user_id);
                oci_execute($updateStid);

                echo "<div class='alert alert-success'>Shop added successfully and user table updated!</div>";
                oci_free_statement($updateStid);
            } else {
                $e = oci_error($stid);
                echo "<div class='alert alert-danger'>Error: " . htmlentities($e['message'], ENT_QUOTES) . "</div>";
            }

            oci_free_statement($stid);
        } else {
            echo "<div class='alert alert-danger'>Failed to upload logo.</div>";
        }
    } else {
        echo "<div class='alert alert-danger'>Logo upload error: " . $_FILES['logo']['error'] . "</div>";
    }
}

oci_close($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Add Shop</title>
  <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
  <?php include("trader-navbar.php") ?>
  <div class="container mt-5">
    <h2>Add Shop</h2>
    <form action="" method="POST" enctype="multipart/form-data">
      <div class="form-group">
        <label for="shop_name">Shop Name</label>
        <input type="text" class="form-control" id="shop_name" name="shop_name" required>
      </div>
      <div class="form-group">
        <label for="logo">Logo</label>
        <input type="file" class="form-control-file" id="logo" name="logo" required>
      </div>
      <div class="form-group">
        <label for="location">Location</label>
        <input type="text" class="form-control" id="location" name="location" required>
      </div>
      <div class="form-group">
        <label for="description">Description</label>
        <textarea class="form-control" id="description" name="description" rows="3" required></textarea>
      </div>
      <button type="submit" class="btn btn-primary">Add Shop</button>
    </form>
  </div>
</body>
</html>
