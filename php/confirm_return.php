<?php
// confirm_return.php
session_start();
require_once "config.php";

// Pengecekan Admin
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== 'admin') {
    header("location: ../login.php");
    exit;
}

if (!isset($_GET["transaction_id"]) || empty(trim($_GET["transaction_id"]))) {
    header("location: ../admin/transaksi.php?error=invalid_id");
    exit;
}

$transaction_id = intval($_GET["transaction_id"]);

mysqli_begin_transaction($link);

try {
    // 1. Ambil detail transaksi (hanya status dan book_id)
    $sql_get_trans = "SELECT book_id, status FROM transactions WHERE id = ?";
    $transaction = null;

    if ($stmt_get = mysqli_prepare($link, $sql_get_trans)) {
        mysqli_stmt_bind_param($stmt_get, "i", $transaction_id);
        mysqli_stmt_execute($stmt_get);
        $result = mysqli_stmt_get_result($stmt_get);
        $transaction = mysqli_fetch_assoc($result);
    } else {
        throw new Exception("Error fetching transaction details.");
    }
    mysqli_stmt_close($stmt_get);

    if (!$transaction) {
        throw new Exception("Transaksi tidak ditemukan.");
    }
    
    // VALIDASI: Pastikan statusnya benar-benar 'Pending Return' sebelum mengkonfirmasi
    if ($transaction['status'] != 'Pending Return') {
        throw new Exception("Buku tidak dalam status Menunggu Konfirmasi.");
    }

    $book_id = $transaction['book_id'];

    // 2. Update status transaksi menjadi 'Returned'
    $new_status = 'Returned';
    // Kita tidak perlu update return_date karena sudah dicatat saat pengajuan user
    $sql_update_trans = "UPDATE transactions SET status = ? WHERE id = ?"; 
    if ($stmt_update_trans = mysqli_prepare($link, $sql_update_trans)) {
        mysqli_stmt_bind_param($stmt_update_trans, "si", $new_status, $transaction_id);
        mysqli_stmt_execute($stmt_update_trans);
    } else {
        throw new Exception("Error updating transaction status.");
    }
    mysqli_stmt_close($stmt_update_trans);

    // 3. Tambahkan Stok Buku kembali (Stok baru ditambahkan di sini)
    $sql_update_stock = "UPDATE books SET stock_available = stock_available + 1 WHERE id = ?";
    if ($stmt_update_stock = mysqli_prepare($link, $sql_update_stock)) {
        mysqli_stmt_bind_param($stmt_update_stock, "i", $book_id);
        mysqli_stmt_execute($stmt_update_stock);
    } else {
        throw new Exception("Error updating book stock.");
    }
    mysqli_stmt_close($stmt_update_stock);

    mysqli_commit($link);

    // Redirect kembali ke halaman transaksi Admin
    header("location: ../admin/transaksi.php?status=return_confirmed");
    exit();

} catch (Exception $e) {
    mysqli_rollback($link);
    error_log("Admin confirmation failed: " . $e->getMessage());
    header("location: ../admin/transaksi.php?error=confirmation_failed");
    exit();
}
?>