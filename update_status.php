<?php

// Check if the necessary parameters are provided in the query string
if ((isset($_GET['product_id']) && isset($_GET['status'])) || (isset($_GET['user_id']) && isset($_GET['status']))) {
    $status = $_GET['status'];

    // Connect to the database
    $conn = oci_connect('AAYUSH', 'Goodluck#123', '//localhost/xe');

    if (!$conn) {
        $e = oci_error();
        die(json_encode(['success' => false, 'error' => $e['message']]));
    }

    if (isset($_GET['product_id'])) {
        $product_id = $_GET['product_id'];

        // Update the status of the product in the database
        $sql = "UPDATE product SET STATUS = :status WHERE PRODUCT_ID = :product_id";
        $stid = oci_parse($conn, $sql);

        oci_bind_by_name($stid, ':status', $status);
        oci_bind_by_name($stid, ':product_id', $product_id);
    } elseif (isset($_GET['user_id'])) {
        $user_id = $_GET['user_id'];

        // Update the status of the trader in the database
        $sql = "UPDATE users SET STATUS = :status WHERE USER_ID = :user_id";
        $stid = oci_parse($conn, $sql);

        oci_bind_by_name($stid, ':status', $status);
        oci_bind_by_name($stid, ':user_id', $user_id);
    }

    $result = oci_execute($stid);

    if ($result) {
        // Status updated successfully
        echo json_encode(['success' => true]);
    } else {
        // Failed to update status
        $e = oci_error($stid);
        echo json_encode(['success' => false, 'error' => $e['message']]);
    }

    oci_free_statement($stid);
    oci_close($conn);
} else {
    // Required parameters not provided in the query string
    echo json_encode(['success' => false, 'error' => 'Required parameters not provided']);
}
