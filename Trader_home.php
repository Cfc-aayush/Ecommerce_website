<?php
session_start();

if(!isset($_SESSION['role']) || $_SESSION['role'] != "trader") {
    echo '<script>alert("You are not authorized to access this page."); window.location.href = "login.php";</script>';
    exit(); // Stop further execution
}

$conn = oci_connect('AAYUSH', 'Goodluck#123', '//localhost/xe');

if (!$conn) {
    $e = oci_error();
    trigger_error(htmlentities($e['message'], ENT_QUOTES), E_USER_ERROR);
}

$user_id = $_SESSION['user_id'];

$sql1 = "SELECT p.PRODUCT_NAME, p.DESCRIPTION, p.PRODUCT_ID, p.PRODUCT_PRICE, i.IMAGE
         FROM Product p
         JOIN Shop s ON s.SHOP_ID = p.FK2_SHOP_ID
         JOIN users u ON u.FK1_SHOP_ID = s.SHOP_ID
         JOIN Product_Image i ON p.PRODUCT_ID = i.FK1_PRODUCT_ID
         WHERE u.USER_ID = :user_id AND p.STATUS = 1";

$stid1 = oci_parse($conn, $sql1);
oci_bind_by_name($stid1, ':user_id', $user_id);
oci_execute($stid1);

$products = []; // Array to store products with images

while ($row = oci_fetch_assoc($stid1)) {
    $product = $row; // Store entire product data in a variable

    // Check if there's an image associated with the product
    if (isset($row['IMAGE'])) {
        $product['image'] = $row['IMAGE']; // Add image path to product data
    } else {
        $product['image'] = null; // Set image to null if no image found
    }

    $products[] = $product;
}

// Close database connection
oci_close($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trader Home Page</title>
    <link rel="stylesheet" href="trader-navbar.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css' rel='stylesheet' integrity='sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH' crossorigin='anonymous'>
    <link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css'>
    <link href='https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css' rel='stylesheet'>
</head>
<body>
<?php include('trader-navbar.php')?>
<div id="your_product" class="content active mt-4">
    <div class="container">
        <div class="row">
            <?php
            foreach ($products as $product) {
                echo "<div class='col-md-4'>";
                echo "  <div class='container'>";
                echo "    <div class='row mb-4'>";
                echo "      <div class=''>";
                echo "        <div class='card'>";
                echo "          <img class='card-img' src='". $product['image'] ."' alt='Product Image'>";
                echo "          <div class='card-body'>";
                echo "            <h2 class='card-title'>" . $product['PRODUCT_NAME'] . "</h2>";
                echo "            <p class='card-text'>" . $product['DESCRIPTION'] . "</p>";
                echo "             <div class='buy d-flex justify-content-between align-items-center'>";
                echo "<a href='edit_product.php?product_id=" . $product['PRODUCT_ID'] . "' class='btn btn-primary mt-3'>Edit Product</a>";
                echo "<a href='delete_product.php?product_id=" . $product['PRODUCT_ID'] . "' class='btn btn-danger mt-3'>Delete Product</a>";
                echo "            </div>";
                echo "          </div>";
                echo "        </div>";
                echo "      </div>";
                echo "    </div>";
                echo "  </div>";
                echo "</div>";
            }
            ?>
        </div>
    </div>
</div>

</body>
</html>
