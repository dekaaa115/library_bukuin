<?php
include '../config.php';

if (!isset($_GET['id'])) {
    echo "Invalid request!";
    exit;
}

$id = $_GET['id'];

// Ambil status pembayaran saat ini
$query = mysqli_query($conn, "SELECT status_pembayaran FROM pengembalian WHERE id = '$id'");
$data  = mysqli_fetch_assoc($query);

if (!$data) {
    echo "Data tidak ditemukan!";
    exit;
}

$status_sekarang = $data['status_pembayaran'];

// Tentukan status selanjutnya
if ($status_sekarang == "Belum Dibayar") {
    $status_baru = "Sudah Dibayar";
} elseif ($status_sekarang == "Sudah Dibayar") {
    $status_baru = "Lunas";
} else {
    $status_baru = "Lunas"; // fallback (jika sudah lunas, tetap lunas)
}

// Update ke database
$update = mysqli_query($conn, "
    UPDATE pengembalian 
    SET status_pembayaran = '$status_baru' 
    WHERE id = '$id'
");

if ($update) {
    header("Location: laporan.php?update=success");
    exit;
} else {
    echo "Gagal update status pembayaran!";
}
?>
