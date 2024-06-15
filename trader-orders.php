<?php
session_start(); // Make sure to start the session to access session variables

if(!isset($_SESSION['role']) || $_SESSION['role'] != "trader") {
    echo '<script>alert("You are not authorized to access this page."); window.location.href = "login.php";</script>';
    exit(); // Stop further execution
}

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    // Redirect to login page if not logged in
    header("Location: login.php");
    exit;
}

// Get the user ID from the session
$user_id = $_SESSION['user_id'];

$conn = oci_connect('AAYUSH', 'Goodluck#123', '//localhost/xe');

if (!$conn) {
    $e = oci_error();
    trigger_error(htmlentities($e['message'], ENT_QUOTES), E_USER_ERROR);
}

// SQL to retrieve orders, products, and collection slots
$sql = "
    SELECT o.ORDER_ID, 
           o.ORDER_AMOUNT, 
           o.ORDER_DATE, 
           o.ORDER_TIME,
           p.PRODUCT_NAME,
           p.PRODUCT_PRICE
    FROM ordered_product op
    JOIN product p ON op.FK1_PRODUCT_ID = p.PRODUCT_ID
    JOIN orders o ON op.FK2_ORDER_ID = o.ORDER_ID
    JOIN collection_slot cs ON o.FK1_SLOT_ID = cs.SLOT_ID
    JOIN shop s ON p.FK2_SHOP_ID = s.SHOP_ID
    JOIN users u ON u.FK1_SHOP_ID = s.SHOP_ID
    WHERE u.USER_ID = :user_id
    AND u.FK1_SHOP_ID = p.FK2_SHOP_ID
    ORDER BY o.ORDER_ID
";

$stid = oci_parse($conn, $sql);
oci_bind_by_name($stid, ':user_id', $user_id);
if (!oci_execute($stid)) {
    $e = oci_error($stid);
    echo "Error executing query: " . htmlentities($e['message'], ENT_QUOTES);
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trader Orders</title>
    <link rel="stylesheet" href="traderorders.css">
</head>
<body>
<?php include('trader-navbar.php'); ?>

<div class="container">
    <h1>Orders</h1>
    <table>
        <thead>
            <tr>
                <th>Order ID</th>
                <th>Order Date</th>
                <th>Order Time</th>
                <th>Product Name</th>
                <th>Product Price</th>
                <th>Product Quantity</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $current_order_id = null; // To track current order ID
            $total_amount = 0; // To track total amount for each order
            while ($row = oci_fetch_assoc($stid)):
                // If it's a new order ID, reset total amount
                if ($current_order_id !== $row['ORDER_ID']) {
                    $current_order_id = $row['ORDER_ID'];
                    $total_amount = 0;
                    $product_quantity = 0;
                }
                // Add product price to total amount
                $total_amount += $row['PRODUCT_PRICE'];
                $product_quantity = $product_quantity +1;
            ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['ORDER_ID']); ?></td>
                    <td><?php echo htmlspecialchars($row['ORDER_DATE']); ?></td>
                    <td><?php echo htmlspecialchars($row['ORDER_TIME']); ?></td>
                    <td><?php echo htmlspecialchars($row['PRODUCT_NAME']); ?></td>
                    <td><?php echo htmlspecialchars($row['PRODUCT_PRICE']); ?></td>
                    <!-- Add a column for product quantity -->
                    <td><?php echo $product_quantity; ?></td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<script>
document.getElementById('mobile-menu').addEventListener('click', function() {
  const navLinks = document.querySelector('.nav-links');
  navLinks.classList.toggle('mobile-active');
});
</script>

<?php
oci_free_statement($stid);
oci_close($conn);
?>

</body>
</html>
