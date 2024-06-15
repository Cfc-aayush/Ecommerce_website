<?php
session_start();
$conn = oci_connect('AAYUSH', 'Goodluck#123', '//localhost/xe');
if (!$conn) {
    $e = oci_error();
    trigger_error(htmlentities($e['message'], ENT_QUOTES), E_USER_ERROR);
}

$shop_id = isset($_GET['shop_id']) ? $_GET['shop_id'] : '';
$user_id = $_SESSION['user_id'];

// Fetch product data along with wishlist status
$sql = "
WITH Wishlist_Check AS (
  SELECT 
    pw.FK1_PRODUCT_ID,
    COUNT(*) AS IS_IN_WISHLIST
  FROM 
    PRODUCT_WISHLIST pw
    JOIN WISHLIST w ON pw.FK2_WISHLIST_ID = w.WISHLIST_ID
  WHERE 
    w.FK1_USER_ID = :user_id
  GROUP BY 
    pw.FK1_PRODUCT_ID
)
SELECT 
  p.*, 
  i.IMAGE,
  NVL(wc.IS_IN_WISHLIST, 0) AS IN_WISHLIST
FROM 
  PRODUCT p 
  JOIN PRODUCT_IMAGE i ON p.PRODUCT_ID = i.FK1_PRODUCT_ID
  LEFT JOIN Wishlist_Check wc ON p.PRODUCT_ID = wc.FK1_PRODUCT_ID
WHERE 
  p.FK2_SHOP_ID = :shop_id
  AND p.STATUS = 1";

$stid1 = oci_parse($conn, $sql);

oci_bind_by_name($stid1, ':user_id', $user_id);
oci_bind_by_name($stid1, ':shop_id', $shop_id);

oci_execute($stid1);
$products = [];
while ($row = oci_fetch_assoc($stid1)) {
    $products[] = $row;
}

// Close database connection
oci_close($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product List</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css' rel='stylesheet'>
    <link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css'>
    <style>
        .card-img-overlay {
            pointer-events: none;
        }
        .card-link.like {
            pointer-events: auto;
        }
        .form-select {
            margin-bottom: 20px;
        }
        a {
            color: black;
        }
        .card-img {
            max-height: 250px;
            object-fit: cover;
        }
    </style>
</head>
<body>
    <?php include('navbar.php'); ?>
    <div class='container'>
        <div class='row'>
            <?php foreach ($products as $product): ?>
                <div class='col-md-4'>
                    <div class='container'>
                        <div class='row mb-4'>
                            <div class=''>
                                <div class='card'>
                                    <img class='card-img' src='<?php echo htmlspecialchars($product['IMAGE']); ?>' alt='Product Image'>
                                    <div class='card-img-overlay d-flex justify-content-end'>
                                        <a href='javascript:void(0);' class='card-link text-danger like'>
                                            <div class='favorite-icon' onclick='toggleFavorite(this)' data-product-id='<?php echo htmlspecialchars($product['PRODUCT_ID']); ?>'>
                                                <i class='<?php echo $product['IN_WISHLIST'] ? 'fas' : 'far'; ?> fa-heart'></i>
                                            </div>
                                        </a>
                                    </div>
                                    <div class='card-body'>
                                        <a href="product.php?product_id=<?php echo htmlspecialchars($product['PRODUCT_ID']); ?>"><h4 class="card-title"><?php echo htmlspecialchars($product['PRODUCT_NAME']); ?></h4></a>
                                        <p class='card-text'><?php echo htmlspecialchars($product['DESCRIPTION']); ?></p>
                                        <div class='buy d-flex justify-content-between align-items-center'>
                                            <div class='price text-success'><h5 class='mt-4'><?php echo "$" . htmlspecialchars($product['PRODUCT_PRICE']); ?></h5></div>
                                            <a href='javascript:void(0);' class='btn btn-danger mt-3' onclick="addToCart(<?php echo htmlspecialchars($product['PRODUCT_ID']); ?>)"><i class='fas fa-shopping-cart'></i> Add to Cart</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <script>
    function toggleFavorite(element) {
    const heartIcon = element.querySelector("i");
    const productId = element.getAttribute("data-product-id");
    const isAdding = heartIcon.classList.contains("far"); // If it has "far", it means we're adding to wishlist

    // Toggle heart icon
    if (isAdding) {
        heartIcon.classList.remove("far");
        heartIcon.classList.add("fas");
    } else {
        heartIcon.classList.remove("fas");
        heartIcon.classList.add("far");
    }

    // Send AJAX request to add or remove from wishlist
    const xhr = new XMLHttpRequest();
    xhr.open("POST", "add_to_wishlist.php", true);
    xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");

    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4 && xhr.status === 200) {
            if (xhr.responseText === "User not logged in") {
                // Revert heart icon change
                if (isAdding) {
                    heartIcon.classList.remove("fas");
                    heartIcon.classList.add("far");
                } else {
                    heartIcon.classList.remove("far");
                    heartIcon.classList.add("fas");
                }
                alert("Please log in to add items to your wishlist.");
            } else {
                alert(xhr.responseText); // Log response from the server
            }
        }
    };

    xhr.send(`product_id=${productId}&action=${isAdding ? 'add' : 'remove'}`);
}

    function addToCart(productId) {
        const xhr = new XMLHttpRequest();
        xhr.open("POST", "add_to_cart.php", true);
        xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");

        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4 && xhr.status === 200) {
                alert(xhr.responseText);
            }
        };

        xhr.send("product_id=" + productId);
    }
    </script>
</body>
</html>
