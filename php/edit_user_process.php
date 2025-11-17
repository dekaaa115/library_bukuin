<?php
// edit_user_process.php
session_start();
require_once "config.php";

// Pengecekan Admin
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== 'admin') {
    header("location: ../login.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // 1. Ambil data dari form admin/edit_user.php
    $user_id = intval($_POST['user_id']); // ID Anggota yang di-edit
    $full_name = trim($_POST['full_name']);
    $nickname = trim($_POST['nickname']);
    $kelas = trim($_POST['kelas']);
    $phone_number = trim($_POST['phone_number']);
    $address = trim($_POST['address']);
    $gender = trim($_POST['gender']);
    $is_verified = intval($_POST['is_verified']); // Ambil status verifikasi
    
    $error = '';
    
    // 2. Cek apakah nickname sudah digunakan oleh orang lain (kecuali diri sendiri)
    $sql_check_nick = "SELECT id FROM users WHERE nickname = ? AND id != ?";
    if ($stmt_check = mysqli_prepare($link, $sql_check_nick)) {
        mysqli_stmt_bind_param($stmt_check, "si", $nickname, $user_id);
        mysqli_stmt_execute($stmt_check);
        mysqli_stmt_store_result($stmt_check);
        if (mysqli_stmt_num_rows($stmt_check) > 0) {
            $error = "Username sudah digunakan oleh anggota lain.";
        }
        mysqli_stmt_close($stmt_check);
    }

    // 3. Query Update
    if (empty($error)) {
        $sql = "UPDATE users SET 
            full_name = ?, 
            nickname = ?, 
            kelas = ?, 
            phone_number = ?, 
            address = ?, 
            gender = ?,
            is_verified = ?
            WHERE id = ?";
        
        if ($stmt = mysqli_prepare($link, $sql)) {
            // Binding: s s s s s s i i (7 String, 2 Integer)
            mysqli_stmt_bind_param($stmt, "ssssssii", 
                $full_name, 
                $nickname, 
                $kelas, 
                $phone_number, 
                $address, 
                $gender, 
                $is_verified, // is_verified (Integer)
                $user_id
            );
            
            if (mysqli_stmt_execute($stmt)) {
                // Redirect kembali ke halaman daftar anggota dengan pesan sukses
                header("location: ../admin/data-anggota.php?status=edit_success");
            } else {
                $error = "Gagal memperbarui data: " . mysqli_error($link);
            }
            mysqli_stmt_close($stmt);

        } else {
            $error = "Error: Could not prepare query.";
        }
    }
    
    // 4. Jika ada error, redirect kembali ke form edit dengan pesan error
    if (!empty($error)) {
        header("location: ../admin/edit_user.php?id=" . $user_id . "&error=" . urlencode($error));
    }

} else {
    // Jika diakses tanpa POST
    header("location: ../admin/data-anggota.php");
}
exit;
?>