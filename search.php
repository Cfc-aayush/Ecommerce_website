<?php
session_start();
$conn = oci_connect('AAYUSH', 'Goodluck#123', '//localhost/xe');
if (!$conn) {
    $e = oci_error();
    trigger_error(htmlentities($e['message'], ENT_QUOTES), E_USER_ERROR);
}

$searchTerm = isset($_GET['term']) ? $_GET['term'] : '';
$user_id = $_SESSION['user_id'];

// Sorting parameters
$sort = isset($_GET['sort']) ? $_GET['sort'] : '';
$order = '';

// Determine the sorting order
switch ($sort) {
    case 'price_asc':
        $order = 'ORDER BY p.PRODUCT_PRICE ASC';
        break;
    case 'price_desc':
        $order = 'ORDER BY p.PRODUCT_PRICE DESC';
        break;
    case 'name_asc':
        $order = 'ORDER BY p.PRODUCT_NAME ASC';
        break;
    case 'name_desc':
        $order = 'ORDER BY p.PRODUCT_NAME DESC';
        break;
    default:
        $order = ''; // Default order
}

// Fetch product data along with wishlist status
$sql1 = "
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
WHERE LOWER(p.PRODUCT_NAME) LIKE LOWER(:search_keyword)
AND p.STATUS = 1
$order"; // Apply sorting order

// Bind search term
$search_keyword = "%{$searchTerm}%";
$stid1 = oci_parse($conn, $sql1);
oci_bind_by_name($stid1, ':user_id', $_SESSION['user_id']);
oci_bind_by_name($stid1, ':search_keyword', $search_keyword); 
oci_execute($stid1);

$products = []; // Array to store products with images

while ($row = oci_fetch_assoc($stid1)) {
    $product = $row;
    $product['image'] = isset($row['IMAGE']) ? $row['IMAGE'] : null;
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
  color: black; /* Sets text color to black */
}

   
    .card-img {
        max-height: 250px;
        object-fit: cover;
    }
 

    </style>
</head>
<body>
    <?php include('navbar.php');?>
    <div class='container'>
        <div class='d-flex justify-content-end mt-3'>
            <form class="form-inline" method="GET" action="">
                <input type="hidden" name="term" value="<?php echo htmlspecialchars($searchTerm); ?>">
                <select name="sort" class="form-select" onchange="this.form.submit()">
                    <option value="">Sort By</option>
                    <option value="price_asc" <?php if ($sort == 'price_asc') echo 'selected'; ?>>Price: Low to High</option>
                    <option value="price_desc" <?php if ($sort == 'price_desc') echo 'selected'; ?>>Price: High to Low</option>
                    <option value="name_asc" <?php if ($sort == 'name_asc') echo 'selected'; ?>>Name: A to Z</option>
                    <option value="name_desc" <?php if ($sort == 'name_desc') echo 'selected'; ?>>Name: Z to A</option>
                </select>
            </form>
        </div>
        <div class='row'>
            <?php foreach ($products as $product): ?>
                <div class='col-md-4'>
                    <div class='container'>
                        <div class='row mb-4'>
                            <div class=''>
                                <div class='card'>
                                    <img class='card-img' src='<?php echo $product['IMAGE']; ?>' alt='Product Image'>
                                    <div class='card-img-overlay d-flex justify-content-end'>
                                        <a href='javascript:void(0);' class='card-link text-danger like'>
                                            <div class='favorite-icon' onclick='toggleFavorite(this)' data-product-id='<?php echo $product['PRODUCT_ID']; ?>'>
                                                <i class='<?php echo $product['IN_WISHLIST'] ? 'fas' : 'far'; ?> fa-heart'></i>
                                            </div>
                                        </a>
                                    </div>
                                    <div class='card-body'>
                                    <a href="product.php?product_id=<?php echo $product['PRODUCT_ID']; ?>"><h4 class="card-title"><?php echo $product['PRODUCT_NAME']; ?></h4></a>
                                        <p class='card-text'><?php echo $product['DESCRIPTION']; ?></p>
                                        <div class='buy d-flex justify-content-between align-items-center'>
                                            <div class='price text-success'><h5 class='mt-4'><?php echo "$" . htmlspecialchars($product['PRODUCT_PRICE']); ?></h5></div>
                                            <a href='javascript:void(0);' class='btn btn-danger mt-3' onclick="addToCart(<?php echo $product['PRODUCT_ID']; ?>)"><i class='fas fa-shopping-cart'></i> Add to Cart</a>
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
                alert(xhr.responseText); // Show response from the server
            }
        };

        xhr.send("product_id=" + productId);
    }
    </script>
</body>
</html>
