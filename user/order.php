<?php
// order.php
session_start();
require_once "../php/config.php";

// Cek login
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../login.php");
    exit;
}

// 1. Ambil ID Buku dari URL
if (!isset($_GET['book_id']) || empty($_GET['book_id'])) {
    header("location: daftar-buku.php");
    exit;
}
$book_id = intval($_GET['book_id']);

// Dapatkan user_id dari sesi
$user_id = $_SESSION['id']; 

// Hitung tanggal kembali (misal, 7 hari dari sekarang)
$days_of_borrow = 7; 
$due_date = date('Y-m-d', strtotime("+$days_of_borrow days")); 

// 2. Ambil data buku yang akan diorder
$sql_book = "SELECT id, title, author, cover_image_url, stock_available FROM books WHERE id = ?";
$book = null;

if ($stmt = mysqli_prepare($link, $sql_book)) {
    mysqli_stmt_bind_param($stmt, "i", $book_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    if ($row = mysqli_fetch_assoc($result)) {
        $book = $row;
    }
    mysqli_stmt_close($stmt);
}

// Cek stok (pemeriksaan kedua) dan jika buku tidak ditemukan
if (!$book || $book['stock_available'] <= 0) {
    header("location: daftar-buku.php?error=stok_habis");
    exit;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Konfirmasi Pesanan - <?php echo htmlspecialchars($book['title']); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="bg-[#212121] text-white">

    <div class="flex h-screen bg-[#212121] text-white">
        <!-- Sidebar Navigation (Disalin dari daftar-buku.php) -->
        <nav class="w-64 bg-[#333333] p-6 flex flex-col justify-between">
            <div>
                <div class="flex flex-col items-center mb-10">
                    <img src="../<?php echo htmlspecialchars($_SESSION['profile_image_url']); ?>" onerror="this.onerror=null; this.src='https://placehold.co/100x100/A78BFA/FFFFFF?text=<?php echo substr(htmlspecialchars($_SESSION['full_name']), 0, 1); ?>'" alt="User Profile" class="rounded-full w-24 h-24 mb-4 border-2 border-purple-400 object-cover">
                    <h3 class="font-bold text-lg"><?php echo htmlspecialchars($_SESSION['full_name']); ?></h3>
                    <p class="text-sm bg-blue-500 px-3 py-1 rounded-full mt-2">Pengguna</p>
                </div>
                <ul>
                    <li class="nav-item rounded-lg mb-2">
                        <a href="daftar-buku.php" class="flex items-center p-3 rounded-lg">
                            <i data-lucide="book-open" class="mr-3"></i>Daftar Buku
                        </a>
                    </li>
                    <li class="nav-item rounded-lg mb-2">
                        <a href="profil-user.php" class="flex items-center p-3 rounded-lg">
                        <i data-lucide="file-pen-line" class="mr-3"></i>Edit Profil</a>
                    </li>
                    <li class="nav-item rounded-lg mb-2">
                        <a href="transaksi.php" class="flex items-center p-3 rounded-lg active-nav">
                           <i data-lucide="qr-code" class="mr-3"></i>Transaksi
                        </a>
                    </li>
                    <li class="nav-item rounded-lg mb-2">
                        <a href="payment.php" class="flex items-center p-3 rounded-lg">
                            <i data-lucide="wallet" class="mr-3"></i>Pembayaran
                        </a>
                    </li>
                    <li class="nav-item rounded-lg mb-2">
                        <a href="history.php" class="flex items-center p-3 rounded-lg">
                            <i data-lucide="history" class="mr-3"></i>History
                        </a>
                    </li>
                </ul>
            </div>
            <div>
                 <a href="../php/logout.php" class="flex items-center p-3 rounded-lg nav-item">
                    <i data-lucide="log-out" class="mr-3"></i>Logout
                </a>
            </div>
        </nav>
        
        <!-- Main Content -->
        <main class="flex-1 flex flex-col">
            <!-- Header (Disalin dari daftar-buku.php) -->
            <header class="bg-[#A78BFA] text-black p-4 flex justify-between items-center shadow-md">
                <div class="flex items-center">
                    <i data-lucide="library" class="mr-3"></i>
                    <h1 class="text-xl font-semibold">Buku in - Konfirmasi Pesanan</h1>
                </div>
                <div class="flex items-center">
                    <span class="mr-4"><?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
                    <a href="profil-user.php">
                        <img src="../<?php echo htmlspecialchars($_SESSION['profile_image_url']); ?>" onerror="this.onerror=null; this.src='https://placehold.co/40x40/FFFFFF/333333?text=<?php echo substr(htmlspecialchars($_SESSION['full_name']), 0, 1); ?>'" alt="User Avatar" class="rounded-full w-10 h-10 cursor-pointer object-cover">
                    </a>
                </div>
            </header>

            <div class="flex-1 p-8 overflow-y-auto">
                <div class="bg-[#333333] p-8 rounded-xl shadow-lg max-w-xl mx-auto">
                    <h2 class="text-3xl font-bold mb-8 border-b border-gray-600 pb-3">Konfirmasi Pesanan Pinjam</h2>
                    
                    <!-- Detail Buku yang Akan Dipinjam -->
                    <div class="flex items-start gap-6 mb-6">
                        <img src="../<?php echo htmlspecialchars($book['cover_image_url']); ?>" 
                             alt="<?php echo htmlspecialchars($book['title']); ?>" 
                             class="w-20 h-30 rounded-lg object-cover shadow-md"
                             style="aspect-ratio: 150 / 220;">
                        <div>
                            <h3 class="text-2xl font-semibold text-white"><?php echo htmlspecialchars($book['title']); ?></h3>
                            <p class="text-md text-gray-400">Oleh: <?php echo htmlspecialchars($book['author']); ?></p>
                        </div>
                    </div>
                    
                    <p class="text-lg mb-4 text-gray-300">Anda akan meminjam buku ini dengan detail:</p>
                    
                    <!-- Detail Peminjaman -->
                    <div class="bg-[#4F4F4F] p-5 rounded-lg mb-8 shadow-inner">
                        <div class="flex justify-between py-1 border-b border-gray-600">
                            <span class="font-medium text-gray-400">Durasi Pinjam:</span>
                            <span class="font-semibold text-white"><?php echo $days_of_borrow; ?> Hari</span>
                        </div>
                        <div class="flex justify-between py-1 border-b border-gray-600">
                            <span class="font-medium text-gray-400">Tanggal Kembali Estimasi:</span>
                            <span class="font-semibold text-purple-300"><?php echo $due_date; ?></span>
                        </div>
                        <div class="flex justify-between py-1">
                            <span class="font-medium text-gray-400">Stok Tersedia Saat Ini:</span>
                            <span class="font-semibold text-green-400"><?php echo $book['stock_available']; ?></span>
                        </div>
                    </div>

                    <!-- Peringatan dan Form -->
                    <form action="../php/borrow_process.php" method="POST">
                        <!-- Data Tersembunyi yang Dibutuhkan untuk Proses -->
                        <input type="hidden" name="book_id" value="<?php echo $book['id']; ?>">
                        <input type="hidden" name="due_date" value="<?php echo $due_date; ?>">
                        
                        <p class="text-sm text-yellow-500 mb-4 bg-yellow-900/30 p-3 rounded-lg border border-yellow-700">
                            <i data-lucide="alert-triangle" class="inline w-4 h-4 mr-1"></i> 
                            Dengan mengklik Konfirmasi, Anda setuju mengembalikan buku sebelum tanggal **<?php echo $due_date; ?>**.
                        </p>
                        
                        <button type="submit" class="bg-purple-600 hover:bg-purple-700 text-white font-bold py-3 px-6 rounded-lg w-full transition-colors shadow-md">
                            Konfirmasi & Pinjam Sekarang
                        </button>
                    </form>
                    
                </div>
            </div>
        </main>
    </div>
    <script>
        lucide.createIcons();
    </script>
</body>
</html>