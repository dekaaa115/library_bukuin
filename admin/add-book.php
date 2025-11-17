<?php
// admin/add-book.php
session_start();
require_once "../php/config.php";

// Pengecekan Admin
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== 'admin') {
    header("location: ../login.php");
    exit;
}

// Tentukan proses file yang akan dituju (asumsi Anda memiliki ../php/add_book_process.php)
$process_file = '../php/add_book_process.php';
$page_title = 'Tambah Buku Baru';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body class="bg-gray-100 text-gray-800">

    <div class="flex h-screen bg-[#212121] text-white">
        <!-- Sidebar Admin -->
        <nav class="w-64 bg-[#333333] p-6 flex flex-col justify-between">
            <!-- ... Sidebar content ... -->
            <div>
                <div class="flex flex-col items-center mb-10">
                    <h3 class="font-bold text-lg">Admin Dashboard</h3>
                    <p class="text-sm bg-red-500 px-3 py-1 rounded-full mt-2">Administrator</p>
                </div>
                <ul>
                    <li class="nav-item rounded-lg mb-2 active-nav"><a href="data-buku.php" class="flex items-center p-3 rounded-lg"><i data-lucide="book-open" class="mr-3"></i>Data Buku</a></li>
                    <li class="nav-item rounded-lg mb-2"><a href="data-anggota.php" class="flex items-center p-3 rounded-lg"><i data-lucide="users" class="mr-3"></i>Data Anggota</a></li>
                    <li class="nav-item rounded-lg mb-2"><a href="transaksi.php" class="flex items-center p-3 rounded-lg"><i data-lucide="clipboard-list" class="mr-3"></i>Transaksi</a></li>
                    <li class="nav-item rounded-lg mb-2"><a href="laporan.php" class="flex items-center p-3 rounded-lg"><i data-lucide="bar-chart-3" class="mr-3"></i>Laporan</a></li>
                    <li class="nav-item rounded-lg mb-2">
                        <a href="profil-admin.php" class="flex items-center p-3 rounded-lg">
                        <i data-lucide="user-cog" class="mr-3"></i>Edit Profil</a>
                    </li>
                </ul>
            </div>
            <div>
                 <a href="../php/logout.php" class="flex items-center p-3 rounded-lg nav-item"><i data-lucide="log-out" class="mr-3"></i>Logout</a>
            </div>
        </nav>
        
        <!-- Main Content -->
        <main class="flex-1 flex flex-col">
            <!-- Header -->
            <header class="bg-[#A78BFA] text-black p-4 flex justify-between items-center shadow-md">
                <div class="flex items-center">
                    <i data-lucide="library" class="mr-3"></i>
                    <h1 class="text-xl font-semibold"><?php echo $page_title; ?></h1>
                </div>
            </header>

            <div class="flex-1 p-8 overflow-y-auto">
                <div class="bg-[#333333] p-8 rounded-xl shadow-lg max-w-4xl mx-auto">
                    <h2 class="text-2xl font-bold text-gray-300 mb-6 border-b border-gray-600 pb-3"><?php echo $page_title; ?></h2>
                    
                    <!-- Formulir Tambah Buku -->
                    <form action="<?php echo $process_file; ?>" method="POST" enctype="multipart/form-data">
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            
                            <!-- Book Title -->
                            <div class="mb-4">
                                <label for="title" class="block text-sm font-medium text-gray-400 mb-1">Book Title</label>
                                <input type="text" id="title" name="title" required placeholder="e.g. The Midnight Library"
                                       class="w-full bg-[#4F4F4F] text-white rounded-lg py-2 px-4 focus:outline-none focus:ring-2 focus:ring-purple-400 border border-gray-600">
                            </div>

                            <!-- Author -->
                            <div class="mb-4">
                                <label for="author" class="block text-sm font-medium text-gray-400 mb-1">Author</label>
                                <input type="text" id="author" name="author" required placeholder="e.g. Matt Haig"
                                       class="w-full bg-[#4F4F4F] text-white rounded-lg py-2 px-4 focus:outline-none focus:ring-2 focus:ring-purple-400 border border-gray-600">
                            </div>
                            
                            <!-- Genre -->
                            <div class="mb-4">
                                <label for="genre" class="block text-sm font-medium text-gray-400 mb-1">Genre</label>
                                <input type="text" id="genre" name="genre" required placeholder="e.g. Fantasy"
                                       class="w-full bg-[#4F4F4F] text-white rounded-lg py-2 px-4 focus:outline-none focus:ring-2 focus:ring-purple-400 border border-gray-600">
                            </div>

                            <!-- Rating -->
                            <div class="mb-4">
                                <label for="rating" class="block text-sm font-medium text-gray-400 mb-1">Rating (1.0 - 5.0)</label>
                                <input type="number" step="0.1" min="1" max="5" id="rating" name="rating" required placeholder="e.g. 4.5"
                                       class="w-full bg-[#4F4F4F] text-white rounded-lg py-2 px-4 focus:outline-none focus:ring-2 focus:ring-purple-400 border border-gray-600">
                            </div>

                            <!-- Stock Available -->
                            <div class="mb-4">
                                <label for="stock_available" class="block text-sm font-medium text-gray-400 mb-1">Stock Available</label>
                                <input type="number" id="stock_available" name="stock_available" value="0" required placeholder="e.g. 10"
                                       class="w-full bg-[#4F4F4F] text-white rounded-lg py-2 px-4 focus:outline-none focus:ring-2 focus:ring-purple-400 border border-gray-600">
                            </div>

                            <!-- Stock Needed -->
                            <div class="mb-4">
                                <label for="stock_needed" class="block text-sm font-medium text-gray-400 mb-1">Stock Needed</label>
                                <input type="number" id="stock_needed" name="stock_needed" value="0" required placeholder="e.g. 15"
                                       class="w-full bg-[#4F4F4F] text-white rounded-lg py-2 px-4 focus:outline-none focus:ring-2 focus:ring-purple-400 border border-gray-600">
                            </div>

                            <!-- Cover Image -->
                            <div class="mb-4 md:col-span-2">
                                <label for="cover_image" class="block text-sm font-medium text-gray-400 mb-1">Cover Image</label>
                                <input type="file" id="cover_image" name="cover_image" required
                                       class="w-full bg-[#4F4F4F] text-white rounded-lg py-2 px-4 focus:outline-none focus:ring-2 focus:ring-purple-400 border border-gray-600 file:mr-4 file:py-1 file:px-2 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-purple-500 file:text-white hover:file:bg-purple-600">
                            </div>
                        </div>
                        
                        <!-- Sinopsis (Full Width) -->
                        <div class="mb-6">
                            <label for="synopsis" class="block text-sm font-medium text-gray-400 mb-1">Synopsis</label>
                            <textarea id="synopsis" name="synopsis" rows="6" required placeholder="e.g. A detailed summary of the book's plot and themes."
                                    class="w-full bg-[#4F4F4F] text-white rounded-lg py-2 px-4 focus:outline-none focus:ring-2 focus:ring-purple-400 border border-gray-600">
                            </textarea> 
                        </div>
                        
                        <!-- Tombol Submit -->
                        <div class="flex justify-end gap-3">
                            <a href="data-buku.php" class="bg-gray-600 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded-lg flex items-center">Cancel</a>
                            <button type="submit" class="bg-purple-600 hover:bg-purple-700 text-white font-bold py-2 px-4 rounded-lg flex items-center">
                                <i data-lucide="plus" class="w-5 h-5 mr-2"></i> Tambah Buku
                            </button>
                        </div>
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