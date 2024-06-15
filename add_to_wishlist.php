<?php
session_start();

$conn = oci_connect('AAYUSH', 'Goodluck#123', '//localhost/xe');
if (!$conn) {
  $e = oci_error();
  trigger_error(htmlentities($e['message'], ENT_QUOTES), E_USER_ERROR);
}

if (!isset($_SESSION['user_id'])) {
  echo "User not logged in";
  
  exit;
}

$user_id = $_SESSION['user_id'];
$product_id = $_POST['product_id'];
$action = $_POST['action'];

// Check if the product is already in the wishlist
$sql_check = "SELECT pw.PRODUCT_WISHLIST_ID 
              FROM PRODUCT_WISHLIST pw 
              JOIN WISHLIST w ON pw.FK2_WISHLIST_ID = w.WISHLIST_ID 
              WHERE w.FK1_USER_ID = :user_id AND pw.FK1_PRODUCT_ID = :product_id";

$stid_check = oci_parse($conn, $sql_check);
oci_bind_by_name($stid_check, ':user_id', $user_id);
oci_bind_by_name($stid_check, ':product_id', $product_id);
oci_execute($stid_check);

if ($row = oci_fetch_assoc($stid_check)) {
  if ($action === 'remove') {
    // Remove the product from the wishlist
    $sql_remove = "DELETE FROM PRODUCT_WISHLIST WHERE PRODUCT_WISHLIST_ID = :product_wishlist_id";
    $stid_remove = oci_parse($conn, $sql_remove);
    oci_bind_by_name($stid_remove, ':product_wishlist_id', $row['PRODUCT_WISHLIST_ID']);
    oci_execute($stid_remove);
    echo "Product removed from wishlist";
  } else {
    echo "Product already in wishlist";
  }
} else {
  if ($action === 'add') {
    // Check for existing wishlist for the user
    $sql_get_wishlist = "SELECT WISHLIST_ID FROM WISHLIST WHERE FK1_USER_ID = :user_id";
    $stid_get_wishlist = oci_parse($conn, $sql_get_wishlist);
    oci_bind_by_name($stid_get_wishlist, ':user_id', $user_id);
    oci_execute($stid_get_wishlist);

    $wishlist_id = null;
    if ($row = oci_fetch_assoc($stid_get_wishlist)) {
      $wishlist_id = $row['WISHLIST_ID'];
    }

    // Create a new wishlist if not exists
    if (!$wishlist_id) {
      // Start by creating the wishlist
      $sql_create_wishlist = "INSERT INTO WISHLIST (FK1_USER_ID) VALUES (:user_id)";
      $stid_create_wishlist = oci_parse($conn, $sql_create_wishlist);
      oci_bind_by_name($stid_create_wishlist, ':user_id', $user_id);
  
      if (oci_execute($stid_create_wishlist)) {
          oci_commit($conn); // Commit to ensure wishlist is created
  
          // Get the newly created wishlist ID using a sequence
          // First, call NEXTVAL to advance the sequence and generate a new value
          $sql_get_nextval = "SELECT WISHLIST_SEQ.NEXTVAL AS WISHLIST_ID FROM DUAL"; // Replace WISHLIST_SEQ with your actual sequence name
          $stid_get_nextval = oci_parse($conn, $sql_get_nextval);
          oci_execute($stid_get_nextval);
  
          if ($row = oci_fetch_assoc($stid_get_nextval)) {
              $wishlist_id = $row['WISHLIST_ID'];
          } else {
              echo "Error retrieving wishlist ID.";
              exit;
          }
      } else {
          $e = oci_error($stid_create_wishlist);
          echo "Error creating wishlist: " . htmlentities($e['message']);
          exit;
      }
  }
  
    // Add product to the wishlist using the retrieved wishlist ID
    $sql_add_wishlist = "INSERT INTO PRODUCT_WISHLIST (FK1_PRODUCT_ID, FK2_WISHLIST_ID) VALUES (:product_id, :wishlist_id)";
    $stid_add_wishlist = oci_parse($conn, $sql_add_wishlist);
    oci_bind_by_name($stid_add_wishlist, ':product_id', $product_id);
    oci_bind_by_name($stid_add_wishlist, ':wishlist_id', $wishlist_id);
    oci_execute($stid_add_wishlist);

    echo "Product added to wishlist";
  } else {
    echo "Product not in wishlist";
  }
}

// Close database connection
oci_close($conn);
?>
