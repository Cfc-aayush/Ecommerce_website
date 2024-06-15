<?php
$conn = oci_connect('AAYUSH', 'Goodluck#123', '//localhost/xe');

if (!$conn) {
    $e = oci_error();
    trigger_error(htmlentities($e['message'], ENT_QUOTES), E_USER_ERROR);
}

if (isset($_GET['product_id'])) {
    $product_id = $_GET['product_id'];

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $product_name = $_POST['product_name'];
        $description = $_POST['description'];
        $stock_left = $_POST['stock_left'];
        $product_price = $_POST['product_price'];
        $min_order = $_POST['min_order'];
        $max_order = $_POST['max_order'];
        $allergy = $_POST['allergy'];
        $status = $_POST['status'];
        $image = $_FILES['image']['name'];

        if ($image) {
            $target_dir = "uploads/";
            $target_file = $target_dir . basename($_FILES['image']['name']);
            move_uploaded_file($_FILES['image']['tmp_name'], $target_file);
        }

        $sql = "UPDATE Product 
                SET product_name = :product_name, 
                    description = :description, 
                    stock_left = :stock_left, 
                    product_price = :product_price, 
                    min_order = :min_order, 
                    max_order = :max_order, 
                    allergy = :allergy, 
                    status = :status 
                WHERE product_id = :product_id";
        $stid = oci_parse($conn, $sql);

        oci_bind_by_name($stid, ':product_name', $product_name);
        oci_bind_by_name($stid, ':description', $description);
        oci_bind_by_name($stid, ':stock_left', $stock_left);
        oci_bind_by_name($stid, ':product_price', $product_price);
        oci_bind_by_name($stid, ':min_order', $min_order);
        oci_bind_by_name($stid, ':max_order', $max_order);
        oci_bind_by_name($stid, ':allergy', $allergy);
        oci_bind_by_name($stid, ':status', $status);
        oci_bind_by_name($stid, ':product_id', $product_id);

        if (oci_execute($stid)) {
            header('Location: Trader_home.php');
        } else {
            $e = oci_error($stid);
            echo "Error updating product: " . htmlentities($e['message'], ENT_QUOTES);
        }

        oci_free_statement($stid);

        if ($image) {
            $sql = "UPDATE Product_Image SET image = :image WHERE fk1_product_id = :product_id";
            $stid = oci_parse($conn, $sql);
            oci_bind_by_name($stid, ':image', $target_file);
            oci_bind_by_name($stid, ':product_id', $product_id);
            oci_execute($stid);
            oci_free_statement($stid);
        }

    } else {
        $sql = "SELECT product_name, description, stock_left, product_price, min_order, max_order, allergy, status 
                FROM Product WHERE product_id = :product_id";
        $stid = oci_parse($conn, $sql);
        oci_bind_by_name($stid, ':product_id', $product_id);
        oci_execute($stid);
        $product = oci_fetch_assoc($stid);
        oci_free_statement($stid);

        $sql = "SELECT image FROM Product_Image WHERE fk1_product_id = :product_id";
        $stid = oci_parse($conn, $sql);
        oci_bind_by_name($stid, ':product_id', $product_id);
        oci_execute($stid);
        $image = oci_fetch_assoc($stid);
        oci_free_statement($stid);
    }
} else {
    echo "Product ID not provided.";
    exit();
}

oci_close($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Product</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 40px;
            background-color: #f4f4f4;
        }
        .form-body {
            background: #fff;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            max-width: 400px;
            margin: auto;
        }
        label {
            display: block;
            margin-bottom: 10px;
            font-weight: bold;
        }
        input[type="text"],
        textarea,
        input[type="number"],
        input[type="file"] {
            width: 100%;
            padding: 10px;
            margin-bottom: 20px;
            border: 1px solid #ccc;
            border-radius: 5px;
            max-width: 95%;
            display: block;
            margin: auto;
        }
        input[type="submit"] {
            background: #28a745;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
        }
        input[type="submit"]:hover {
            background: #218838;
        }
    </style>
</head>
<body>

<?php if ($_SERVER['REQUEST_METHOD'] === 'GET'): ?>
    <div class="form-body">
        <form method="POST" enctype="multipart/form-data">
            <label for="product_name">Product Name:</label>
            <input type="text" name="product_name" id="product_name" value="<?php echo htmlspecialchars($product['PRODUCT_NAME']); ?>" required><br>

            <label for="description">Description:</label>
            <textarea name="description" id="description" required><?php echo htmlspecialchars($product['DESCRIPTION']); ?></textarea><br>

            <label for="stock_left">Stock Left:</label>
            <input type="number" name="stock_left" id="stock_left" value="<?php echo htmlspecialchars($product['STOCK_LEFT']); ?>" required><br>

            <label for="product_price">Product Price:</label>
            <input type="number" name="product_price" id="product_price" value="<?php echo htmlspecialchars($product['PRODUCT_PRICE']); ?>" required><br>

            <label for="min_order">Minimum Order:</label>
            <input type="number" name="min_order" id="min_order" value="<?php echo htmlspecialchars($product['MIN_ORDER']); ?>" required><br>

            <label for="max_order">Maximum Order:</label>
            <input type="number" name="max_order" id="max_order" value="<?php echo htmlspecialchars($product['MAX_ORDER']); ?>" required><br>

            <label for="allergy">Allergy Information:</label>
            <input type="text" name="allergy" id="allergy" value="<?php echo htmlspecialchars($product['ALLERGY']); ?>"><br>

            <label for="status">Status:</label>
            <input type="number" name="status" id="status" value="<?php echo htmlspecialchars($product['STATUS']); ?>" required><br>

            <label for="image">Product Image:</label>
            <input type="file" name="image" id="image"><br>
            <?php if (!empty($image['IMAGE'])): ?>
                <img src="<?php echo htmlspecialchars($image['IMAGE']); ?>" alt="Product Image" style="max-width: 100%; height: auto;">
            <?php endif; ?><br>

            <input type="submit" value="Update Product">
        </form>
    </div>
<?php endif; ?>

</body>
</html>
