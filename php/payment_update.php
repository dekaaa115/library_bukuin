<?php
session_start();
require_once $_SERVER['DOCUMENT_ROOT']."/Buku-in/php/config.php";

// Hanya admin boleh akses
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"]!==true || $_SESSION["role"]!=='admin'){
    header("location:/Buku-in/login.php"); exit;
}

if(!isset($_GET['id']) || !is_numeric($_GET['id'])){
    die("Invalid request");
}

$id = (int)$_GET['id'];

// Ambil denda
$sql = "SELECT denda, payment_status FROM transactions WHERE id=?";
$stmt = mysqli_prepare($link, $sql);
mysqli_stmt_bind_param($stmt,"i",$id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$data = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if(!$data) die("Transaction not found");
if((int)$data['denda']===0){
    header("Location:/Buku-in/admin/laporan.php?msg=nofine"); exit;
}

// Update payment
$update = "UPDATE transactions SET payment_status='Paid', payment_date=NOW() WHERE id=?";
$stmt2 = mysqli_prepare($link,$update);
mysqli_stmt_bind_param($stmt2,"i",$id);
mysqli_stmt_execute($stmt2);
mysqli_stmt_close($stmt2);

mysqli_close($link);
header("Location:/Buku-in/admin/laporan.php?success=pay");
exit;
