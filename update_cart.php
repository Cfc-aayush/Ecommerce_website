<?php
session_start();

if (!isset($_SESSION['user_id']) || !isset($_POST['product_id']) || !isset($_POST['change'])) {
    echo "Invalid request.";
    exit;
}

$userId = $_SESSION['user_id'];
$cartId = $_SESSION['cartId'];
$productId = intval($_POST['product_id']);
$change = intval($_POST['change']);

$conn = oci_connect('AAYUSH', 'Goodluck#123', '//localhost/xe');
if (!$conn) {
    $e = oci_error();
    trigger_error(htmlentities($e['message'], ENT_QUOTES), E_USER_ERROR);
}

// Check current quantity
$sql = "
SELECT COUNT(*) AS QUANTITY
FROM PRODUCT_CART
WHERE FK2_CART_ID = :cart_id AND FK1_PRODUCT_ID = :product_id
";

$stid = oci_parse($conn, $sql);
oci_bind_by_name($stid, ':cart_id', $cartId);
oci_bind_by_name($stid, ':product_id', $productId);
oci_execute($stid);

$row = oci_fetch_assoc($stid);
$currentQuantity = $row['QUANTITY'];
oci_free_statement($stid);

$newQuantity = $currentQuantity + $change;

if ($newQuantity > 0) {
    if ($change > 0) {
        // Add product to cart
        for ($i = 0; $i < $change; $i++) {
            $sql = "INSERT INTO PRODUCT_CART (FK1_PRODUCT_ID, FK2_CART_ID) VALUES (:product_id, :cart_id)";
            $stid = oci_parse($conn, $sql);
            oci_bind_by_name($stid, ':product_id', $productId);
            oci_bind_by_name($stid, ':cart_id', $cartId);
            oci_execute($stid);
            oci_free_statement($stid);
        }
    } else {
        // Remove product from cart
        for ($i = 0; $i < abs($change); $i++) {
            $sql = "DELETE FROM PRODUCT_CART WHERE FK1_PRODUCT_ID = :product_id AND FK2_CART_ID = :cart_id AND ROWNUM = 1";
            $stid = oci_parse($conn, $sql);
            oci_bind_by_name($stid, ':product_id', $productId);
            oci_bind_by_name($stid, ':cart_id', $cartId);
            oci_execute($stid);
            oci_free_statement($stid);
        }
    }
} elseif ($newQuantity == 0) {
    // Remove all items for this product if the new quantity is 0
    $sql = "DELETE FROM PRODUCT_CART WHERE FK1_PRODUCT_ID = :product_id AND FK2_CART_ID = :cart_id";
    $stid = oci_parse($conn, $sql);
    oci_bind_by_name($stid, ':product_id', $productId);
    oci_bind_by_name($stid, ':cart_id', $cartId);
    oci_execute($stid);
    oci_free_statement($stid);
}

oci_commit($conn);
oci_close($conn);

echo "Cart updated successfully!";
?>
