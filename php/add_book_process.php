<?php
// add_book_process.php
session_start();
require_once "config.php";

// Pengecekan Admin
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== 'admin') {
    header("location: ../login.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // 1. Ambil dan sanitasi semua data dari formulir
    $title = trim($_POST['title']);
    $author = trim($_POST['author']);
    $genre = trim($_POST['genre']);
    $synopsis = trim($_POST['synopsis']); 
    // Menggunakan string untuk rating (karena rating float/decimal)
    $rating = $_POST['rating']; 
    $stock_available = intval($_POST['stock_available']);
    $stock_needed = intval($_POST['stock_needed']);
    
    $cover_image_url = ''; 

    // --- Logika Upload Gambar ---
    if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] == 0) {
        $target_dir = "../uploads/"; 
        $file_extension = pathinfo($_FILES["cover_image"]["name"], PATHINFO_EXTENSION);
        $new_file_name = time() . "_" . uniqid() . "." . $file_extension;
        $target_file = $target_dir . $new_file_name;

        if (move_uploaded_file($_FILES["cover_image"]["tmp_name"], $target_file)) {
            $cover_image_url = "uploads/" . $new_file_name; 
        } else {
            $cover_image_url = 'path/to/default_image.jpg';
        }
    }
    // --- Akhir Logika Upload Gambar ---

    // 2. Query SQL INSERT (PASTIKAN 8 KOLOM DATA ADA)
    $sql = "INSERT INTO books (title, author, genre, synopsis, rating, stock_available, stock_needed, cover_image_url, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";

    if ($stmt = mysqli_prepare($link, $sql)) {
        
        // KOREKSI FINAL: Menggunakan "sssssiis" (8 karakter untuk 8 variabel yang di-bind)
        // s: title, s: author, s: genre, s: synopsis, s: rating, i: stock_available, i: stock_needed, s: cover_image_url
        mysqli_stmt_bind_param($stmt, "sssssiis", 
            $title, 
            $author, 
            $genre, 
            $synopsis,      // 4. String (s)
            $rating,        // 5. String (s) - Rating
            $stock_available, // 6. Integer (i) - Stok Tersedia
            $stock_needed,  // 7. Integer (i) - Stok Kebutuhan
            $cover_image_url // 8. String (s) - URL Gambar
        );
        
        if (mysqli_stmt_execute($stmt)) {
            // Berhasil
            header("location: ../admin/data-buku.php?status=add_success");
        } else {
            // Gagal
            die("Error: " . mysqli_error($link));
        }
        mysqli_stmt_close($stmt);

    } else {
        die("Error: Could not prepare query.");
    }
} else {
    // Jika diakses tanpa POST
    header("location: ../admin/add-book.php");
}
exit;
?>