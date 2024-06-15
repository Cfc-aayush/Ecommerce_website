<?php 
session_start();
if(!isset($_SESSION['role']) || $_SESSION['role'] != "admin") {
    echo '<script>alert("You are not authorized to access this page."); window.location.href = "login.php";</script>';
    exit(); // Stop further execution
}

$conn = oci_connect('AAYUSH', 'Goodluck#123', '//localhost/xe');

if (!$conn) {
    $e = oci_error();
    trigger_error(htmlentities($e['message'], ENT_QUOTES), E_USER_ERROR);
}

// Fetch shops with verification status 0
$shop_sql = "
    SELECT 
        SHOP_ID, SHOP_NAME, DESCRIPTION, SHOP_LOCATION, SHOP_LOGO
    FROM 
        shop
    WHERE 
        VERIFICATION = 0";

$shop_stid = oci_parse($conn, $shop_sql);
oci_execute($shop_stid);

// Fetch the shop details
$shops = [];
while ($row = oci_fetch_array($shop_stid, OCI_ASSOC+OCI_RETURN_NULLS)) {
    $shops[] = $row;
}

oci_free_statement($shop_stid);

// Fetch products with status 0
$product_sql = "
    SELECT 
        p.PRODUCT_ID, p.PRODUCT_NAME, p.DESCRIPTION AS PRODUCT_DESCRIPTION, p.STOCK_LEFT, p.ALLERGY, p.PRODUCT_PRICE, 
        pi.IMAGE, 
        u.FIRST_NAME || ' ' || u.LAST_NAME AS TRADER_NAME, u.PHN_NO, u.EMAIL AS TRADER_EMAIL,
        s.SHOP_NAME, s.SHOP_ID
    FROM 
        product p
    JOIN 
        product_image pi ON p.PRODUCT_ID = pi.FK1_PRODUCT_ID
    JOIN 
        users u ON p.FK2_SHOP_ID = u.FK1_SHOP_ID
    JOIN 
        shop s ON s.SHOP_ID = p.FK2_SHOP_ID
    WHERE 
        p.STATUS = 0";

$product_stid = oci_parse($conn, $product_sql);
oci_execute($product_stid);

// Fetch the product details
$products = [];
while ($row = oci_fetch_array($product_stid, OCI_ASSOC+OCI_RETURN_NULLS)) {
    $products[] = $row;
}

oci_free_statement($product_stid);

// Fetch traders with status 0
$trader_sql = "
    SELECT 
        USER_ID, FIRST_NAME, LAST_NAME, PHN_NO, EMAIL, STATUS
    FROM 
        users
    WHERE 
        STATUS = 0 AND ROLE = 'trader'";

$trader_stid = oci_parse($conn, $trader_sql);
oci_execute($trader_stid);

// Fetch the trader details
$traders = [];
while ($row = oci_fetch_array($trader_stid, OCI_ASSOC+OCI_RETURN_NULLS)) {
    $traders[] = $row;
}

oci_free_statement($trader_stid);
oci_close($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <title>Admin Dashboard</title>
</head>
<body>

<?php include("admin-navbar.php"); ?>
<div class="line"></div>

<div class="text-box">
    <div class="text"><h2>Shops Request</h2></div>
</div>

<div class="container mt-5">
    <h3>Pending Shops</h3>
    <?php foreach ($shops as $shop): ?>
        <div class="card mb-3">
            <div class="card-header">
                <strong><?= htmlspecialchars($shop['SHOP_NAME']) ?></strong>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <img src="<?= htmlspecialchars($shop['SHOP_LOGO']) ?>" class="img-fluid" alt="Shop Logo">
                    </div>
                    <div class="col-md-8">
                        <p><strong>Description:</strong> <?= htmlspecialchars($shop['DESCRIPTION']) ?></p>
                        <p><strong>Location:</strong> <?= htmlspecialchars($shop['SHOP_LOCATION']) ?></p>
                    </div>
                </div>
                <div class="mt-3">
                    <button class="btn btn-success accept-shop" data-shop-id="<?= htmlspecialchars($shop['SHOP_ID']) ?>">Accept</button>
                    <button class="btn btn-danger reject-shop" data-shop-id="<?= htmlspecialchars($shop['SHOP_ID']) ?>">Reject</button>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<div class="text-box">
    <div class="text"><h2>Traders Request</h2></div>
</div>

<div class="container mt-5">
    <h3>Pending Products</h3>
    <?php foreach ($products as $product): ?>
        <div class="card mb-3">
            <div class="card-header">
                <strong><?= htmlspecialchars($product['PRODUCT_NAME']) ?></strong> - <?= htmlspecialchars($product['SHOP_NAME']) ?>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <img src="<?= htmlspecialchars($product['IMAGE']) ?>" class="img-fluid" alt="Product Image">
                    </div>
                    <div class="col-md-8">
                        <p><strong>Description:</strong> <?= htmlspecialchars($product['PRODUCT_DESCRIPTION']) ?></p>
                        <p><strong>Price:</strong> $<?= htmlspecialchars($product['PRODUCT_PRICE']) ?></p>
                        <p><strong>Stock Left:</strong> <?= htmlspecialchars($product['STOCK_LEFT']) ?></p>
                        <p><strong>Allergy Information:</strong> <?= htmlspecialchars($product['ALLERGY']) ?></p>
                        <p><strong>Trader:</strong> <?= htmlspecialchars($product['TRADER_NAME']) ?> (<?= htmlspecialchars($product['TRADER_EMAIL']) ?>, <?= htmlspecialchars($product['PHN_NO']) ?>)</p>
                    </div>
                </div>
                <div class="mt-3">
                    <button class="btn btn-success accept-product" data-product-id="<?= htmlspecialchars($product['PRODUCT_ID']) ?>">Accept</button>
                    <button class="btn btn-danger reject-product" data-product-id="<?= htmlspecialchars($product['PRODUCT_ID']) ?>">Reject</button>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<div class="container mt-5">
    <h3>Pending Traders</h3>
    <?php foreach ($traders as $trader): ?>
        <div class="card mb-3">
            <div class="card-header">
                <strong>Trader Name: <?= htmlspecialchars($trader['FIRST_NAME'] . ' ' . $trader['LAST_NAME']) ?></strong>
            </div>
            <div class="card-body">
                <p><strong>Email:</strong> <?= htmlspecialchars($trader['EMAIL']) ?></p>
                <p><strong>Phone Number:</strong> <?= htmlspecialchars($trader['PHN_NO']) ?></p>
                <div class="mt-3">
                    <button class="btn btn-success accept-trader" data-user-id="<?= htmlspecialchars($trader['USER_ID']) ?>">Accept</button>
                    <button class="btn btn-danger reject-trader" data-user-id="<?= htmlspecialchars($trader['USER_ID']) ?>">Reject</button>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<script>
    //using the update_verification page o verify.
document.querySelectorAll('.accept-shop').forEach(button => {
    button.addEventListener('click', function() {
        const shopId = this.getAttribute('data-shop-id');
        // AJAX call to update shop verification status to 1
        fetch('update_verification.php?shop_id=' + shopId + '&status=1')
            .then(response => {
                if (response.ok) {
                    alert('Shop ' + shopId + ' accepted successfully.');
                    location.reload();
                } else {
                    throw new Error('Failed to accept shop ' + shopId);
                }
            })
            .catch(error => {
                console.error(error.message);
            });
    });
});

document.querySelectorAll('.reject-shop').forEach(button => {
    button.addEventListener('click', function() {
        const shopId = this.getAttribute('data-shop-id');
        // AJAX call to update shop verification status to 2 (rejected)
        fetch('update_verification.php?shop_id=' + shopId + '&status=2')
            .then(response => {
                if (response.ok) {
                    alert('Shop ' + shopId + ' rejected successfully.');
                    location.reload();
                } else {
                    throw new Error('Failed to reject shop ' + shopId);
                }
            })
            .catch(error => {
                console.error(error.message);
            });
    });
});

document.querySelectorAll('.accept-product').forEach(button => {
    button.addEventListener('click', function() {
        const productId = this.getAttribute('data-product-id');
        // AJAX call to update product status to 1
        fetch('update_status.php?product_id=' + productId + '&status=1')
            .then(response => {
                if (response.ok) {
                    alert('Product ' + productId + ' accepted successfully.');
                    location.reload();
                } else {
                    throw new Error('Failed to accept product ' + productId);
                }
            })
            .catch(error => {
                console.error(error.message);
            });
    });
});

document.querySelectorAll('.reject-product').forEach(button => {
    button.addEventListener('click', function() {
        const productId = this.getAttribute('data-product-id');
        // AJAX call to update product status to 2 (rejected)
        fetch('update_status.php?product_id=' + productId + '&status=2')
            .then(response => {
                if (response.ok) {
                    alert('Product ' + productId + ' rejected successfully.');
                    location.reload();
                } else {
                    throw new Error('Failed to reject product ' + productId);
                }
            })
            .catch(error => {
                console.error(error.message);
            });
    });
});

document.querySelectorAll('.accept-trader').forEach(button => {
    button.addEventListener('click', function() {
        const userId = this.getAttribute('data-user-id');
        // AJAX call to update trader status to 1
        fetch('update_status.php?user_id=' + userId + '&status=1')
            .then(response => {
                if (response.ok) {
                    alert('Trader ' + userId + ' accepted successfully.');
                    location.reload();
                } else {
                    throw new Error('Failed to accept trader ' + userId);
                }
            })
            .catch(error => {
                console.error(error.message);
            });
    });
});

document.querySelectorAll('.reject-trader').forEach(button => {
    button.addEventListener('click', function() {
        const userId = this.getAttribute('data-user-id');
        // AJAX call to update trader status to 2 (rejected)
        fetch('update_status.php?user_id=' + userId + '&status=2')
            .then(response => {
                if (response.ok) {
                    alert('Trader ' + userId + ' rejected successfully.');
                    location.reload();
                } else {
                    throw new Error('Failed to reject trader ' + userId);
                }
            })
            .catch(error => {
                console.error(error.message);
            });
    });
});
</script>

</body>
</html>
