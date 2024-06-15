<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

// Oracle Database connection
$conn = oci_connect('AAYUSH', 'Goodluck#123', '//localhost/xe');
if (!$conn) {
    $e = oci_error();
    error_log("Database Connection Error: " . htmlentities($e['message']));
    echo "Database Connection Error: " . htmlentities($e['message']);
    exit;
}

// Check session variables
if (!isset($_SESSION['totalamount']) || !isset($_SESSION['user_id']) || !isset($_SESSION['slot_id'])) {
    error_log("Session variables not set");
    echo "Session variables not set";
    exit;
}

$orderAmount = $_SESSION['totalamount'];
$userId = $_SESSION['user_id'];
$slotId = $_SESSION['slot_id'];

try {
    // Begin the transaction
    oci_execute(oci_parse($conn, "ALTER SESSION SET commit_write=nowait"));

    // Step 1: Insert the Order
    $insertOrderQuery = "INSERT INTO orders (ORDER_AMOUNT, ORDER_DATE, ORDER_TIME, FK1_SLOT_ID, FK2_USER_ID)
                         VALUES (:order_amount, SYSDATE, SYSTIMESTAMP, :slot_id, :user_id)
                         RETURNING ORDER_ID INTO :order_id";
    $stid = oci_parse($conn, $insertOrderQuery);
    oci_bind_by_name($stid, ':order_amount', $orderAmount);
    oci_bind_by_name($stid, ':slot_id', $slotId);
    oci_bind_by_name($stid, ':user_id', $userId);
    oci_bind_by_name($stid, ':order_id', $orderId, -1, SQLT_INT);
    $result = oci_execute($stid, OCI_NO_AUTO_COMMIT);
    if (!$result) {
        $e = oci_error($stid);
        throw new Exception("Database Insertion Error (Order): " . htmlentities($e['message']));
    }
    $_SESSION['order_id'] = $orderId;
    oci_free_statement($stid);

    // Step 2: Retrieve the cart ID for the user
    $cartQuery = "SELECT CART_ID FROM cart WHERE FK2_USER_ID = :user_id";
    $stid = oci_parse($conn, $cartQuery);
    oci_bind_by_name($stid, ':user_id', $userId);
    $result = oci_execute($stid);
    if (!$result) {
        $e = oci_error($stid);
        throw new Exception("Error retrieving cart ID: " . htmlentities($e['message']));
    }

    $cartId = null;
    if ($row = oci_fetch_assoc($stid)) {
        $cartId = $row['CART_ID'];
    } else {
        throw new Exception("Cart not found for user ID: " . $userId);
    }
    oci_free_statement($stid);

    // Step 3: Fetch all products in the cart and count occurrences
    $cartProductsQuery = "SELECT FK1_PRODUCT_ID, COUNT(*) AS QUANTITY FROM product_cart WHERE FK2_CART_ID = :cart_id GROUP BY FK1_PRODUCT_ID";
    $stid = oci_parse($conn, $cartProductsQuery);
    oci_bind_by_name($stid, ':cart_id', $cartId);
    $result = oci_execute($stid);
    if (!$result) {
        $e = oci_error($stid);
        throw new Exception("Error retrieving cart products: " . htmlentities($e['message']));
    }

    // Prepare to collect product details for the email
    $productDetails = "";
    $totalAmount = 0;
    // Step 4: Insert each product in the cart into the ordered_product table and update stock
while ($row = oci_fetch_assoc($stid)) {
    $productId = $row['FK1_PRODUCT_ID'];
    $quantity = $row['QUANTITY'];

    // Insert product into ordered_product table for each instance in the cart
    for ($i = 0; $i < $quantity; $i++) {
        $insertProductQuery = "INSERT INTO ordered_product (FK1_PRODUCT_ID, FK2_ORDER_ID) VALUES (:product_id, :order_id)";
        $stidInsert = oci_parse($conn, $insertProductQuery);
        oci_bind_by_name($stidInsert, ':product_id', $productId);
        oci_bind_by_name($stidInsert, ':order_id', $orderId);

        $result = oci_execute($stidInsert, OCI_NO_AUTO_COMMIT);
        if (!$result) {
            $e = oci_error($stidInsert);
            throw new Exception("Database Insertion Error (Order Product): " . htmlentities($e['message']));
        }
        oci_free_statement($stidInsert);
    }

    // Update the STOCK_LEFT for the product
    $updateStockQuery = "UPDATE product SET STOCK_LEFT = STOCK_LEFT - :quantity WHERE PRODUCT_ID = :product_id";
    $stidUpdate = oci_parse($conn, $updateStockQuery);
    oci_bind_by_name($stidUpdate, ':quantity', $quantity);
    oci_bind_by_name($stidUpdate, ':product_id', $productId);

    $result = oci_execute($stidUpdate, OCI_NO_AUTO_COMMIT);
    if (!$result) {
        $e = oci_error($stidUpdate);
        throw new Exception("Error updating stock: " . htmlentities($e['message']));
    }
    oci_free_statement($stidUpdate);
    // Fetch product details for email
    $productQuery = "SELECT PRODUCT_NAME, PRODUCT_PRICE FROM product WHERE PRODUCT_ID = :product_id";
    $stidProduct = oci_parse($conn, $productQuery);
    oci_bind_by_name($stidProduct, ':product_id', $productId);
    oci_execute($stidProduct);
    if ($productRow = oci_fetch_assoc($stidProduct)) {
        $productName = $productRow['PRODUCT_NAME'];
        $productPrice = $productRow['PRODUCT_PRICE'];
        $productDetails .= "Product: $productName, Quantity: $quantity, Price: $productPrice\n";
        $totalAmount += $productPrice * $quantity;
    }
    oci_free_statement($stidProduct);
}
oci_free_statement($stid);



    // Step 5: Payment Handling (Integration with payment gateway)
    $paymentSuccess = true; // Placeholder. Set to 'true' after successful payment

    if ($paymentSuccess) {
        // Step 6: Insert Payment if payment is successful
        $insertPaymentQuery = "INSERT INTO payment (TOTAL_PAYMENT, PAYMENT_DATE, PAYMENT_TIME, FK1_ORDER_ID, FK2_USER_ID)
                               VALUES (:total_payment, SYSDATE, SYSTIMESTAMP, :order_id, :user_id)";
        $stid = oci_parse($conn, $insertPaymentQuery);
        oci_bind_by_name($stid, ':total_payment', $orderAmount);
        oci_bind_by_name($stid, ':order_id', $orderId);
        oci_bind_by_name($stid, ':user_id', $userId);

        $result = oci_execute($stid, OCI_NO_AUTO_COMMIT);
        if (!$result) {
            $e = oci_error($stid);
            throw new Exception("Database Insertion Error (Payment): " . htmlentities($e['message']));
        }
        oci_free_statement($stid);
    } else {
        throw new Exception("Payment Failed.");
    }

    // Step 7: Clear the Cart (after successful payment and commit)
    $deleteCartQuery = "DELETE FROM product_cart WHERE FK2_CART_ID = :cart_id";
    $stid = oci_parse($conn, $deleteCartQuery);
    oci_bind_by_name($stid, ':cart_id', $cartId);
    $result = oci_execute($stid, OCI_NO_AUTO_COMMIT);
    if (!$result) {
        $e = oci_error($stid);
        throw new Exception("Error clearing cart after payment: " . htmlentities($e['message']));
    }
    oci_free_statement($stid);

    // Commit the transaction
    oci_commit($conn);

    // Step 8: Send Email with product details
    $userEmailQuery = "SELECT EMAIL FROM users WHERE USER_ID = :user_id";
    $stid = oci_parse($conn, $userEmailQuery);
    oci_bind_by_name($stid, ':user_id', $userId);
    oci_execute($stid);
    $userEmail = "";
    if ($row = oci_fetch_assoc($stid)) {
        $userEmail = $row['EMAIL'];
    }
    oci_free_statement($stid);

    if ($userEmail) {
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'clekbuy@gmail.com';
            $mail->Password = 'rgvcnwlrwuhjpysk';
            $mail->SMTPSecure = 'ssl';
            $mail->Port = 465;

            $mail->setFrom('clekbuy@gmail.com', 'Cleckbuy');
            $mail->addAddress($userEmail);

            $mail->isHTML(true);

            //invoice
            $mail->Subject = 'Order Confirmation';
            $mail->Body = "<h2>Your Order Details</h2><p>Total Amount: $totalAmount</p><pre>$productDetails</pre>";

            $mail->send();
        } catch (Exception $e) {
            error_log("Message could not be sent. Mailer Error: {$mail->ErrorInfo}");
            echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
        }
    }

    // Redirect to a success page
    header('Location: order_success.php');
    exit;
} catch (Exception $e) {
    // Rollback the transaction in case of error
    oci_rollback($conn);
    error_log($e->getMessage());
    echo "Error: " . $e->getMessage();
    exit;
} finally {
    oci_close($conn);
}
?>
