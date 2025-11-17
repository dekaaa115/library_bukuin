<?php
// payment.php
session_start();
require_once "../php/config.php";

// Cek login
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../login.php");
    exit;
}

$user_id = $_SESSION['id'];
$unpaid_fines = [];

// Query: Ambil semua transaksi dengan denda dan status pembayaran BUKAN 'Sudah Dibayar' atau 'Paid'
$sql = "
    SELECT 
        t.id AS transaction_id, 
        t.borrow_date, 
        t.due_date, 
        t.return_date,
        t.fine_amount, 
        t.payment_status,
        b.title
    FROM 
        transactions t
    INNER JOIN 
        books b ON t.book_id = b.id
    WHERE 
        t.user_id = ? AND t.fine_amount > 0 
        AND (LOWER(t.payment_status) != 'sudah dibayar' AND LOWER(t.payment_status) != 'paid')
    ORDER BY 
        t.due_date ASC";

if ($stmt = mysqli_prepare($link, $sql)) {
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    if (mysqli_stmt_execute($stmt)) {
        $result = mysqli_stmt_get_result($stmt);
        while ($row = mysqli_fetch_assoc($result)) {
            $unpaid_fines[] = $row;
        }
    }
    mysqli_stmt_close($stmt);
}

// Hitung total denda
$total_fine = array_sum(array_column($unpaid_fines, 'fine_amount'));

// Ambil pesan dari URL (jika ada)
$status_message = '';
if (isset($_GET['status']) && $_GET['status'] == 'submitted') {
    $status_message = "Pengajuan pembayaran berhasil dikirim. Menunggu konfirmasi dari Admin.";
}
if (isset($_GET['error'])) {
    $status_message = "Error: " . htmlspecialchars($_GET['error']);
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pembayaran Denda - Buku in</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .table-auto { width: 100%; border-collapse: collapse; }
        .table-auto th, .table-auto td { padding: 12px 16px; text-align: left; }
    </style>
</head>
<body class="bg-[#212121] text-white">

    <div class="flex h-screen bg-[#212121] text-white">
        <!-- Sidebar Navigation -->
        <nav class="w-64 bg-[#333333] p-6 flex flex-col justify-between">
            <div>
                <div class="flex flex-col items-center mb-10">
                    <img src="../<?php echo htmlspecialchars($_SESSION['profile_image_url'] ?? 'https://placehold.co/100x100/A78BFA/FFFFFF?text=' . substr($_SESSION['full_name'], 0, 1)); ?>" onerror="this.onerror=null; this.src='https://placehold.co/100x100/A78BFA/FFFFFF?text=<?php echo substr(htmlspecialchars($_SESSION['full_name']), 0, 1); ?>'" alt="User Profile" class="rounded-full w-24 h-24 mb-4 border-2 border-purple-400 object-cover">
                    <h3 class="font-bold text-lg"><?php echo htmlspecialchars($_SESSION['full_name']); ?></h3>
                    <p class="text-sm bg-blue-500 px-3 py-1 rounded-full mt-2">Pengguna</p>
                </div>
                <ul>
                    <li class="nav-item rounded-lg mb-2"><a href="daftar-buku.php" class="flex items-center p-3 rounded-lg"><i data-lucide="book-open" class="mr-3"></i>Daftar Buku</a></li>
                    <li class="nav-item rounded-lg mb-2"><a href="profil-user.php" class="flex items-center p-3 rounded-lg"><i data-lucide="file-pen-line" class="mr-3"></i>Edit Profil</a></li>
                    <li class="nav-item rounded-lg mb-2"><a href="transaksi.php" class="flex items-center p-3 rounded-lg"><i data-lucide="qr-code" class="mr-3"></i>Transaksi</a></li>
                    <li class="nav-item rounded-lg mb-2 active-nav"><a href="payment.php" class="flex items-center p-3 rounded-lg"><i data-lucide="wallet" class="mr-3"></i>Pembayaran</a></li>
                    <li class="nav-item rounded-lg mb-2"><a href="history.php" class="flex items-center p-3 rounded-lg"><i data-lucide="history" class="mr-3"></i>History</a></li>
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
                    <i data-lucide="wallet" class="mr-3"></i>
                    <h1 class="text-xl font-semibold">Buku in - Pembayaran Denda</h1>
                </div>
            </header>

            <div class="flex-1 p-8 overflow-y-auto">
                <h2 class="text-3xl font-bold text-gray-300 mb-6">Daftar Denda yang Belum Dibayar</h2>
                
                <div class="bg-[#333333] p-6 rounded-xl shadow-lg">
                    
                    <?php if ($status_message): ?>
                        <div class="bg-yellow-600/30 text-yellow-300 p-4 rounded-lg mb-6 flex items-center border border-yellow-700">
                            <i data-lucide="alert-triangle" class="w-5 h-5 mr-3"></i>
                            <span><?php echo $status_message; ?></span>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($total_fine > 0): ?>
                        
                        <!-- Box Total Denda -->
                        <div class="bg-red-900/30 p-4 rounded-lg mb-6 flex justify-between items-center border border-red-700">
                            <span class="text-xl font-bold">Total Denda yang Harus Dibayar:</span>
                            <span class="text-2xl font-extrabold text-red-400">Rp. <?php echo number_format($total_fine, 0, ',', '.'); ?></span>
                        </div>
                        
                        <div class="overflow-x-auto mb-6">
                            <table class="table-auto min-w-full text-sm">
                                <thead>
                                    <tr class="text-gray-400 border-b border-gray-600 uppercase tracking-wider">
                                        <th class="py-3 px-4 font-medium">Buku</th>
                                        <th class="py-3 px-4 font-medium">Tgl Kembali (Realisasi)</th>
                                        <th class="py-3 px-4 font-medium">Denda</th>
                                        <th class="py-3 px-4 font-medium">Status Bayar</th>
                                        <th class="py-3 px-4 font-medium">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    foreach ($unpaid_fines as $f): 
                                        // Pengecekan status diajukan (case-insensitive)
                                        $is_submitted = (strtolower($f['payment_status']) == 'diajukan' || strtolower($f['payment_status']) == 'pending');
                                    ?>
                                    <tr class="border-b border-gray-700 hover:bg-[#4F4F4F]">
                                        <td class="py-3 px-4 font-semibold text-white"><?php echo htmlspecialchars($f['title']); ?></td>
                                        <td class="py-3 px-4 text-sm text-gray-300"><?php echo date('d M Y', strtotime($f['return_date'] ?? '1970-01-01')); ?></td>
                                        <td class="py-3 px-4 text-sm font-semibold text-red-400">Rp. <?php echo number_format($f['fine_amount'], 0, ',', '.'); ?></td>
                                        <td class="py-3 px-4">
                                            <span class="bg-red-500 text-white text-xs font-medium px-2.5 py-0.5 rounded-full">
                                                <?php echo htmlspecialchars($f['payment_status']); ?>
                                            </span>
                                        </td>
                                        <td class="py-3 px-4">
                                            <?php if ($is_submitted): ?>
                                                <button disabled class="bg-yellow-600 text-white text-xs font-medium py-1.5 px-3 rounded-lg opacity-70 cursor-not-allowed">
                                                    Menunggu Konfirmasi
                                                </button>
                                            <?php else: ?>
                                                <!-- TOMBOL AKSI: Mengarahkan ke halaman detail pembayaran -->
                                                <a href="ajukan_pembayaran.php" 
                                                   class="bg-purple-600 hover:bg-purple-700 text-white text-xs font-medium py-1.5 px-3 rounded-lg transition-colors">
                                                    Ajukan Pembayaran
                                                </a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                    <?php else: ?>
                        <div class="text-center py-10 bg-[#4F4F4F] rounded-lg">
                            <i data-lucide="check-circle" class="w-12 h-12 text-green-500 mx-auto mb-3"></i>
                            <p class="text-gray-400 text-lg">Semua denda Anda sudah lunas atau menunggu konfirmasi Admin.</p>
                        </div>
                    <?php endif; ?>

                </div>
            </div>
        </main>
    </div>
    <script>
        lucide.createIcons();
    </script>
</body>
</html>