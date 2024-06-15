<?php
session_start();

// Oracle Database Connection
$conn = oci_connect('AAYUSH', 'Goodluck#123', '//localhost/xe');
if (!$conn) {
    $e = oci_error();
    // Log the error for debugging (optional)
    error_log("Database connection failed: " . $e['message']);
    echo 'error'; // Indicate an error to the AJAX call
    exit;
}

// Get cart ID from the session
$cartId = $_SESSION['cartId'];

// Fetch cart item count
$countSql = "SELECT COUNT(*) AS item_count FROM product_cart WHERE FK2_CART_ID = :cartId";
$countStid = oci_parse($conn, $countSql);
oci_bind_by_name($countStid, ':cartId', $cartId);
oci_execute($countStid);

if (($countRow = oci_fetch_assoc($countStid)) === false) {
    // Handle the case where the query fails (e.g., cart not found)
    error_log("Failed to fetch item count: " . oci_error($countStid)['message']);
    echo 'error';
    exit;
}

$itemCount = $countRow['ITEM_COUNT'];

// Check item count and send response
if ($itemCount > 20) {
    echo 'limit_exceeded'; // Indicate limit exceeded to AJAX
} else {
    echo 'ok';  // Indicate everything is fine
}

// Free statement and close connection
oci_free_statement($countStid);
oci_close($conn);
