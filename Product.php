<?php
session_start();

// Oracle Database connection
$conn = oci_connect('AAYUSH', 'Goodluck#123', '//localhost/xe');
if (!$conn) {
    $e = oci_error();
    trigger_error(htmlentities($e['message'], ENT_QUOTES), E_USER_ERROR);
}

// Get product_id from GET method
if (isset($_GET['product_id'])) {
    $product_id = filter_input(INPUT_GET, 'product_id', FILTER_SANITIZE_NUMBER_INT);
} else {
    echo "Product not provided.";
    exit(); // Stop further execution
}


// Prepare and execute SQL statement to fetch product details
$query = 'SELECT p.*, i.IMAGE 
          FROM PRODUCT p 
          JOIN Product_Image i ON p.PRODUCT_ID = i.FK1_PRODUCT_ID 
          WHERE p.PRODUCT_ID = :product_id';
$stid = oci_parse($conn, $query);
oci_bind_by_name($stid, ':product_id', $product_id, -1, SQLT_INT);
oci_execute($stid);

// Fetch the product data
$product = oci_fetch_assoc($stid);

if (!$product) {
    echo "No product found.";
    exit();
}

$query = 'SELECT r.*, u.FIRST_NAME, u.LAST_NAME 
          FROM REVIEW r 
          JOIN USERS u ON r.FK2_USER_ID = u.USER_ID 
          WHERE r.FK1_PRODUCT_ID = :product_id'; 
$stid = oci_parse($conn, $query);
oci_bind_by_name($stid, ':product_id', $product_id, -1, SQLT_INT);
oci_execute($stid);

// Fetch all reviews and calculate total reviews and average rating (with user names)
$reviews = [];
$totalRating = 0;
$totalReviews = 0;

while ($row = oci_fetch_assoc($stid)) {
    $reviews[] = $row;
    $totalRating += $row['RATING'];
    $totalReviews++;
}

$averageRating = $totalReviews > 0 ? $totalRating / $totalReviews : 0;

// Close the statement and connection
oci_free_statement($stid);
oci_close($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product['PRODUCT_NAME']); ?></title>
    <link rel="stylesheet" href="product.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Kumbh+Sans:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="scoped-bootstrap.css">
    <style>
        .card-img-overlay {
            pointer-events: none;
        }
        .card-link.like {
            pointer-events: auto;
        }
    </style>
</head>
<?php include('navbar.php'); ?>
<body>
    <main>
        <article>
            <section class="section product" aria-label="product">
                <div class="container">
                    <div class="product-slides">
                        <div class="slider-banner" data-slider>
                            <figure class="product-banner">
                                <img src="<?php echo htmlspecialchars($product['IMAGE']); ?>" width="600" height="600" loading="lazy" alt="<?php echo htmlspecialchars($product['PRODUCT_NAME']); ?>" class="img-cover">
                            </figure>
                        </div>
                    </div>
                    <div class="product-content">
                        <p class="product-subtitle"><?php echo htmlspecialchars($product['PRODUCT_NAME']); ?></p>
                        <h1 class="h1 product-title"><?php echo htmlspecialchars($product['PRODUCT_NAME']); ?></h1>
                        <div class="rating">
                            <div class="value" style="display: inline-block; vertical-align: middle; margin-right: 10px;">
                                <p style="display: inline-block; margin: 0; vertical-align: middle; color: black; font-size: 20px;">
                                    <?php echo number_format($averageRating, 1); ?>
                                </p>
                                <i class="fa fa-star" style="color: black;" aria-hidden="true"></i>
                            </div>
                            <div class="review" style="display: inline-block; vertical-align: middle; margin-right: 10px;">
                                <p style="display: inline-block; margin: 0; vertical-align: middle; color: black; font-size: 20px;">
                                    <?php echo htmlspecialchars($totalReviews); ?> Reviews
                                </p>
                            </div>
                        </div>
                        <br>
                        <div class="available">
                            <p style="color: black;"><?php echo htmlspecialchars($product['STOCK_LEFT']); ?> pieces available</p>
                        </div>
                        <div class="wrapper">
                            <span class="price" data-total-price>$<?php echo htmlspecialchars($product['PRODUCT_PRICE']); ?></span>
                        </div>
                        <p class="product-text">
                            <?php echo nl2br(htmlspecialchars($product['DESCRIPTION'])); ?>
                        </p>
                            <div class="btn-group">
                                <div class="counter-wrapper">
                                </div>
                                <button type="submit" class="cart-btn">
                                    <ion-icon name="bag-handle-outline" aria-hidden="true"></ion-icon>
                                    <span class="span" onclick="addToCart(<?php echo $product['PRODUCT_ID']; ?>)">Add to cart</span>
                                </button>
                            </div>
                    </div>
                </div>
            </section>
        </article>
    </main>
        <style>
            /* Review Form Styling */
.review-form {
  background-color: #f8f9fa; /* Light background for the form */
  padding: 20px;
  border-radius: 8px; /* Rounded corners */
  box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1); /* Subtle shadow */
  max-width: 80%;
  display: block;
  margin: auto;
}

.review-form label {
  display: block; /* Place labels above inputs */
  margin-bottom: 5px;
  font-weight: 500; /* Slightly bolder label text */
}

.review-form textarea {
  width: 100%;
  padding: 10px;
  margin-bottom: 15px;
  border: 1px solid #ced4da; /* Match Bootstrap's input border */
  border-radius: 4px;
  box-sizing: border-box; /* Include padding and border in element's total width and height */
  resize: vertical; /* Allow vertical resizing only */
}

/* Star Rating Styling */
.rating {
  display: inline-block;
  unicode-bidi: bidi-override;
  direction: rtl;
  font-size: 24px;
  color: #ccc; /* Initial star color (gray) */
}

.rating input[type="radio"] {
  position: absolute;
  left: -9999px; /* Hide the default radio buttons */
}

.rating label {
  display: inline-block;
  cursor: pointer;
  padding: 0 5px;
}

.rating input[type="radio"]:checked ~ label, /* Filled star on hover */
.rating:not(:checked) label:hover,
.rating:not(:checked) label:hover ~ label { 
  color: #f5b041; /* Gold/Yellow color for filled stars */
}

/* Submit Button */
.review-form button[type="submit"] {
  background-color: #007bff; /* Blue background */
  color: white;
  padding: 10px 20px;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  transition: background-color 0.3s ease; /* Smooth transition on hover */
}

.review-form button[type="submit"]:hover {
  background-color: #0056b3; /* Slightly darker blue on hover */
}
        </style>
    <div class="reviews">
    <div class="review-form">
    <h2>Leave a Review</h2>
    <form action="" method="post">
    <input type="hidden" name="form_name" value="review_form">
        <div>
            <label for="review">Review:</label>
            <textarea id="review" name="review" rows="4" cols="50" required></textarea>
        </div>
        <div>
            <label for="rating">Rating:</label>
            <div class="rating">
                <input type="radio" id="star5" name="rating" value="5" required />
                <label for="star5" title="5 stars">★</label>
                <input type="radio" id="star4" name="rating" value="4" />
                <label for="star4" title="4 stars">★</label>
                <input type="radio" id="star3" name="rating" value="3" />
                <label for="star3" title="3 stars">★</label>
                <input type="radio" id="star2" name="rating" value="2" />
                <label for="star2" title="2 stars">★</label>
                <input type="radio" id="star1" name="rating" value="1" />
                <label for="star1" title="1 star">★</label>
            </div>
        </div>
        <input type="hidden" name="product_id" value="<?php echo htmlspecialchars($product_id); ?>">
        <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($_SESSION['user_id']); ?>">
        <div>
            <button type="submit" name="submit_review">Submit Review</button>
        </div>
    </form>
</div>

<style>
    .rating {
        direction: rtl;
        display: flex;
    }

    .rating input {
        display: none;
    }

    .rating label {
        font-size: 2em;
        color: #ddd;
        cursor: pointer;
    }

    .rating label:hover,
    .rating label:hover ~ label,
    .rating input:checked ~ label {
        color: #f5c518;
    }
</style>
<style>
    .review-container {
      display: flex;
      border: 1px solid #ccc;
      border-radius: 10px;
      padding: 20px;
      margin: 20px;
      background-color: #f9f9f9;
      max-width: 700px;
      margin:auto;
      margin-bottom: 8px;

    }
    .user-info {
      width: 20%;
      display: flex;
      flex-direction: column;
      align-items: center;
      margin-right: 20px;
    }
    
    @media only screen and (max-width: 600px) {
        .review-container {
            display: flex;
            border: 1px solid #ccc;
            border-radius: 10px;
            padding: 20px;
            margin: 20px;
            background-color: #f9f9f9;
            flex-direction: column;
        }

        .user-info {
            width: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-right: 0px;
        }
    }

    

    .user-image {
      width: 40px;
      height: 40px;
      border-radius: 50%;
      margin-bottom: 10px;
    }

    .user-name {
      font-weight: bold;
      margin-bottom: 5px;
    }

    .review-date,
    .star-rating {
      font-size: 14px;
      color: #777;
    }

    .review-text {
      flex: 1; /* Makes review text fill remaining space */
      font-size: 16px;
      line-height: 1.5;
      text-align: center;
    }

    .review-item{
        text-align: center;
    }

    .review-list h2{
        margin-top: 50px;
        margin-bottom: 50px;
    }
  </style>
  
    <div class="review-list">

    
    <h2>Reviews</h2>
    <?php foreach ($reviews as $review): ?>
    <div class="review-container">
            <div class="review-item">
                    <div class="user-name"><?php echo htmlspecialchars($review['FIRST_NAME']) . ' ' . htmlspecialchars($review['LAST_NAME']); ?></div>
                    <div class="review-date">Customer</div>
                    <div class="star-rating">⭐ <?php echo htmlspecialchars(number_format($review['RATING'], 1)); ?></div>
                </div>
                <div class="review-text">
                    <p><?php echo $review['REVIEWS']; ?></p>
                </div>
            </div>
    </div>
    <?php endforeach; ?>
</div>

<style>
    .random-products h2{
        margin-top:40px;
        margin-bottom: 40px;
    }
</style>
    <div class="random-products">
         
        <?php include("random_product.php")?>
    </div>
    <script type="module" src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.js"></script>
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
