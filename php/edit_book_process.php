<?php
// edit_book_process.php - versi bersih dan working
session_start();
require_once "config.php";

// Cek admin
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== 'admin') {
    die("Access Denied.");
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../admin/data-buku.php");
    exit;
}

// Ambil dan sanitasi input
$book_id = isset($_POST['id']) ? intval($_POST['id']) : 0;
$title = isset($_POST['title']) ? trim($_POST['title']) : '';
$author = isset($_POST['author']) ? trim($_POST['author']) : '';
$genre = isset($_POST['genre']) ? trim($_POST['genre']) : '';
$synopsis = isset($_POST['synopsis']) ? trim($_POST['synopsis']) : '';
$rating = isset($_POST['rating']) ? trim($_POST['rating']) : '';
$stock_available = isset($_POST['stock_available']) ? intval($_POST['stock_available']) : 0;
$stock_needed = isset($_POST['stock_needed']) ? intval($_POST['stock_needed']) : 0;
$existing_cover = isset($_POST['existing_cover_image_url']) ? trim($_POST['existing_cover_image_url']) : "";

// inisialisasi variabel cover; null berarti tidak ada upload baru
$cover_image_url = null;

if ($book_id <= 0) {
    die("Invalid book id.");
}

if (isset($_FILES["cover_image"]) && isset($_FILES["cover_image"]["error"]) && $_FILES["cover_image"]["error"] === UPLOAD_ERR_OK) {
    $target_dir = __DIR__ . "/../assets/images/"; // folder fisik
    if (!is_dir($target_dir)) {
        // coba buat folder jika belum ada (opsional)
        @mkdir($target_dir, 0755, true);
    }

    $ext = pathinfo($_FILES["cover_image"]["name"], PATHINFO_EXTENSION);
    $ext = strtolower($ext);
    // validasi ekstensi sederhana
    $allowed_ext = ['jpg','jpeg','png','gif','webp'];
    if (!in_array($ext, $allowed_ext, true)) {
        // ekstensi tidak valid -> anggap tidak ada upload baru
        $cover_image_url = null;
    } else {
        $safe_filename = time() . "_" . uniqid() . "." . $ext;
        $target_file = $target_dir . $safe_filename;
        if (move_uploaded_file($_FILES["cover_image"]["tmp_name"], $target_file)) {
            // path relatif yang disimpan ke DB (sesuaikan dengan struktur project)
            $cover_image_url = "assets/images/" . $safe_filename;
        } else {
            $cover_image_url = null; // gagal upload: lanjut tanpa mengubah gambar
        }
    }
}

// Query update
if ($cover_image_url !== null) {
    // Update termasuk cover_image_url
    $sql = "UPDATE books SET title = ?, author = ?, genre = ?, synopsis = ?, rating = ?, stock_available = ?, stock_needed = ?, cover_image_url = ? WHERE id = ?";
    if ($stmt = mysqli_prepare($link, $sql)) {
        // tipe parameter: title(s), author(s), genre(s), synopsis(s), rating(s), stock_available(i), stock_needed(i), cover_image_url(s), id(i)
        // => "sssss i i s i" -> contiguous => "sssssiisi"
        mysqli_stmt_bind_param($stmt, "sssssiisi",
            $title,
            $author,
            $genre,
            $synopsis,
            $rating,
            $stock_available,
            $stock_needed,
            $cover_image_url,
            $book_id
        );
        if (!mysqli_stmt_execute($stmt)) {
            die("MySQL Error (update with image): " . mysqli_error($link));
        }
        mysqli_stmt_close($stmt);
    } else {
        die("Prepare failed (update with image): " . mysqli_error($link));
    }
} else {
    // Update tanpa mengubah cover_image_url
    $sql = "UPDATE books SET title = ?, author = ?, genre = ?, synopsis = ?, rating = ?, stock_available = ?, stock_needed = ? WHERE id = ?";
    if ($stmt = mysqli_prepare($link, $sql)) {
        // tipe: 5x s, 3x i => "sssssiii"
        mysqli_stmt_bind_param($stmt, "sssssiii",
            $title,
            $author,
            $genre,
            $synopsis,
            $rating,
            $stock_available,
            $stock_needed,
            $book_id
        );
        if (!mysqli_stmt_execute($stmt)) {
            die("MySQL Error (update no image): " . mysqli_error($link));
        }
        mysqli_stmt_close($stmt);
    } else {
        die("Prepare failed (update no image): " . mysqli_error($link));
    }
}

mysqli_close($link);
// Redirect kembali ke halaman daftar buku
header("Location: ../admin/data-buku.php?status=edit_success");
exit;
?>