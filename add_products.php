<?php 
session_start();
$conn = oci_connect('AAYUSH', 'Goodluck#123', '//localhost/xe');

if (!$conn) {
    $e = oci_error();
    trigger_error(htmlentities($e['message'], ENT_QUOTES), E_USER_ERROR);
}

$user_id = $_SESSION['user_id'];
//query for getting shop
$sql1 = "SELECT s.shop_name, s.shop_id
         FROM shop s 
         JOIN users u ON u.FK1_SHOP_ID = s.shop_id 
         WHERE u.user_id = :user_id
         and s.VERIFICATION=1";

$stid = oci_parse($conn, $sql1);
oci_bind_by_name($stid, ':user_id', $user_id);
oci_execute($stid);

// Fetch the shop names and ids into an array
$shops = [];
while ($row = oci_fetch_array($stid, OCI_ASSOC+OCI_RETURN_NULLS)) {
    $shops[] = ['id' => $row['SHOP_ID'], 'name' => $row['SHOP_NAME']];
}

oci_free_statement($stid);
oci_close($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <title>Add Product</title>
</head>
<body>
<?php
        include('trader-navbar.php');
    ?>
<div class="container mt-5">
    
    <h2>Add Product</h2>
    <?php
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $conn = oci_connect('AAYUSH', 'Goodluck#123', '//localhost/xe');

        if (!$conn) {
            $e = oci_error();
            trigger_error(htmlentities($e['message'], ENT_QUOTES), E_USER_ERROR);
        }

        $product_name = $_POST['product_name'];
        $description = $_POST['description'];
        $stock_left = $_POST['stock_left'];
        $product_price = $_POST['product_price'];
        $min_order = $_POST['min_order'];
        $max_order = $_POST['max_order'];
        $allergy = $_POST['allergy'];
        $shop_id = $_POST['shop'];
        $status = 0; // Default status
        $discount_id = null; // Replace with the actual discount ID or logic to get it

        // Handle the image upload
        $image = $_FILES['image']['name'];
        $target_dir = "uploads/";
        $target_file = $target_dir . basename($image);

        if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
            // Prepare the SQL query to insert product data
            $sql = "INSERT INTO Product (PRODUCT_NAME, DESCRIPTION, STOCK_LEFT, PRODUCT_PRICE, MIN_ORDER, MAX_ORDER, ALLERGY, FK1_DISCOUNT_ID, FK2_SHOP_ID, STATUS) 
                    VALUES (:product_name, :description, :stock_left, :product_price, :min_order, :max_order, :allergy, :discount_id, :shop_id, :status)";

            $stid = oci_parse($conn, $sql);

            oci_bind_by_name($stid, ':product_name', $product_name);
            oci_bind_by_name($stid, ':description', $description);
            oci_bind_by_name($stid, ':stock_left', $stock_left);
            oci_bind_by_name($stid, ':product_price', $product_price);
            oci_bind_by_name($stid, ':min_order', $min_order);
            oci_bind_by_name($stid, ':max_order', $max_order);
            oci_bind_by_name($stid, ':allergy', $allergy);
            oci_bind_by_name($stid, ':discount_id', $discount_id);
            oci_bind_by_name($stid, ':shop_id', $shop_id);
            oci_bind_by_name($stid, ':status', $status);

            $result = oci_execute($stid, OCI_COMMIT_ON_SUCCESS);

            if ($result) {
                // Get the last inserted product ID
                $sql2 = "SELECT increase_id.CURRVAL as last_product_id FROM dual";
                $stid2 = oci_parse($conn, $sql2);
                oci_execute($stid2);
                $row = oci_fetch_array($stid2, OCI_ASSOC);
                $last_product_id = $row['LAST_PRODUCT_ID'];

                // Prepare the SQL query to insert image data
                $sql3 = "INSERT INTO PRODUCT_IMAGE (IMAGE, FK1_PRODUCT_ID) VALUES (:image, :product_id)";
                $stid3 = oci_parse($conn, $sql3);
                oci_bind_by_name($stid3, ':image', $target_file);
                oci_bind_by_name($stid3, ':product_id', $last_product_id);

                $result_image = oci_execute($stid3, OCI_COMMIT_ON_SUCCESS);

                if ($result_image) {
                    echo "<div class='alert alert-success'>Product and image added successfully!</div>";
                } else {
                    $e = oci_error($stid3);
                    echo "<div class='alert alert-danger'>Error adding image: " . htmlentities($e['message']) . "</div>";
                }

                oci_free_statement($stid2);
                oci_free_statement($stid3);
            } else {
                $e = oci_error($stid);
                echo "<div class='alert alert-danger'>Error adding product: " . htmlentities($e['message']) . "</div>";
            }

            oci_free_statement($stid);
        } else {
            echo "<div class='alert alert-danger'>Sorry, there was an error uploading your file.</div>";
        }

        oci_close($conn);
    }
    ?>
    <form action="" method="POST" enctype="multipart/form-data">
        <div class="form-group">
            <label for="shop">Select Shop</label>
            <select class="form-control" id="shop" name="shop" required>
                <option value="" disabled selected>Select a shop</option>
                <?php foreach ($shops as $shop): ?>
                    <option value="<?= htmlspecialchars($shop['id']) ?>"><?= htmlspecialchars($shop['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label for="product_name">Product Name</label>
            <input type="text" class="form-control" id="product_name" name="product_name" required>
        </div>
        <div class="form-group">
            <label for="description">Description</label>
            <textarea class="form-control" id="description" name="description" rows="3" required></textarea>
        </div>
        <div class="form-group">
            <label for="stock_left">Stock Left</label>
            <input type="number" class="form-control" id="stock_left" name="stock_left" required>
        </div>
        <div class="form-group">
            <label for="product_price">Product Price</label>
            <input type="number" class="form-control" id="product_price" name="product_price" step="0.01" required>
        </div>
        <div class="form-group">
            <label for="min_order">Min Order</label>
            <input type="number" class="form-control" id="min_order" name="min_order" required>
        </div>
        <div class="form-group">
            <label for="max_order">Max Order</label>
            <input type="number" class="form-control" id="max_order" name="max_order" required>
        </div>
        <div class="form-group">
            <label for="allergy">Allergy Information</label>
            <input type="text" class="form-control" id="allergy" name="allergy" required>
        </div>
        <div class="form-group">
            <label for="image">Product Image</label>
            <input type="file" class="form-control-file" id="image" name="image" required>
        </div>
        <button type="submit" class="btn btn-primary">Add Product</button>
    </form>
</div>
</body>
</html>
