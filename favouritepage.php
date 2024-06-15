<?php
session_start();

$conn = oci_connect('AAYUSH', 'Goodluck#123', '//localhost/xe');

if (!$conn) {
    $e = oci_error();
    trigger_error(htmlentities($e['message'], ENT_QUOTES), E_USER_ERROR);
}

if (!isset($_SESSION['user_id'])) {
    echo '<script>alert("User not login"); window.location.replace("login.php");</script>';
    exit;
}

$user_id = $_SESSION['user_id'];

// Handle Add to Cart action
if (isset($_POST['add_to_cart'])) {
    $product_id = intval($_POST['product_id']);

    // Check if a cart exists for the user
    $sqlCart = "SELECT CART_ID FROM CART WHERE FK2_USER_ID = :user_id";
    $stidCart = oci_parse($conn, $sqlCart);
    oci_bind_by_name($stidCart, ':user_id', $user_id);
    oci_execute($stidCart);

    $cart_id = null;
    if ($row = oci_fetch_assoc($stidCart)) {
        $cart_id = $row['CART_ID'];
    } else {
        // Create a new cart if not exists
        $sqlCreateCart = "INSERT INTO CART (FK2_USER_ID) VALUES (:user_id) RETURNING CART_ID INTO :cart_id";
        $stidCreateCart = oci_parse($conn, $sqlCreateCart);
        oci_bind_by_name($stidCreateCart, ':user_id', $user_id);
        oci_bind_by_name($stidCreateCart, ':cart_id', $cart_id, -1, SQLT_INT);

        if (oci_execute($stidCreateCart)) {
            oci_commit($conn); // Commit the transaction to ensure the cart is created
        } else {
            $e = oci_error($stidCreateCart);
            echo "Error creating cart: " . htmlentities($e['message']);
            exit;
        }
    }

    // Insert product into product_cart table
    if ($product_id > 0 && $cart_id !== null) {
        $sql = "INSERT INTO PRODUCT_CART (FK1_PRODUCT_ID, FK2_CART_ID) VALUES (:product_id, :cart_id)";
        $stid = oci_parse($conn, $sql);
        oci_bind_by_name($stid, ':product_id', $product_id);
        oci_bind_by_name($stid, ':cart_id', $cart_id);

        if (oci_execute($stid)) {
            oci_commit($conn); // Commit the transaction to save the changes
            echo "<script>
            alert('Product added to cart successfully! ');
            window.location.href = 'favouritepage.php';
          </script>";
        } else {
            $e = oci_error($stid);
            echo "Error adding product to cart: " . htmlentities($e['message']);
        }
    } else {
        echo "Invalid product ID or cart ID.";
    }
}

// Handle Remove from Wishlist action
if (isset($_POST['remove_from_wishlist'])) {
    $product_id = intval($_POST['product_id']);

    // Delete product from product_wishlist table
    $sql = "DELETE FROM PRODUCT_WISHLIST WHERE FK1_PRODUCT_ID = :product_id AND FK2_WISHLIST_ID IN (SELECT WISHLIST_ID FROM WISHLIST WHERE FK1_USER_ID = :user_id)";
    $stid = oci_parse($conn, $sql);
    oci_bind_by_name($stid, ':product_id', $product_id);
    oci_bind_by_name($stid, ':user_id', $user_id);

    if (oci_execute($stid)) {
        oci_commit($conn); // Commit the transaction to save the changes
        echo "Product removed from wishlist successfully!";
    } else {
        $e = oci_error($stid);
        echo "Error removing product from wishlist: " . htmlentities($e['message']);
    }
}

$query = "SELECT p.PRODUCT_ID, p.PRODUCT_NAME, p.PRODUCT_PRICE, p.DESCRIPTION, pi.IMAGE
          FROM PRODUCT p
          JOIN PRODUCT_WISHLIST pw ON p.PRODUCT_ID = pw.FK1_PRODUCT_ID
          JOIN WISHLIST w ON pw.FK2_WISHLIST_ID = w.WISHLIST_ID
          LEFT JOIN PRODUCT_IMAGE pi ON p.PRODUCT_ID = pi.FK1_PRODUCT_ID
          WHERE w.FK1_USER_ID = :user_id";

$stid = oci_parse($conn, $query);
oci_bind_by_name($stid, ":user_id", $user_id);
oci_execute($stid);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Favourites</title>
    <link rel="stylesheet" href="favouritepage.css">
</head>
<body>
   
    <?php include("navbar.php"); ?>
    
    <div class="favourite-body">
        <h1>Favourites</h1>
        
        <?php while ($row = oci_fetch_array($stid, OCI_ASSOC+OCI_RETURN_NULLS)) { ?>
            <div class="product-card">
                <div class="product-image">
                    <img src="<?= htmlspecialchars($row['IMAGE'], ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($row['PRODUCT_NAME'], ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="product-info">
                    <h2>$<?= htmlspecialchars($row['PRODUCT_PRICE'], ENT_QUOTES, 'UTF-8') ?></h2>
                    <h3><?= htmlspecialchars($row['PRODUCT_NAME'], ENT_QUOTES, 'UTF-8') ?></h3>
                    <p><?= htmlspecialchars($row['DESCRIPTION'], ENT_QUOTES, 'UTF-8') ?></p>
                    <div class="product-actions">
                        <form method="post" style="display:inline;">
                            <input type="hidden" name="product_id" value="<?= htmlspecialchars($row['PRODUCT_ID'], ENT_QUOTES, 'UTF-8') ?>">
                            <button class="btn add-to-cart" name="add_to_cart">Add to cart</button>
                        </form>
                        <form method="post" style="display:inline;">
                            <input type="hidden" name="product_id" value="<?= htmlspecialchars($row['PRODUCT_ID'], ENT_QUOTES, 'UTF-8') ?>">
                            <button class="btn remove" name="remove_from_wishlist">Remove</button>
                        </form>
                    </div>
                </div>
            </div>
            <br>
        <?php } ?>

    </div>
</body>
<?php include("footer.php")?>
</html>

<?php
oci_free_statement($stid);
oci_close($conn);
?>
