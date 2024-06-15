<?php 
session_start();
if(!isset($_SESSION['role']) || $_SESSION['role'] != "admin") {
    echo '<script>alert("You are not authorized to access this page."); window.location.href = "login.php";</script>';
    exit(); // Stop further execution
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Order Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.css" integrity="sha512-5A8nwdMOWrSz20fDsjczgUidUBR8liPYU+WymTZP1lmY9G6Oc7HlZv156XqnsgNUzTyMefFTcsFH/tnJE/+xBg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <style>
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 8px 12px;
            text-align: left;
            border: 1px solid #ddd;
        }
        th {
            background-color: #f2f2f2;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        tr:hover {
            background-color: #f1f1f1;
        }
    </style>
</head>
<body>
<?php 
include("admin-navbar.php");
?>

<div class="container">
    <div class="line mb-3"></div>
    <div class="text-box mb-3">
        <div class="text">Orders</div>
    </div>

    <?php
$conn = oci_connect('AAYUSH', 'Goodluck#123', '//localhost/xe');

    if (!$conn) {
        $e = oci_error();
        trigger_error(htmlentities($e['message'], ENT_QUOTES), E_USER_ERROR);
    }

    // SQL query to fetch orders and related products
    $sql = "SELECT o.ORDER_ID, o.ORDER_AMOUNT, o.ORDER_DATE, o.ORDER_TIME, u.FIRST_NAME, u.LAST_NAME, p.PRODUCT_NAME
    FROM ORDERS o
    JOIN USERS u ON o.FK2_USER_ID = u.USER_ID
    JOIN ORDERED_PRODUCT op ON o.ORDER_ID = op.FK2_ORDER_ID
    JOIN PRODUCT p ON op.FK1_PRODUCT_ID = p.PRODUCT_ID";
    $stid = oci_parse($conn, $sql);
    oci_execute($stid);

    echo "<table>
            <tr>
                <th>Order ID</th>
                <th>Order Amount</th>
                <th>Order Date</th>
                <th>Order Time</th>
                <th>User</th>
                <th>Products</th>
            </tr>";

    // Output data of each row
    while ($row = oci_fetch_array($stid, OCI_ASSOC+OCI_RETURN_NULLS)) {
        echo "<tr>
                <td>" . htmlspecialchars($row['ORDER_ID']) . "</td>
                <td>" . htmlspecialchars($row['ORDER_AMOUNT']) . "</td>
                <td>" . htmlspecialchars($row['ORDER_DATE']) . "</td>
                <td>" . htmlspecialchars($row['ORDER_TIME']) . "</td>
                <td>" . htmlspecialchars($row['FIRST_NAME'] . ' ' . $row['LAST_NAME']) . "</td>
                <td>" . htmlspecialchars($row['PRODUCT_NAME']) . "</td>
              </tr>";
    }
    echo "</table>";

    // Free statement resources and close the connection
    oci_free_statement($stid);
    oci_close($conn);
    ?>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

</body>
</html>
