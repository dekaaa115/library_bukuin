<?php
// payment_confirmation.php
session_start();
require_once "../php/config.php";

// Pengecekan Admin (Asumsi role 'admin')
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== 'admin') {
    header("location: ../login.php");
    exit;
}

$pending_fines = [];

// Ambil data spesifik dari transaction_id jika dikirimkan dari admin/transaksi.php
$filter_id = isset($_GET['transaction_id']) ? intval($_GET['transaction_id']) : 0;
// Filter: Cari transaksi yang sudah diajukan ('Diajukan') dan memiliki denda
$where_clause = $filter_id > 0 ? "t.id = $filter_id AND t.payment_status = 'Diajukan'" : "t.payment_status = 'Diajukan'";

// Query: Ambil semua transaksi yang status pembayarannya 'Diajukan'
$sql = "
    SELECT 
        t.id AS transaction_id, 
        t.return_date,
        t.fine_amount, 
        t.payment_status,
        t.proof_path, /* KOLOM KRITIS UNTUK BUKTI BAYAR */
        u.full_name AS user_name,
        b.title
    FROM 
        transactions t
    INNER JOIN 
        users u ON t.user_id = u.id
    INNER JOIN 
        books b ON t.book_id = b.id
    WHERE 
        $where_clause
    ORDER BY 
        t.return_date ASC";

if ($result = mysqli_query($link, $sql)) {
    while ($row = mysqli_fetch_assoc($result)) {
        $pending_fines[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Konfirmasi Pembayaran Denda (Admin)</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        .table-auto { width: 100%; border-collapse: collapse; }
        .table-auto th, .table-auto td { padding: 12px 16px; text-align: left; }
    </style>
</head>
<body class="bg-gray-100 text-gray-800">
    <!-- Asumsi Anda memiliki Header/Sidebar Admin yang sesuai -->
    <div class="flex h-screen bg-[#212121] text-white">
        <!-- Sidebar Navigation -->
        <nav class="w-64 bg-[#333333] p-6 flex flex-col justify-between">
            <div>
                <div class="flex flex-col items-center mb-10">
                    <h3 class="font-bold text-lg">Admin Dashboard</h3>
                    <p class="text-sm bg-red-500 px-3 py-1 rounded-full mt-2">Administrator</p>
                </div>
                <ul>
                    <li class="nav-item rounded-lg mb-2"><a href="data-buku.php" class="flex items-center p-3 rounded-lg"><i data-lucide="book-open" class="mr-3"></i>Data Buku</a></li>
                    <li class="nav-item rounded-lg mb-2"><a href="data-anggota.php" class="flex items-center p-3 rounded-lg"><i data-lucide="users" class="mr-3"></i>Data Anggota</a></li>
                    <li class="nav-item rounded-lg mb-2 active-nav"><a href="transaksi.php" class="flex items-center p-3 rounded-lg"><i data-lucide="clipboard-list" class="mr-3"></i>Transaksi</a></li>
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
            <header class="bg-[#A78BFA] text-black p-4 flex justify-between items-center shadow-md">
                <div class="flex items-center">
                    <i data-lucide="wallet" class="mr-3"></i>
                    <h1 class="text-xl font-semibold">Konfirmasi Pembayaran Denda</h1>
                </div>
            </header>

            <div class="flex-1 p-8 overflow-y-auto">
                <?php if (isset($_GET['status']) && $_GET['status'] == 'confirmed'): ?>
                    <div class="bg-green-600/30 text-green-300 p-4 rounded-lg mb-6 flex items-center border border-green-700">
                        <i data-lucide="check-circle" class="w-5 h-5 mr-3"></i>
                        <span>Pembayaran berhasil dikonfirmasi. Status denda sudah diperbarui menjadi Lunas.</span>
                    </div>
                <?php endif; ?>
                
                <div class="bg-[#333333] p-6 rounded-xl shadow-lg">

                    <?php if (!empty($pending_fines)): ?>
                        <div class="overflow-x-auto">
                            <table class="table-auto min-w-full text-sm bg-[#333333] border-collapse">
                                <thead>
                                    <tr class="text-gray-400 border-b border-gray-600 uppercase text-xs tracking-wider">
                                        <th class="py-3 px-4 font-medium">Pengguna</th>
                                        <th class="py-3 px-4 font-medium">Buku</th>
                                        <th class="py-3 px-4 font-medium">Tgl Kembali</th>
                                        <th class="py-3 px-4 font-medium">Denda</th>
                                        <th class="py-3 px-4 font-medium">Bukti Bayar</th>
                                        <th class="py-3 px-4 font-medium">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($pending_fines as $f): ?>
                                    <tr class="border-b border-gray-700 hover:bg-[#4F4F4F]">
                                        <td class="py-4 px-4 font-semibold text-white"><?php echo htmlspecialchars($f['user_name']); ?></td>
                                        <td class="py-4 px-4 text-gray-300"><?php echo htmlspecialchars($f['title']); ?></td>
                                        <td class="py-4 px-4 text-sm text-gray-300"><?php echo date('d M Y', strtotime($f['return_date'])); ?></td>
                                        <td class="py-4 px-4 text-base font-bold text-red-400">Rp. <?php echo number_format($f['fine_amount'], 0, ',', '.'); ?></td>
                                        <td class="py-4 px-4">
                                            <?php if ($f['proof_path']): ?>
                                                <!-- Tautan untuk melihat/mengunduh bukti bayar -->
                                                <a href="../<?php echo htmlspecialchars($f['proof_path']); ?>" target="_blank" class="text-blue-400 hover:underline text-sm flex items-center">
                                                    <i data-lucide="file-text" class="w-4 h-4 mr-1"></i> Lihat Bukti
                                                </a>
                                            <?php else: ?>
                                                <span class="text-gray-500 text-sm">Tidak ada bukti</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="py-4 px-4">
                                            <!-- Tombol Konfirmasi -->
                                            <a href="../php/payment_update_process.php?action=confirm&transaction_id=<?php echo $f['transaction_id']; ?>" 
                                               onclick="return confirm('KONFIRMASI: Verifikasi pembayaran lunas Rp. <?php echo number_format($f['fine_amount'], 0, ',', '.'); ?> dari <?php echo htmlspecialchars($f['user_name']); ?>?')"
                                               class="bg-green-500 hover:bg-green-600 text-white text-sm font-medium py-1.5 px-3 rounded-lg transition-colors">
                                                Konfirmasi Lunas
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-10 bg-[#4F4F4F] rounded-lg">
                            <i data-lucide="bell" class="w-12 h-12 text-gray-400 mx-auto mb-3"></i>
                            <p class="text-gray-400 text-lg">Tidak ada denda yang menunggu konfirmasi pembayaran.</p>
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
</html>~