<?php
$conn = oci_connect('aditya', 'Ramidara12', '//localhost/xe');

if (!$conn) {
    $e = oci_error();
    trigger_error(htmlentities($e['message'], ENT_QUOTES), E_USER_ERROR);
}

// Get user_id from GET method
if (isset($_GET['user_id'])) {
    $user_id = $_GET['user_id'];
} else {
    // Handle case where user_id is not provided in the GET method
    echo "User ID not provided.";
    exit(); // Stop further execution
}

$sql1 = "SELECT p.product_name, p.Description, p.product_id, p.product_Price, i.IMAGE
         FROM Product p
         JOIN Shop s ON s.Shop_Id = p.FK2_SHOP_ID
         JOIN users u ON u.FK1_SHOP_ID = s.Shop_Id
         JOIN Product_Image i ON p.product_id = i.FK1_PRODUCT_ID
         WHERE u.user_Id = $user_id";

$stid1 = oci_parse($conn, $sql1);
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
    <title>Navbar Example</title>
    <link rel="stylesheet" href="trader-navbar.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css' rel='stylesheet' integrity='sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH' crossorigin='anonymous'>
    <link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css'>
    <link href='https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css' rel='stylesheet'>
</head>
<body>

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
