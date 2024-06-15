<?php
session_start();

// Oracle Database connection
$conn = oci_connect('AAYUSH', 'Goodluck#123', '//localhost/xe');
if (!$conn) {
    $e = oci_error();
    trigger_error(htmlentities($e['message'], ENT_QUOTES), E_USER_ERROR);
}

$cartId = $_SESSION['cartId'];
$userId = $_SESSION['user_id'];

// Initialize the cart session variable if it does not exist
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Fetch cart items with aggregated quantities
$sql = "
SELECT pc.FK1_PRODUCT_ID, p.PRODUCT_NAME, p.DESCRIPTION, p.PRODUCT_PRICE, p.STOCK_LEFT, i.IMAGE, COUNT(*) AS QUANTITY
FROM product_cart pc
JOIN product p ON pc.FK1_PRODUCT_ID = p.PRODUCT_ID
JOIN Product_Image i ON p.PRODUCT_ID = i.FK1_PRODUCT_ID
WHERE pc.FK2_CART_ID = :cartId
GROUP BY pc.FK1_PRODUCT_ID, p.PRODUCT_NAME, p.DESCRIPTION, p.PRODUCT_PRICE, p.STOCK_LEFT, i.IMAGE
";

$stid = oci_parse($conn, $sql);
oci_bind_by_name($stid, ':cartId', $cartId);
oci_execute($stid);

$cartItems = [];
$subtotal = 0;
while ($row = oci_fetch_assoc($stid)) {
    $cartItems[] = $row;
    $subtotal += $row['PRODUCT_PRICE'] * $row['QUANTITY'];

    // Add product IDs to the session cart
    if (!in_array($row['FK1_PRODUCT_ID'], $_SESSION['cart'])) {
        $_SESSION['cart'][] = $row['FK1_PRODUCT_ID'];
    }
}

$shipping = 00.00;
$total = $subtotal + $shipping;
$_SESSION['totalamount'] = $total;

oci_free_statement($stid);
oci_close($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script>
function updateQuantity(productId, change) {
    var quantityElement = document.getElementById('quantity_' + productId);
    var currentQuantity = parseInt(quantityElement.textContent);
    var newQuantity = currentQuantity + change;

    // Calculate the current total quantity in the cart, including the change
    var totalQuantity = 0;
    var quantities = document.querySelectorAll('[id^="quantity_"]');
    quantities.forEach(function(q) {
        var id = q.id.split('_')[1];
        if (id == productId) {
            totalQuantity += newQuantity; // Use newQuantity for the current item
        } else {
            totalQuantity += parseInt(q.textContent);
        }
    });


    $.ajax({
        url: 'update_cart.php',
        type: 'POST',
        data: { product_id: productId, change: change },
        success: function(response) {
            quantityElement.textContent = newQuantity;
            updateSubtotal();
        },
        error: function() {
            alert('Failed to update cart');
        }
    });
}

function updateSubtotal() {
    var items = document.querySelectorAll('[id^="quantity_"]');
    var subtotal = 0;
    items.forEach(function(item) {
        var productId = item.id.split('_')[1];
        var quantity = parseInt(item.textContent);
        var price = parseFloat(document.getElementById('price_' + productId).textContent);
        subtotal += quantity * price;
    });
    document.getElementById('subtotal').textContent = subtotal.toFixed(2);
    var total = subtotal; // Including fixed shipping
    document.getElementById('total').textContent = total.toFixed(2);
    document.getElementById('checkout-total').textContent = total.toFixed(2);

    // Update the session variable via AJAX
    $.ajax({
        url: 'update_session_total.php',
        type: 'POST',
        data: { total: total },
        success: function(response) {
            console.log('Session total updated');
        },
        error: function() {
            alert('Failed to update session total');
        }
    });
}

function checkout() {
    // Check again before proceeding to checkout
    $.ajax({
        url: 'check_cart_limit.php', 
        type: 'POST',
        success: function(response) {
            if (response === 'ok') {
                document.getElementById('checkout_form').submit(); 
            } else {
                alert('You cannot have more than 20 items in your cart.');
            }
        },
        error: function() {
            alert('Failed to check cart limit');
        }
    });
}

    </script>
</head>
<body>
<section class="h-100 h-custom" style="background-color: #eee;">
  <div class="container py-5 h-100">
    <div class="row d-flex justify-content-center align-items-center h-100">
      <div class="col">
        <div class="card">
          <div class="card-body p-4">
            <div class="row">
              <div class="col-lg-7">
                <h5 class="mb-3"><a href="home.php" class="text-body"><i class="fas fa-long-arrow-alt-left me-2"></i>Continue shopping</a></h5>
                <hr>
                <div class="d-flex justify-content-between align-items-center mb-4">
                  <div>
                    <p class="mb-1">Shopping cart</p>
                    <p class="mb-0">You have <?php echo count($cartItems); ?> items in your cart</p>
                  </div>
                  <div>
                    <p class="mb-0"><span class="text-muted">Sort by:</span> <a href="#!" class="text-body">price <i class="fas fa-angle-down mt-1"></i></a></p>
                  </div>
                </div>

                <?php foreach ($cartItems as $item) : ?>
                <div class="card mb-3">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div class="d-flex flex-row align-items-center">
                                <div>
                                    <img src="<?php echo htmlspecialchars($item['IMAGE']); ?>" class="img-fluid rounded-3" alt="Shopping item" style="width: 65px;">
                                </div>
                                <div class="ms-3">
                                    <h5><?php echo htmlspecialchars($item['PRODUCT_NAME']); ?></h5>
                                    <p class="small mb-0"><?php echo htmlspecialchars($item['DESCRIPTION']); ?></p>
                                </div>
                            </div>
                            <div class="d-flex flex-row align-items-center">
                                <div style="width: 30px;">
                                    <button class="btn btn-sm btn-outline-secondary" onclick="updateQuantity(<?php echo $item['FK1_PRODUCT_ID']; ?>, -1)">-</button>
                                </div>
                                <div style="width: 50px;">
                                    <h5 class="fw-normal mb-0" id="quantity_<?php echo $item['FK1_PRODUCT_ID']; ?>"><?php echo $item['QUANTITY']; ?></h5>
                                </div>
                                <div style="width: 30px;">
                                    <button class="btn btn-sm btn-outline-secondary" onclick="updateQuantity(<?php echo $item['FK1_PRODUCT_ID']; ?>, 1)">+</button>
                                </div>
                                <div style="width: 80px;">
                                    <h5 class="mb-0" id="price_<?php echo $item['FK1_PRODUCT_ID']; ?>"><?php echo number_format($item['PRODUCT_PRICE'], 2); ?></h5>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
              </div>
              <div class="col-lg-5">
                <div class="card bg-secondary text-white rounded-3">
                  <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                      <h5 class="mb-0">Cart totals</h5>
                    </div>
                    <hr class="my-4">
                    <div class="d-flex justify-content-between">
                      <p class="mb-2">Subtotal</p>
                      <p class="mb-2" id="subtotal">$<?php echo number_format($subtotal, 2); ?></p>
                    </div>
                    
                    <div class="d-flex justify-content-between mb-4">
                      <p class="mb-2">Total(Incl. taxes)</p>
                      <p class="mb-2" id="total">$<?php echo number_format($total, 2); ?></p>
                    </div>
                    <button type="button" class="btn btn-info btn-block btn-lg" onclick="checkout()">
                      <div class="d-flex justify-content-between">
                        <span id="checkout-total">$<?php echo number_format($total, 2); ?> </span>
                        <a>Checkout <i class="fas fa-long-arrow-alt-right ms-2"></i></a>
                      </div>
                    </button>
                    <form id="checkout_form" action="checkout.php" method="POST" style="display: none;">
                        <input type="hidden" name="total_amount" value="<?php echo $total; ?>">
                    </form>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>
</body>
</html>
