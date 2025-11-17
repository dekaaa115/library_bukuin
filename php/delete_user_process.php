<?php
// delete_user_process.php
session_start();
require_once "config.php";

session_start();
require_once "config.php";

// Cek akses admin
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== 'admin') {
    header("location: ../login.php");
    exit;
}

// Pastikan ada ID user
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("location: ../admin/data-anggota.php?error=invalid_id");
    exit;
}

$user_id = intval($_GET['id']);

// Cek apakah user benar-benar ada
$sql_check = "SELECT id FROM users WHERE id = ?";
if ($stmt = mysqli_prepare($link, $sql_check)) {
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);

    if (mysqli_stmt_num_rows($stmt) === 0) {
        // Jika user tidak ditemukan
        mysqli_stmt_close($stmt);
        header("location: ../admin/data-anggota.php?error=user_not_found");
        exit;
    }
    mysqli_stmt_close($stmt);
}

// Proses delete
$sql_delete = "DELETE FROM users WHERE id = ?";
if ($stmt = mysqli_prepare($link, $sql_delete)) {
    mysqli_stmt_bind_param($stmt, "i", $user_id);

    if (mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);
        header("location: ../admin/data-anggota.php?status=delete_success");
        exit;
    } else {
        mysqli_stmt_close($stmt);
        header("location: ../admin/data-anggota.php?error=delete_failed");
        exit;
    }
}

header("location: ../admin/data-anggota.php?error=query_error");
exit;

?>