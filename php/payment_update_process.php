<?php
// payment_update_process.php
session_start();
require_once "config.php";

// Cek login
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../login.php");
    exit;
}

// ----------------------------------------------------------------------
// LOGIC PENGAJUAN OLEH USER (action=submit)
// ----------------------------------------------------------------------
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['transaction_ids'])) {
    
    // Ambil data
    $transaction_ids_str = trim($_POST['transaction_ids']);
    $total_fine = floatval($_POST['total_fine']);
    $user_id = $_SESSION['id'];
    
    $file_upload_path = null;
    $error = '';
    
    // 1. Proses Upload Bukti Bayar
    if (isset($_FILES['proof']) && $_FILES['proof']['error'] == 0) {
        $target_dir = "../uploads/proofs/"; 
        if (!is_dir($target_dir)) { mkdir($target_dir, 0777, true); }

        $file_extension = pathinfo($_FILES["proof"]["name"], PATHINFO_EXTENSION);
        $new_file_name = "proof_" . $user_id . "_" . time() . "." . $file_extension;
        $target_file = $target_dir . $new_file_name;

        if (move_uploaded_file($_FILES["proof"]["tmp_name"], $target_file)) {
            $file_upload_path = "uploads/proofs/" . $new_file_name; 
        } else {
            $error = "Gagal mengunggah bukti pembayaran.";
        }
    } else {
        $error = "Bukti pembayaran wajib diunggah.";
    }

    if (!empty($error)) {
        header("location: ../user/ajukan_pembayaran.php?error=" . urlencode($error));
        exit;
    }
    
    // 2. Update Status Transaksi di Database
    mysqli_begin_transaction($link);

    try {
        // Status diubah menjadi 'Diajukan'
        $new_payment_status = 'Diajukan'; 
        
        // Update SEMUA transaksi yang ID-nya ada di $transaction_ids_str
        $sql_update = "UPDATE transactions SET payment_status = ?, proof_path = ? WHERE id IN ($transaction_ids_str) AND user_id = ?";
        
        if ($stmt_update = mysqli_prepare($link, $sql_update)) {
            // Binding: s s i (status, proof_path, user_id)
            mysqli_stmt_bind_param($stmt_update, "ssi", 
                $new_payment_status, 
                $file_upload_path,
                $user_id
            );
            
            if (!mysqli_stmt_execute($stmt_update)) {
                throw new Exception("Gagal memperbarui status pembayaran di DB.");
            }
            mysqli_stmt_close($stmt_update);
        } else {
            throw new Exception("Error preparing update statement.");
        }

        mysqli_commit($link);
        
        // Redirect ke halaman payment dengan pesan sukses
        header("location: ../user/payment.php?status=submitted");
        
    } catch (Exception $e) {
        mysqli_rollback($link);
        // Hapus file yang sudah terupload jika transaksi gagal
        if ($file_upload_path && file_exists("../" . $file_upload_path)) {
             unlink("../" . $file_upload_path);
        }
        header("location: ../user/payment.php?error=" . urlencode("Transaksi Gagal: " . $e->getMessage()));
    }
    exit;

} 
// ----------------------------------------------------------------------
// LOGIC KONFIRMASI OLEH ADMIN (action=confirm)
// ----------------------------------------------------------------------
elseif (isset($_GET['action']) && $_GET['action'] == 'confirm' && isset($_GET['transaction_id'])) {
    
    // Pengecekan Admin
    if ($_SESSION["role"] !== 'admin') {
        header("location: ../login.php");
        exit;
    }
    
    $transaction_id = intval($_GET['transaction_id']);
    $new_payment_status = 'Sudah Dibayar'; 

    mysqli_begin_transaction($link);

    try {
        // 1. Ambil path bukti bayar (proof_path) dari transaksi ini
        $sql_get_path = "SELECT proof_path FROM transactions WHERE id = ?";
        $proof_path = null;
        
        if ($stmt_path = mysqli_prepare($link, $sql_get_path)) {
            mysqli_stmt_bind_param($stmt_path, "i", $transaction_id);
            mysqli_stmt_execute($stmt_path);
            mysqli_stmt_bind_result($stmt_path, $proof_path);
            mysqli_stmt_fetch($stmt_path);
            mysqli_stmt_close($stmt_path);
        }
        
        // 2. Update status pembayaran menjadi 'Sudah Dibayar' dan hapus bukti bayar
        $sql_update = "UPDATE transactions SET payment_status = ?, proof_path = NULL WHERE id = ?";
        
        if ($stmt_update = mysqli_prepare($link, $sql_update)) {
            mysqli_stmt_bind_param($stmt_update, "si", $new_payment_status, $transaction_id);
            
            if (!mysqli_stmt_execute($stmt_update)) {
                throw new Exception("Gagal memperbarui status pembayaran menjadi Lunas.");
            }
            mysqli_stmt_close($stmt_update);
        } else {
            throw new Exception("Error preparing update statement.");
        }

        mysqli_commit($link);
        
        // 3. Hapus file bukti pembayaran dari server (opsional, tapi disarankan)
        if ($proof_path && file_exists("../" . $proof_path)) {
             unlink("../" . $proof_path);
        }
        
        // Redirect kembali ke halaman konfirmasi Admin
        header("location: ../admin/payment_confirmation.php?status=confirmed");
        
    } catch (Exception $e) {
        mysqli_rollback($link);
        header("location: ../admin/payment_confirmation.php?error=" . urlencode("Konfirmasi Gagal: " . $e->getMessage()));
    }
    exit;
}
// ----------------------------------------------------------------------
// C. Akses tidak valid
// ----------------------------------------------------------------------
else {
    header("location: ../user/daftar-buku.php");
    exit;
}
?>