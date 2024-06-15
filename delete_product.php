<?php
$conn = oci_connect('AAYUSH', 'Goodluck#123', '//localhost/xe');

if (!$conn) {
    $e = oci_error();
    trigger_error(htmlentities($e['message'], ENT_QUOTES), E_USER_ERROR);
}

if (isset($_GET['product_id'])) {
    $product_id = $_GET['product_id'];

    $sql = "UPDATE Product SET STATUS = 2 WHERE PRODUCT_ID = :product_id";
    $stid = oci_parse($conn, $sql);
    oci_bind_by_name($stid, ':product_id', $product_id);

    if (oci_execute($stid)) {
        echo "<script>
        alert('Product is deleted ');
        window.location.href = 'Trader_home.php';
      </script>";
    } else {
        $e = oci_error($stid);
        echo "<script>
        alert('Error updating product status: " . htmlentities($e['message'], ENT_QUOTES)." ');
        window.location.href = 'Trader_home.php';
      </script>";
    }

    oci_free_statement($stid);
} else {
    echo "<script>
    alert('Product ID not provided. ');
    window.location.href = 'Trader_home.php';
  </script>";
    exit();
}

oci_close($conn);
?>
