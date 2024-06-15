<?php
session_start();
if (isset($_POST['slot_id'])) {
    $_SESSION['slot_id'] = intval($_POST['slot_id']);
}
?>
