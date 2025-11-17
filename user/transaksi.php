<?php
// transaksi.php
session_start();
require_once "../php/config.php";

// Cek login
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../login.php");
    exit;
}

$user_id = $_SESSION['id'];
$active_transactions = [];
$today = date('Y-m-d');

// Query: Ambil transaksi pinjam AKTIF ('Borrowed') dan yang MENUNGGU KONFIRMASI ('Pending Return')
$sql = "
    SELECT 
        t.id AS transaction_id, 
        t.borrow_date, 
        t.due_date, 
        t.status,
        t.fine_amount, 
        t.payment_status,
        b.title, 
        b.author, 
        b.cover_image_url
    FROM 
        transactions t
    INNER JOIN 
        books b ON t.book_id = b.id
    WHERE 
        t.user_id = ? AND (t.status = 'Borrowed' OR t.status = 'Pending Return')
    ORDER BY 
        t.due_date ASC";

if ($stmt = mysqli_prepare($link, $sql)) {
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    if (mysqli_stmt_execute($stmt)) {
        $result = mysqli_stmt_get_result($stmt);
        while ($row = mysqli_fetch_assoc($result)) {
            $active_transactions[] = $row;
        }
    }
    mysqli_stmt_close($stmt);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transaksi Aktif - Buku in</title>
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
                    <li class="nav-item rounded-lg mb-2 active-nav">
                        <a href="transaksi.php" class="flex items-center p-3 rounded-lg">
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
            <!-- Header -->
            <header class="bg-[#A78BFA] text-black p-4 flex justify-between items-center shadow-md">
                <div class="flex items-center">
                    <i data-lucide="library" class="mr-3"></i>
                    <h1 class="text-xl font-semibold">Buku in - Transaksi Aktif</h1>
                </div>
                <div class="flex items-center">
                    <span class="mr-4"><?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
                    <a href="profil-user.php">
                        <img src="../<?php echo htmlspecialchars($_SESSION['profile_image_url']); ?>" onerror="this.onerror=null; this.src='https://placehold.co/40x40/FFFFFF/333333?text=<?php echo substr(htmlspecialchars($_SESSION['full_name']), 0, 1); ?>'" alt="User Avatar" class="rounded-full w-10 h-10 cursor-pointer object-cover">
                    </a>
                </div>
            </header>

            <div class="flex-1 p-8 overflow-y-auto">
                <h2 class="text-3xl font-bold text-gray-300 mb-6">Buku yang Sedang Dipinjam</h2>
                
                <div class="bg-[#333333] p-6 rounded-xl shadow-lg">
                    
                    <?php 
                    // Tampilkan pesan setelah pengembalian
                    if (isset($_GET['status'])):
                        if ($_GET['status'] == 'success'): ?>
                            <div class="bg-green-600/30 text-green-300 p-4 rounded-lg mb-6 flex items-center border border-green-700">
                                <i data-lucide="check-circle" class="w-5 h-5 mr-3"></i>
                                <span>Pesanan pinjam buku berhasil dikonfirmasi!</span>
                            </div>
                        <?php elseif ($_GET['status'] == 'pending_return'): 
                            $fine = isset($_GET['fine']) ? intval($_GET['fine']) : 0;
                            if ($fine > 0): ?>
                                <div class="bg-yellow-600/30 text-yellow-300 p-4 rounded-lg mb-6 flex items-center border border-yellow-700">
                                    <i data-lucide="clock" class="w-5 h-5 mr-3"></i>
                                    <span>Pengajuan pengembalian berhasil! Ada denda **Rp. <?php echo number_format($fine, 0, ',', '.'); ?>**. Menunggu konfirmasi Admin.</span>
                                </div>
                            <?php else: ?>
                                <div class="bg-yellow-600/30 text-yellow-300 p-4 rounded-lg mb-6 flex items-center border border-yellow-700">
                                    <i data-lucide="clock" class="w-5 h-5 mr-3"></i>
                                    <span>Pengajuan pengembalian berhasil! Buku sudah diserahkan. Menunggu konfirmasi Admin.</span>
                                </div>
                            <?php endif; ?>
                        <?php elseif ($_GET['status'] == 'returned'): // Status ini hanya terjadi setelah Admin mengkonfirmasi
                            $fine = isset($_GET['fine']) ? intval($_GET['fine']) : 0;
                            if ($fine > 0): ?>
                                <div class="bg-green-600/30 text-green-300 p-4 rounded-lg mb-6 flex items-center border border-green-700">
                                    <i data-lucide="check-circle" class="w-5 h-5 mr-3"></i>
                                    <span>Buku berhasil dikonfirmasi kembali oleh Admin. Denda Rp. <?php echo number_format($fine, 0, ',', '.'); ?> telah dicatat.</span>
                                </div>
                            <?php else: ?>
                                <div class="bg-green-600/30 text-green-300 p-4 rounded-lg mb-6 flex items-center border border-green-700">
                                    <i data-lucide="check-circle" class="w-5 h-5 mr-3"></i>
                                    <span>Buku berhasil dikonfirmasi kembali oleh Admin. Terima kasih!</span>
                                </div>
                            <?php endif; ?>
                        <?php elseif ($_GET['error'] == 'process_failed'): ?>
                             <div class="bg-red-600/30 text-red-300 p-4 rounded-lg mb-6 flex items-center border border-red-700">
                                <i data-lucide="x-circle" class="w-5 h-5 mr-3"></i>
                                <span>Gagal memproses pengajuan pengembalian. Silakan coba lagi.</span>
                            </div>
                        <?php endif; 
                    endif; ?>

                    <?php if (!empty($active_transactions)): ?>
                        <div class="overflow-x-auto">
                            <table class="table-auto min-w-full">
                                <thead>
                                    <tr class="text-gray-400 border-b border-gray-600 uppercase text-sm tracking-wider">
                                        <th class="py-3 px-4 font-medium">Buku</th>
                                        <th class="py-3 px-4 font-medium">Penulis</th>
                                        <th class="py-3 px-4 font-medium">Tgl Kembali</th>
                                        <th class="py-3 px-4 font-medium">Denda</th>
                                        <th class="py-3 px-4 font-medium">Status</th>
                                        <th class="py-3 px-4 font-medium">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($active_transactions as $t): 
                                        $is_overdue = ($t['due_date'] < $today);
                                        
                                        $due_date_status = 'text-yellow-400';
                                        $button_disabled = false;
                                        $button_text = 'Kembalikan';
                                        $button_class = 'bg-green-600 hover:bg-green-700';

                                        if ($t['status'] == 'Pending Return') {
                                            $due_date_status = 'text-yellow-500 font-bold';
                                            $button_disabled = true;
                                            $button_text = 'Menunggu Konfirmasi';
                                            $button_class = 'bg-yellow-600 cursor-not-allowed';
                                        } elseif ($is_overdue) {
                                            $due_date_status = 'text-red-500 font-bold';
                                        }

                                        // LOGIC untuk Denda dan Status Pembayaran
                                        $fine_text = ($t['fine_amount'] > 0) ? 'Rp. ' . number_format($t['fine_amount'], 0, ',', '.') : 'Rp. 0';
                                        $is_unpaid = ($t['fine_amount'] > 0 && strtolower($t['payment_status']) != 'paid' && strtolower($t['payment_status']) != 'dibayar');
                                        $payment_status_class = $is_unpaid ? 'bg-red-500' : 'bg-gray-500';
                                    ?>
                                    <tr class="border-b border-gray-700 hover:bg-[#4F4F4F]">
                                        <td class="py-4 px-4 flex items-center">
                                            <img src="../<?php echo htmlspecialchars($t['cover_image_url']); ?>" class="w-10 h-14 rounded-md mr-3 object-cover shadow-sm" alt="<?php echo htmlspecialchars($t['title']); ?>">
                                            <span class="font-semibold text-white"><?php echo htmlspecialchars($t['title']); ?></span>
                                        </td>
                                        <td class="py-4 px-4 text-gray-300"><?php echo htmlspecialchars($t['author']); ?></td>
                                        <td class="py-4 px-4 text-sm <?php echo $due_date_status; ?>">
                                            <?php echo date('d M Y', strtotime($t['due_date'])); ?>
                                            <?php if ($is_overdue && $t['status'] != 'Pending Return'): ?>
                                                <span class="text-red-600 text-xs block">(Terlambat)</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="py-4 px-4 text-sm font-semibold text-red-400">
                                            <?php echo $fine_text; ?>
                                        </td>
                                        <td class="py-4 px-4">
                                            <span class="bg-purple-600 text-white text-xs font-medium px-2.5 py-0.5 rounded-full"><?php echo htmlspecialchars($t['status']); ?></span>
                                            <span class="<?php echo $payment_status_class; ?> text-white text-xs font-medium px-2.5 py-0.5 rounded-full block mt-1">Bayar: <?php echo htmlspecialchars($t['payment_status']); ?></span>
                                        </td>
                                        <td class="py-4 px-4">
                                            <?php if ($t['status'] == 'Borrowed'): ?>
                                                <a href="../php/return_process.php?transaction_id=<?php echo $t['transaction_id']; ?>" 
                                                   onclick="return confirm('Apakah Anda yakin sudah menyerahkan buku ini di perpustakaan?')"
                                                   class="<?php echo $button_class; ?> text-white text-sm font-medium py-1.5 px-3 rounded-lg transition-colors">
                                                    Kembalikan
                                                </a>
                                            <?php else: ?>
                                                <button disabled class="<?php echo $button_class; ?> text-white text-sm font-medium py-1.5 px-3 rounded-lg opacity-70">
                                                    <?php echo $button_text; ?>
                                                </button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-10 bg-[#4F4F4F] rounded-lg">
                            <i data-lucide="book-open" class="w-12 h-12 text-gray-500 mx-auto mb-3"></i>
                            <p class="text-gray-400 text-lg">Anda tidak memiliki buku yang sedang dipinjam atau menunggu konfirmasi saat ini.</p>
                            <a href="daftar-buku.php" class="text-purple-400 hover:underline mt-2 inline-block">Mulai pinjam sekarang!</a>
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