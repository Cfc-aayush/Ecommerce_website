<?php
$conn = oci_connect('AAYUSH', 'Goodluck#123', '//localhost/xe');
if (!$conn) {
    $e = oci_error();
    trigger_error(htmlentities($e['message'], ENT_QUOTES), E_USER_ERROR);
}
$is_logged_in = isset($_SESSION['first_name']) && !empty($_SESSION['first_name']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

  // Check which form is being submitted
  if (isset($_POST['form_name'])) {
      if ($_POST['form_name'] === 'search_form') {
              $searchTerm = $_POST['search_term'];
              header('Location: search.php?term=' . urlencode($searchTerm));  // Ensure the term is properly encoded
              exit();  // Make sure to exit after a header redirection
          
      } elseif ($_POST['form_name'] === 'review_form' && isset($_POST['submit_review'])) {
          // Handle review form submission
          if(!$is_logged_in){
            echo '<script>alert("User not logged in"); window.location.href = "login.php";</script>';

            exit;
          }
          $review = filter_input(INPUT_POST, 'review', FILTER_SANITIZE_STRING);
          $rating = filter_input(INPUT_POST, 'rating', FILTER_SANITIZE_NUMBER_INT);
          $user_id = $_SESSION['user_id'];

          // Ensure $product_id is set (assuming it's coming from somewhere in your session or code)
          if (isset($product_id)) {
            // Get product_id from GET method
if (isset($_GET['product_id'])) {
  $product_id = filter_input(INPUT_GET, 'product_id', FILTER_SANITIZE_NUMBER_INT);
} else {
  echo "Product not provided.";
  exit(); // Stop further execution
}

              // Insert the review into the database
              $insertReviewQuery = 'INSERT INTO REVIEW (REVIEWS, RATING, FK1_PRODUCT_ID, FK2_USER_ID) 
                                    VALUES (:review, :rating, :product_id, :user_id)';
              $stid = oci_parse($conn, $insertReviewQuery);
              oci_bind_by_name($stid, ':review', $review);
              oci_bind_by_name($stid, ':rating', $rating);
              oci_bind_by_name($stid, ':product_id', $product_id);
              oci_bind_by_name($stid, ':user_id', $user_id);
              oci_execute($stid);
              oci_free_statement($stid);

              echo '<script>alert("Review submitted successfully."); window.location.href = "product.php?product_id='.$product_id.'";</script>';

          } else {
              // Handle the case where $product_id is not set
              echo 'Product ID is not set';
          }
      }
  }
}



// Retrieve the cart ID for the current user
$cart_id_sql = "
SELECT CART_ID
FROM CART
WHERE FK2_USER_ID = :user_id
";

$cart_id_stid = oci_parse($conn, $cart_id_sql);
oci_bind_by_name($cart_id_stid, ':user_id', $_SESSION['user_id']);
oci_execute($cart_id_stid);

if ($is_logged_in) {
    $cart_id_row = oci_fetch_assoc($cart_id_stid);
    if ($cart_id_row) {
        $cart_id = $cart_id_row['CART_ID'];

        // Store the cart ID in the session
        $_SESSION['cartId'] = $cart_id;
    } else {
        // Handle the case where there is no cart ID found
        $_SESSION['cartId'] = null;
    }
} else {
    // Handle the case where the user is not logged in
    // You might want to set $cart_item_count to 0 or handle it differently
    $cart_item_count = 0;
    $_SESSION['cartId'] = null;
}

// Get cart item count for the current user
$cart_count_sql = "
SELECT COUNT(*) AS ITEM_COUNT
FROM PRODUCT_CART pc
JOIN CART c ON pc.FK2_CART_ID = c.CART_ID
WHERE c.FK2_USER_ID = :user_id
";

$cart_count_stid = oci_parse($conn, $cart_count_sql);
oci_bind_by_name($cart_count_stid, ':user_id', $_SESSION['user_id']);
oci_execute($cart_count_stid);

$cart_count_row = oci_fetch_assoc($cart_count_stid);
if ($cart_count_row) {
    $cart_item_count = $cart_count_row['ITEM_COUNT'];
} else {
    $cart_item_count = 0; // Handle the case where there are no cart items
}

// Close database connection
oci_close($conn);
?>

<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
<link rel="stylesheet" href="navbar.css">

<style>
  button {
  background: none; /* Remove background color */
  border: none; /* Remove border */
  padding: 0; /* Remove padding */
  font: inherit; /* Inherit font properties from parent */
  cursor: pointer; /* Maintain clickable cursor */
  outline: none; /* Remove outline on focus */
}
.user-link {
  display: flex; /* Make the user link a flex container */
  align-items: center; /* Align items vertically */
  text-decoration: none;
  color: inherit;
}

.user-link i {
  margin-right: 8px;
  font-size: 1.2em;
}

.user-link p {
  margin: 0;
}
.user-detail {
  position: relative;
}

.dropdown .flex-row {
  display: flex;
  align-items: center;
}

.dropdown .flex-row a {
  margin-right: 5px;
}

.dropdown-content {
  display: none;
  position: absolute;
  top: 40px;
  right: 0;
  background-color: white;
  color: black;
  box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
  z-index: 1;
  border-radius: 5px;
  overflow: hidden;
}

.dropdown-content a {
  padding: 10px 20px;
  display: block;
  text-decoration: none;
  color: black;
}

.dropdown-content a:hover {
  background-color: #ddd;
}

.show {
  display: block;
}

#mainHeader .user i {
  margin-right: 0;
  font-size: 24px;
}
.cart-badge {
  background: red;
  padding: 1px 5px;
  border-radius: 50%;
  color: white;
  position: absolute;
  top: -10px; /* Adjust this value as needed */
  right: -10px; /* Adjust this value as needed */
  font-size: 12px;
}
.cart-icon {
  margin-right: 15px;
  position: relative;
}

.fa-user {
  margin-right: 15px;
}

.user-name {
  margin-left: 15px;
}
</style>
<link rel="preconnect" href="https://fonts.gstatic.com">
    <link href="https://fonts.googleapis.com/css2?family=Karla&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.15.2/css/all.css"
        integrity="sha384-vSIIfh2YWi9wW0r9iZe7RJPrKwp6bG+s9QZMoITbCckVJqGCCRhc+ccxNcdpHuYu" crossorigin="anonymous">
        <script src="https://code.jquery.com/jquery-3.2.1.slim.min.js" integrity="sha384-KJ3o2DKtIkvYIK3UENzmM7KCkRr/rE9/Qpg6aAZGJwFDMVNA/GpGFF93hXpG5KkN" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/popper.js@1.12.9/dist/umd/popper.min.js" integrity="sha384-ApNbgh9B+Y1QKtv3Rn7W3mgPxhU9K/ScQsAP7hUibX39j7fakFPskvXusvfa0b4Q" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/js/bootstrap.min.js" integrity="sha384-JZR6Spejh4U02d8jOt6vLEHfe/JQGiRRSQQxSfFWpi1MquVdAyjUar5+76PVCmYl" crossorigin="anonymous"></script>
<header id="mainHeader">
  <div class="main-nav">
    <a href="home.php" class="logo"><img src="image/LogoDark.png"></a>
    <form class="search-box" method="post">
      <input name="search_term" class="search-txt" type="text" placeholder="Type here to Search">
      <input type="hidden" name="form_name" value="search_form">
      <button type="submit" class="search-btn"><i class="fas fa-search"></i></button>
    </form>
    <div class="user">
    <a href="product_cart.php" class="cart-icon">
        <i class="fa fa-shopping-cart" aria-hidden="true"></i>
        <span class="cart-badge" id="cartCount"><?php echo $cart_item_count;?></span>
      </a>
      <div class="user-detail">
        <div class="dropdown">
        <div class="user-link" onclick="toggleDropdown()">
  <i class="fa fa-user" aria-hidden="true"></i>
  <p>
    <?php
    if ($is_logged_in) {
        echo htmlspecialchars($_SESSION['first_name']); // Print the user's first name
    } else {
        echo 'Guest'; // Print 'Guest' if the session value is not set
    }
    ?>
  </p>
</div>
<div class="dropdown-content">
  <a href="<?php echo $is_logged_in ? 'user_profile.php' : 'login.php'; ?>">
    <?php echo $is_logged_in ? 'Profile' : 'Login'; ?> <!-- Change link text based on login status -->
  </a>
  <a href="favouritepage.php">Wishlist</a>
  <?php if ($is_logged_in): ?> <!-- Display Logout link only when logged in -->
    <a href="logout.php">Logout</a>
  <?php endif; ?>
</div>

        </div>
      </div>
    </div>
  </div>
</header>

<script>
  function toggleDropdown() {
    var dropdownContent = document.querySelector('.dropdown-content');
    if (dropdownContent.style.display === 'block') {
      dropdownContent.style.display = 'none';
    } else {
      dropdownContent.style.display = 'block';
    }
  }

  // Close the dropdown when clicking outside of it
  window.onclick = function(event) {
    if (!event.target.closest('.dropdown')) {
      var dropdowns = document.getElementsByClassName('dropdown-content');
      for (var i = 0; i < dropdowns.length; i++) {
        var openDropdown = dropdowns[i];
        if (openDropdown.style.display === 'block') {
          openDropdown.style.display = 'none';
        }
      }
    }
  }
</script>

