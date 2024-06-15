<?php
$conn = oci_connect('AAYUSH', 'Goodluck#123', '//localhost/xe');
if (!$conn) {
    $e = oci_error();
    trigger_error(htmlentities($e['message'], ENT_QUOTES), E_USER_ERROR);
}

// Fetch products
$sql = "
SELECT p.*, i.IMAGE,
CASE 
  WHEN (SELECT COUNT(*) 
        FROM PRODUCT_WISHLIST pw 
        JOIN WISHLIST w ON pw.FK2_WISHLIST_ID = w.WISHLIST_ID 
        WHERE w.FK1_USER_ID = :user_id 
        AND pw.FK1_PRODUCT_ID = p.PRODUCT_ID) > 0
  THEN 1
  ELSE 0
END AS IN_WISHLIST
FROM PRODUCT p 
JOIN Product_Image i ON p.PRODUCT_ID = i.FK1_PRODUCT_ID
WHERE p.STATUS = 1
";


$stid = oci_parse($conn, $sql);
oci_bind_by_name($stid, ':user_id', $_SESSION['user_id']);
oci_execute($stid);

$products = []; // Array to store products

while ($row = oci_fetch_assoc($stid)) {
    $product = $row;
    $product['image'] = isset($row['IMAGE']) ? $row['IMAGE'] : null;
    $products[] = $product;
}

// Close database connection
oci_close($conn);
?>
 <?php
// Limit the number of products to 6
$limitedProducts = array_slice($products, 0, 6);
?>

<style>
    a {
  color: black; /* Sets text color to black */
}

   
    .scoped-bootstrap .card-img-top {
        max-height: 250px;
        object-fit: cover;
    }


</style>

  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/assets/owl.carousel.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/assets/owl.theme.default.min.css">
<div class="scoped-bootstrap container">
    <h2 style="text-align: center; font-weight:600; margin-bottom:50px; margin-top:50px;">Some of our best sellers</h2>
    <div class="scoped-bootstrap row">
   
<?php foreach ($limitedProducts as $product): ?>
    <div class="scoped-bootstrap col-md-4">
        <div class="scoped-bootstrap card mb-4">
            <img class="scoped-bootstrap card-img-top" src="<?php echo $product['IMAGE']; ?>" alt="Product Image">
            <div class="scoped-bootstrap card-img-overlay d-flex justify-content-end">
                <a href="javascript:void(0);" class="scoped-bootstrap card-link text-danger like">
                    <div class="scoped-bootstrap favorite-icon" onclick="toggleFavorite(this)" data-product-id="<?php echo $product['PRODUCT_ID']; ?>">
                        <i class="scoped-bootstrap <?php echo $product['IN_WISHLIST'] ? 'fas' : 'far'; ?> fa-heart"></i>
                    </div>
                </a>
            </div>
            <div class="scoped-bootstrap card-body">
                <a href="product.php?product_id=<?php echo $product['PRODUCT_ID']; ?>"><h4 class="scoped-bootstrap card-title"><?php echo $product['PRODUCT_NAME']; ?></h4></a>
                <p class="scoped-bootstrap card-text"><?php echo $product['DESCRIPTION']; ?></p>
                <div class="scoped-bootstrap buy d-flex justify-content-between align-items-center">
                    <div class="scoped-bootstrap price text-success"><h5 class="scoped-bootstrap mt-4"><?php echo '$' . $product['PRODUCT_PRICE']; ?></h5></div>
                    <a href="javascript:void(0);" class="scoped-bootstrap btn btn-danger mt-3" onclick="addToCart(<?php echo $product['PRODUCT_ID']; ?>)">
                        <i class="scoped-bootstrap fas fa-shopping-cart"></i> Add to Cart
                    </a>
                </div>
            </div>
        </div>
    </div>
<?php endforeach; ?>

    </div>
</div>

