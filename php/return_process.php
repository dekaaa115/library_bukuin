<?php
// Initialize the session
session_start();
require_once "config.php";

// Check if the user is logged in
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../login.php");
    exit;
}

if (!isset($_GET["transaction_id"]) || empty(trim($_GET["transaction_id"]))) {
    header("location: ../user/transaksi.php?error=invalid_id");
    exit;
}

$transaction_id = intval($_GET["transaction_id"]);
$current_user_id = $_SESSION['id'];
$fine_per_day = 2000; 

mysqli_begin_transaction($link);

try {
    // 1. Get transaction details
    $sql_get_trans = "SELECT book_id, due_date, status, user_id FROM transactions WHERE id = ?";
    $transaction = null;
    $due_date = null; // Inisialisasi $due_date di sini

    if ($stmt_get = mysqli_prepare($link, $sql_get_trans)) {
        mysqli_stmt_bind_param($stmt_get, "i", $transaction_id);
        mysqli_stmt_execute($stmt_get);
        $result = mysqli_stmt_get_result($stmt_get);
        $transaction = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt_get);
    } else {
        throw new Exception("Error fetching transaction details.");
    }

    // Pengecekan Transaksi dan Otorisasi (Baris setelah fetch)
    if (!$transaction || $transaction['user_id'] != $current_user_id) {
        throw new Exception("Transaksi tidak valid atau bukan milik Anda.");
    }
    if ($transaction['status'] != 'Borrowed') {
        throw new Exception("Buku tidak dalam status pinjam aktif.");
    }
    
    // PENTING: Pengecekan sebelum membuat objek DateTime (Mengatasi error baris 36)
    if (!empty($transaction['due_date'])) {
         $due_date = new DateTime($transaction['due_date']);
    } else {
         // Jika due_date NULL di DB (seharusnya tidak terjadi), anggap hari ini.
         $due_date = new DateTime(); 
    }


    $book_id = $transaction['book_id'];

    // 2. Calculate fine (Logic perhitungan denda)
    $return_date = new DateTime(); 
    $fine_amount = 0;
    $new_status = 'Pending Return'; 
    $payment_status = 'Belum Dibayar';

    // Pengecekan denda menggunakan objek $due_date yang kini terjamin bukan NULL
    if ($return_date > $due_date) {
        $interval = $return_date->diff($due_date);
        $days_overdue = $interval->days;
        if ($days_overdue > 0) {
             $fine_amount = $days_overdue * $fine_per_day;
        }
    }
    
    // ... (Logic UPDATE dan Redirect lainnya) ...

    $fine_amount_str = strval($fine_amount); 
    
    // 3. Update the transaction record
    $sql_update_trans = "UPDATE transactions SET return_date = ?, status = ?, fine_amount = ?, payment_status = ? WHERE id = ?";
    if ($stmt_update_trans = mysqli_prepare($link, $sql_update_trans)) {
        $return_date_str = $return_date->format('Y-m-d');
        
        mysqli_stmt_bind_param($stmt_update_trans, "ssssi", 
            $return_date_str, 
            $new_status, 
            $fine_amount_str, 
            $payment_status, 
            $transaction_id
        );
        
        if (!mysqli_stmt_execute($stmt_update_trans)) {
            $error = mysqli_stmt_error($stmt_update_trans);
            mysqli_stmt_close($stmt_update_trans);
            throw new Exception("SQL UPDATE Failed: " . $error);
        }
        mysqli_stmt_close($stmt_update_trans);

    } else {
        throw new Exception("Error preparing update statement.");
    }

    mysqli_commit($link);

    $redirect_url = "../user/transaksi.php?status=pending_return";
    if ($fine_amount > 0) {
         $redirect_url .= "&fine=" . $fine_amount;
    }
    header("location: " . $redirect_url);
    exit();

} catch (Exception $e) {
    mysqli_rollback($link);
    die("Error Pemrosesan Pengembalian: " . $e->getMessage()); 
}
?>