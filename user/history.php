<?php
// history.php
session_start();
require_once "../php/config.php";

// Cek login
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../login.php");
    exit;
}

$user_id = $_SESSION['id'];
$transaction_history = [];

// Query: Ambil SEMUA transaksi pengguna, urutkan dari yang terbaru (t.id DESC)
// Kita tetap menyertakan transaksi 'Borrowed' (Aktif) untuk riwayat lengkap,
// tapi kita fokus pada transaksi yang memiliki due_date atau return_date
$sql = "
    SELECT 
        t.id AS transaction_id, 
        t.borrow_date, 
        t.due_date, 
        t.return_date, 
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
        t.user_id = ?
    ORDER BY 
        t.id DESC"; // Urutkan dari transaksi terbaru

if ($stmt = mysqli_prepare($link, $sql)) {
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    if (mysqli_stmt_execute($stmt)) {
        $result = mysqli_stmt_get_result($stmt);
        while ($row = mysqli_fetch_assoc($result)) {
            $transaction_history[] = $row;
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
    <title>History Transaksi - Buku in</title>
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
        <!-- Sidebar Navigation (Pastikan link History aktif) -->
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
                            <i data-lucide="book-open" class="mr-3"></i>Daftar Buku</a>
                        </li>
                    <li class="nav-item rounded-lg mb-2">
                        <a href="profil-user.php" class="flex items-center p-3 rounded-lg">
                        <i data-lucide="file-pen-line" class="mr-3"></i>Edit Profil</a>
                    </li>
                    <li class="nav-item rounded-lg mb-2">
                        <a href="transaksi.php" class="flex items-center p-3 rounded-lg">
                            <i data-lucide="qr-code" class="mr-3"></i>Transaksi</a>
                        </li>
                    <li class="nav-item rounded-lg mb-2">
                        <a href="payment.php" class="flex items-center p-3 rounded-lg">
                            <i data-lucide="wallet" class="mr-3"></i>Pembayaran</a></li>
                    <li class="nav-item rounded-lg mb-2 active-nav">
                        <a href="history.php" class="flex items-center p-3 rounded-lg"><i data-lucide="history" class="mr-3"></i>History</a></li>
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
                    <i data-lucide="history" class="mr-3"></i>
                    <h1 class="text-xl font-semibold">Buku in - Riwayat Transaksi</h1>
                </div>
                <div class="flex items-center">
                    <span class="mr-4"><?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
                    <a href="profil-user.php">
                        <img src="../<?php echo htmlspecialchars($_SESSION['profile_image_url']); ?>" onerror="this.onerror=null; this.src='https://placehold.co/40x40/FFFFFF/333333?text=<?php echo substr(htmlspecialchars($_SESSION['full_name']), 0, 1); ?>'" alt="User Avatar" class="rounded-full w-10 h-10 cursor-pointer object-cover">
                    </a>
                </div>
            </header>

            <div class="flex-1 p-8 overflow-y-auto">
                <h2 class="text-3xl font-bold text-gray-300 mb-6">Riwayat Peminjaman Anda</h2>
                
                <div class="bg-[#333333] p-6 rounded-xl shadow-lg">

                    <?php if (!empty($transaction_history)): ?>
                        <div class="overflow-x-auto">
                            <table class="table-auto min-w-full text-sm">
                                <thead>
                                    <tr class="text-gray-400 border-b border-gray-600 uppercase tracking-wider">
                                        <th class="py-3 px-4 font-medium">Buku</th>
                                        <th class="py-3 px-4 font-medium">Tgl Pinjam</th>
                                        <th class="py-3 px-4 font-medium">Tgl Kembali (Realisasi)</th>
                                        <th class="py-3 px-4 font-medium">Status Pinjam</th>
                                        <th class="py-3 px-4 font-medium">Denda</th>
                                        <th class="py-3 px-4 font-medium">Status Bayar</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($transaction_history as $t): 
                                        $status_class = 'bg-gray-500';
                                        if ($t['status'] == 'Returned') {
                                            $status_class = 'bg-green-600';
                                        } elseif ($t['status'] == 'Borrowed') {
                                            $status_class = 'bg-purple-600';
                                        }
                                        
                                        $fine_text = ($t['fine_amount'] > 0) ? 'Rp. ' . number_format($t['fine_amount'], 0, ',', '.') : '-';
                                        
                                        $payment_class = 'bg-gray-500';
                                        if (strtolower($t['payment_status']) == 'paid' || strtolower($t['payment_status']) == 'dibayar') {
                                            $payment_class = 'bg-blue-600';
                                        } elseif (strtolower($t['payment_status']) != 'paid' && strtolower($t['payment_status']) != 'dibayar' && $t['fine_amount'] > 0) {
                                            $payment_class = 'bg-red-600';
                                        }
                                    ?>
                                    <tr class="border-b border-gray-700 hover:bg-[#4F4F4F]">
                                        <td class="py-3 px-4 font-semibold text-white flex items-center">
                                            <img src="../<?php echo htmlspecialchars($t['cover_image_url']); ?>" class="w-8 h-12 rounded-md mr-3 object-cover shadow-sm" alt="<?php echo htmlspecialchars($t['title']); ?>">
                                            <?php echo htmlspecialchars($t['title']); ?>
                                        </td>
                                        <td class="py-3 px-4 text-gray-300"><?php echo date('d M Y', strtotime($t['borrow_date'])); ?></td>
                                        <td class="py-3 px-4 text-gray-300">
                                            <?php 
                                                // Tampilkan return_date jika ada, jika tidak, tampilkan due_date dengan indikasi status aktif
                                                echo $t['return_date'] ? date('d M Y', strtotime($t['return_date'])) : '<span class="text-purple-400">Aktif (Target: ' . date('d M Y', strtotime($t['due_date'])) . ')</span>';
                                            ?>
                                        </td>
                                        <td class="py-3 px-4">
                                            <span class="<?php echo $status_class; ?> text-white text-xs font-medium px-2.5 py-0.5 rounded-full"><?php echo htmlspecialchars($t['status']); ?></span>
                                        </td>
                                        <td class="py-3 px-4 text-sm font-semibold text-red-400">
                                            <?php echo $fine_text; ?>
                                        </td>
                                        <td class="py-3 px-4">
                                            <span class="<?php echo $payment_class; ?> text-white text-xs font-medium px-2.5 py-0.5 rounded-full">
                                                <?php echo $t['fine_amount'] > 0 ? htmlspecialchars($t['payment_status']) : '-'; ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-10 bg-[#4F4F4F] rounded-lg">
                            <i data-lucide="archive" class="w-12 h-12 text-gray-500 mx-auto mb-3"></i>
                            <p class="text-gray-400 text-lg">Anda belum memiliki riwayat peminjaman.</p>
                            <a href="daftar-buku.php" class="text-purple-400 hover:underline mt-2 inline-block">Cari buku pertama Anda!</a>
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