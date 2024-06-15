<?php
session_start(); // Start the session

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
  echo "User not logged in.";
  exit;
}

$userId = $_SESSION['user_id'];

$conn = oci_connect('AAYUSH', 'Goodluck#123', '//localhost/xe');

if (!$conn) {
  $e = oci_error();
  trigger_error(htmlentities($e['message'], ENT_QUOTES), E_USER_ERROR);
}

// Check if a cart exists for the user
$sqlCart = "SELECT CART_ID FROM CART WHERE FK2_USER_ID = :user_id";
$stidCart = oci_parse($conn, $sqlCart);
oci_bind_by_name($stidCart, ':user_id', $userId);
oci_execute($stidCart);

$cartId = null;
if ($row = oci_fetch_assoc($stidCart)) {
  $cartId = $row['CART_ID'];
  $_SESSION['cartId'] = $cartId;
} else {
  // Create a new cart if not exists
  $sqlCreateCart = "INSERT INTO CART (FK2_USER_ID) VALUES (:user_id)";
  $stidCreateCart = oci_parse($conn, $sqlCreateCart);
  oci_bind_by_name($stidCreateCart, ':user_id', $userId);

  if (oci_execute($stidCreateCart)) {
    oci_commit($conn); // Commit the transaction to ensure the cart is created

    // Get the newly created cart ID using a sequence
    $sqlGetCartId = "SELECT CART_SEQ.CURRVAL AS CART_ID FROM DUAL";  // Replace CART_SEQ with your actual sequence name
    $stidGetCartId = oci_parse($conn, $sqlGetCartId);
    oci_execute($stidGetCartId);

    if ($row = oci_fetch_assoc($stidGetCartId)) {
      $cartId = $row['CART_ID'];
      $_SESSION['cartId'] = $cartId;
    } else {
      echo "Error retrieving cart ID.";
      exit;
    }
  } else {
    $e = oci_error($stidCreateCart);
    echo "Error creating cart: " . htmlentities($e['message']);
    exit;
  }
}

// Rest of the code for processing product addition to cart using $cartId...

// Close the database connection
oci_close($conn);

$productId = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;

if ($productId > 0 && $cartId !== null) {
    // Insert product into product_cart table
    $sql = "INSERT INTO PRODUCT_CART (FK1_PRODUCT_ID, FK2_CART_ID) VALUES (:product_id, :cart_id)";
    $stid = oci_parse($conn, $sql);

    oci_bind_by_name($stid, ':product_id', $productId);
    oci_bind_by_name($stid, ':cart_id', $cartId);

    if (oci_execute($stid)) {
        oci_commit($conn); // Commit the transaction to save the changes
        echo "Product added to cart successfully!";
    } else {
        $e = oci_error($stid);
        echo "Error adding product to cart: " . htmlentities($e['message']);
    }
} else {
    echo "Invalid product ID or cart ID.";
}

// Close the database connection
oci_close($conn);
?>
