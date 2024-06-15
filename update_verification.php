<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    http_response_code(403);
    exit('Unauthorized');
}

$conn = oci_connect('AAYUSH', 'Goodluck#123', '//localhost/xe');
if (!$conn) {
    $e = oci_error();
    http_response_code(500);
    exit(json_encode(['error' => htmlentities($e['message'], ENT_QUOTES)]));
}

$shop_id = intval($_GET['shop_id']);
$status = intval($_GET['status']);

$update_sql = 'UPDATE shop SET VERIFICATION = :status WHERE SHOP_ID = :shop_id';
$stid = oci_parse($conn, $update_sql);
oci_bind_by_name($stid, ':status', $status);
oci_bind_by_name($stid, ':shop_id', $shop_id);

$result = oci_execute($stid);
oci_free_statement($stid);
oci_close($conn);

if ($result) {
    echo json_encode(['success' => true]);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to update shop verification status']);
}
?>
