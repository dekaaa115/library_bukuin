<?php
// Initialize the session
session_start();

// Include database configuration
require_once "config.php";

// Check if the user is logged in
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    // Arahkan ke login jika belum login
    header("location: ../login.php");
    exit;
}

// Check if the form was submitted from order.php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $user_id = $_SESSION["id"];
    
    // Ambil data yang dikirimkan (Menggunakan book_id, bukan book_title)
    $book_id = isset($_POST["book_id"]) ? intval($_POST["book_id"]) : null;
    $due_date = isset($_POST["due_date"]) ? $_POST["due_date"] : null; // Tanggal kembali dari order.php

    // Set tanggal pinjam saat ini
    $borrow_date = date("Y-m-d H:i:s");
    
    // Validasi data
    if (is_null($book_id) || empty($due_date)) {
        die("Error: Invalid book ID or due date received.");
    }

    // 1. Find the book and check its stock (Menggunakan ID)
    $stock_available = 0;
    $sql_check_stock = "SELECT stock_available FROM books WHERE id = ?";

    if ($stmt = mysqli_prepare($link, $sql_check_stock)) {
        mysqli_stmt_bind_param($stmt, "i", $book_id);
        if (mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            if (mysqli_num_rows($result) == 1) {
                $book_data = mysqli_fetch_assoc($result);
                $stock_available = $book_data['stock_available'];
            }
        }
        mysqli_stmt_close($stmt);
    }
    
    // 2. Check if book exists and is in stock
    if ($stock_available <= 0) {
        // Redirect ke halaman daftar buku dengan pesan error
        header("location: ../user/daftar-buku.php?error=stok_habis");
        exit;
    }

    // Use a transaction to ensure data integrity
    mysqli_begin_transaction($link);

    try {
        // 3. Insert the new transaction (Status: Borrowed/Dipinjam)
        $status = 'Borrowed'; 
        $sql_insert_transaction = "INSERT INTO transactions (user_id, book_id, borrow_date, due_date, status) VALUES (?, ?, ?, ?, ?)";
        
        if ($stmt_insert = mysqli_prepare($link, $sql_insert_transaction)) {
            // (i: user_id, i: book_id, s: borrow_date, s: due_date, s: status)
            mysqli_stmt_bind_param($stmt_insert, "iisss", $user_id, $book_id, $borrow_date, $due_date, $status); 
            mysqli_stmt_execute($stmt_insert);
            mysqli_stmt_close($stmt_insert);
        } else {
            throw new Exception("Error preparing transaction insert statement.");
        }

        // 4. Update the book's stock
        $sql_update_stock = "UPDATE books SET stock_available = stock_available - 1 WHERE id = ?";
        if ($stmt_update = mysqli_prepare($link, $sql_update_stock)) {
            mysqli_stmt_bind_param($stmt_update, "i", $book_id);
            mysqli_stmt_execute($stmt_update);
            mysqli_stmt_close($stmt_update);
        } else {
            throw new Exception("Error preparing stock update statement.");
        }

        // If all queries were successful, commit the transaction
        mysqli_commit($link);

        // Redirect ke halaman transaksi user
        header("location: ../user/transaksi.php?status=success"); 
        exit;

    } catch (Exception $e) {
        // If any query failed, roll back the changes
        mysqli_rollback($link);
        // Arahkan kembali dengan pesan error
        header("location: ../user/daftar-buku.php?error=transaksi_gagal");
        exit;
    }

} else {
    // Jika halaman diakses langsung tanpa POST data
    header("location: ../user/daftar-buku.php");
    exit;
}
?>