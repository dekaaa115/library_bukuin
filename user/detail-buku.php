<?php
// Initialize the session and database connection
session_start();
require_once "../php/config.php"; // Pastikan path ke file config.php benar

// 1. Check for logged-in status
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../login.php");
    exit;
}

// 2. Ambil ID Buku dari URL
if (!isset($_GET['id']) || empty($_GET['id'])) {
    // Redirect kembali ke daftar buku jika ID tidak ditemukan
    header("location: daftar-buku.php");
    exit;
}

$book_id = $_GET['id'];
$book = null; // Variable untuk menyimpan data buku lengkap

// 3. Query Database untuk Detail Buku
// Anda mungkin perlu menambahkan kolom 'synopsis' dan 'stock' ke query ini
$sql_detail = "SELECT id, title, author, genre, synopsis, cover_image_url, rating, stock_available FROM books WHERE id = ?"; 

if ($stmt = mysqli_prepare($link, $sql_detail)) {
    mysqli_stmt_bind_param($stmt, "i", $book_id); // 'i' untuk integer (ID)

    if (mysqli_stmt_execute($stmt)) {
        $result = mysqli_stmt_get_result($stmt);

        if (mysqli_num_rows($result) == 1) {
            $book = mysqli_fetch_assoc($result);
        } else {
            // Buku tidak ditemukan
            // Tampilkan pesan atau redirect ke 404
            die("Buku dengan ID tersebut tidak ditemukan.");
        }
    }
    mysqli_stmt_close($stmt);
} else {
    die("ERROR: Could not prepare query.");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($book['title']); ?> - Detail Buku</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="../assets/css/style.css">
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body class="bg-[#212121] text-white">

    <div class="flex h-screen bg-[#212121] text-white">
        <nav class="w-64 bg-[#333333] p-6 flex flex-col justify-between">
            <a href="daftar-buku.php" class="text-white hover:text-purple-400 mb-4 flex items-center">
                 <i data-lucide="arrow-left" class="mr-2"></i> Kembali ke Daftar
            </a>
            </nav>
        
        <main class="flex-1 flex flex-col">
            <header class="bg-[#A78BFA] text-black p-4 flex justify-between items-center shadow-md">
                <div class="flex items-center">
                    <i data-lucide="library" class="mr-3"></i>
                    <h1 class="text-xl font-semibold">Buku in - Detail Buku</h1>
                </div>
                </header>

            <div class="flex-1 p-8 overflow-y-auto">
                <div class="bg-[#333333] p-8 rounded-xl shadow-lg">
                    
                    <div class="flex flex-col md:flex-row gap-8">
                        
                        <div class="md:w-1/3 flex flex-col items-center">
                            <img src="../<?php echo htmlspecialchars($book['cover_image_url']); ?>" 
                                 alt="<?php echo htmlspecialchars($book['title']); ?>" 
                                 class="rounded-lg shadow-2xl mb-4 w-full max-w-xs object-cover" 
                                 style="aspect-ratio: 150 / 220;">

                            <h1 class="text-3xl font-bold text-white text-center mt-2"><?php echo htmlspecialchars($book['title']); ?></h1>
                            <p class="text-lg text-gray-400">Oleh: <?php echo htmlspecialchars($book['author']); ?></p>
                            <p class="text-sm bg-purple-500 px-3 py-1 rounded-full mt-2"><?php echo htmlspecialchars($book['genre']); ?></p>

                            <div class="flex justify-center text-yellow-400 mt-3">
                                <?php 
                                $rating = floatval($book['rating']);
                                for ($i = 1; $i <= 5; $i++): 
                                    echo ($i <= $rating) ? '<i data-lucide="star" class="w-5 h-5 fill-current"></i>' : '<i data-lucide="star" class="w-5 h-5"></i>';
                                endfor; 
                                ?>
                            </div>
                        </div>

                        <div class="md:w-2/3">
                            <h2 class="text-2xl font-semibold mb-4 border-b border-gray-600 pb-2">Sinopsis</h2>
                            
                            <p class="text-gray-300 leading-relaxed mb-6">
                                <?php echo nl2br(htmlspecialchars($book['synopsis'])); ?>
                            </p>

                            <?php 
                            $stock = intval($book['stock_available']); // <-- Kunci array yang benar
                            $stock_class = ($stock > 0) ? 'bg-green-600' : 'bg-red-600';
                            $stock_text = ($stock > 0) ? "Tersedia ({$stock} stok)" : "Tidak Tersedia";
                            ?>
                            <div class="text-lg font-bold mb-4">
                                Status: <span class="<?php echo $stock_class; ?> px-3 py-1 rounded-full text-white text-sm"><?php echo $stock_text; ?></span>
                            </div>

                            <?php if ($stock > 0): ?>
                                <a href="order.php?book_id=<?php echo $book['id']; ?>" 
                                   class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-6 rounded-lg transition-colors flex items-center justify-center w-full md:w-auto">
                                    <i data-lucide="shopping-cart" class="mr-2 w-5 h-5"></i> Pesan Sekarang
                                </a>
                            <?php else: ?>
                                <button disabled class="bg-gray-500 text-white font-bold py-3 px-6 rounded-lg cursor-not-allowed flex items-center justify-center w-full md:w-auto opacity-70">
                                    <i data-lucide="shopping-cart" class="mr-2 w-5 h-5"></i> Stok Habis
                                </button>
                            <?php endif; ?>
                            
                            </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
    <script>
        lucide.createIcons();
    </script>
</body>
</html>