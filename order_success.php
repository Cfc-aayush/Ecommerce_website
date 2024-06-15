<?php
session_start();

// Ensure the order ID is set in the session
if (!isset($_SESSION['order_id']) || !isset($_SESSION['totalamount'])) {
    header('Location: order_error.php');
    exit;
}

$orderID = $_SESSION['order_id'];
$totalAmount = $_SESSION['totalamount'];
$date = date("Y/m/d");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Success</title>
    <link rel="stylesheet" href="style-receipt.css">
</head>
<body>
    <div class="order-success-container">
        <h1>Thank You!</h1>
        <p>Your order has been successfully placed.</p>
        <div class="order-details">
            <p>Order ID: <?php echo htmlspecialchars($orderID); ?></p>
            <p>Total Amount: $<?php echo htmlspecialchars($totalAmount); ?></p>
            <p>Date: <?php echo htmlspecialchars($date); ?></p>
        </div>
        <div class="next-steps">
            <p>You will receive an email confirmation shortly.</p>
            <p><a href="home.php">Continue Shopping</a></p>
        </div>
    </div>
</body>
</html>
