<?php
session_start();

if (isset($_POST['total'])) {
    $_SESSION['totalamount'] = floatval($_POST['total']);
    echo 'Session total updated';
} else {
    echo 'No total amount provided';
}
?>
