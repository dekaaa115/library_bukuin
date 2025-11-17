<?php
session_start();
require_once "../php/config.php";

if (!isset($_POST['transaction_id'])) {
    header("Location: transaksi.php");
    exit;
}

$transaction_id = $_POST['transaction_id'];

$sql = "UPDATE transactions SET fine_paid_status = 'Paid' WHERE id = ?";

if ($stmt = mysqli_prepare($link, $sql)) {
    mysqli_stmt_bind_param($stmt, "i", $transaction_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}

mysqli_close($link);

header("Location: transaksi.php");
exit;
