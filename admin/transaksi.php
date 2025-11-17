<?php
// admin/transaksi.php
session_start();
require_once "../php/config.php";

// Pengecekan Admin
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== 'admin') {
    header("location: ../login.php");
    exit;
}

// Tentukan halaman aktif
$current_page = basename($_SERVER['PHP_SELF']);

// Ambil data admin (untuk sidebar)
$user_id = $_SESSION["id"];
$sql_admin = "SELECT full_name, profile_image_url FROM users WHERE id = ?";
$stmt_admin = mysqli_prepare($link, $sql_admin);
mysqli_stmt_bind_param($stmt_admin, "i", $user_id);
mysqli_stmt_execute($stmt_admin);
$result_admin = mysqli_stmt_get_result($stmt_admin);
$admin_data = mysqli_fetch_assoc($result_admin);

// FIX variabel foto + nama
$full_name = $admin_data['full_name'] ?? 'Admin';
$profile_image_url = $admin_data['profile_image_url'] ?? 'uploads/default.png';

// Ambil semua transaksi
$transactions = [];
$sql = "
    SELECT 
        t.id AS transaction_id, 
        t.borrow_date, 
        t.due_date, 
        t.return_date, 
        t.status,
        t.fine_amount, 
        t.payment_status,
        u.full_name AS user_name,
        b.title
    FROM 
        transactions t
    INNER JOIN 
        users u ON t.user_id = u.id
    INNER JOIN 
        books b ON t.book_id = b.id
    ORDER BY t.id DESC
";

if ($result = mysqli_query($link, $sql)) {
    while ($row = mysqli_fetch_assoc($result)) {
        $transactions[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transaksi (Admin)</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
</head>

<body class="bg-[#212121] text-white">

<div class="flex h-screen">

    <!-- SIDEBAR PREMIUM -->
    <nav class="w-64 bg-[#333333] p-6 flex flex-col justify-between">

        <div>
            <div class="flex flex-col items-center mb-10">
                <!-- FOTO PROFIL DI SIDEBAR -->
                <img 
                    src="../<?php echo $profile_image_url; ?>" 
                    onerror="this.onerror=null; this.src='https://placehold.co/100x100/A78BFA/FFFFFF?text=A';"
                    alt="Admin Profile"
                    class="rounded-full w-24 h-24 mb-4 border-2 border-green-500 object-cover"
                >

                <h3 class="font-bold text-lg">
                    <?php echo htmlspecialchars($full_name); ?>
                </h3>

                <p class="text-sm bg-green-500 px-3 py-1 rounded-full mt-2">
                    Administrator
                </p>
            </div>

            <ul>
                <!-- Data Buku -->
                <li class="rounded-lg mb-2 <?php echo ($current_page == 'data-buku.php') ? 'bg-[#A78BFA] text-black' : 'hover:bg-[#4F4F4F]'; ?>">
                    <a href="data-buku.php" class="flex items-center p-3 rounded-lg">
                        <i data-lucide="book-open" class="mr-3"></i>Data Buku
                    </a>
                </li>

                <!-- Data Anggota -->
                <li class="rounded-lg mb-2 <?php echo ($current_page == 'data-anggota.php') ? 'bg-[#A78BFA] text-black' : 'hover:bg-[#4F4F4F]'; ?>">
                    <a href="data-anggota.php" class="flex items-center p-3 rounded-lg">
                        <i data-lucide="users" class="mr-3"></i>Data Anggota
                    </a>
                </li>

                <!-- Transaksi -->
                <li class="rounded-lg mb-2 <?php echo ($current_page == 'transaksi.php') ? 'bg-[#A78BFA] text-black' : 'hover:bg-[#4F4F4F]'; ?>">
                    <a href="transaksi.php" class="flex items-center p-3 rounded-lg">
                        <i data-lucide="clipboard-list" class="mr-3"></i>Transaksi
                    </a>
                </li>

                <!-- Laporan -->
                <li class="rounded-lg mb-2 <?php echo ($current_page == 'laporan.php') ? 'bg-[#A78BFA] text-black' : 'hover:bg-[#4F4F4F]'; ?>">
                    <a href="laporan.php" class="flex items-center p-3 rounded-lg">
                        <i data-lucide="bar-chart-3" class="mr-3"></i>Laporan
                    </a>
                </li>

                <!-- Edit Profil -->
                <li class="rounded-lg mb-2 <?php echo ($current_page == 'profil-admin.php') ? 'bg-[#A78BFA] text-black' : 'hover:bg-[#4F4F4F]'; ?>">
                    <a href="profil-admin.php" class="flex items-center p-3 rounded-lg">
                        <i data-lucide="user-cog" class="mr-3"></i>Edit Profil
                    </a>
                </li>
            </ul>
        </div>

        <!-- Logout -->
        <div>
            <a href="../php/logout.php" class="flex items-center p-3 rounded-lg hover:bg-[#4F4F4F]">
                <i data-lucide="log-out" class="mr-3"></i>Logout
            </a>
        </div>
    </nav>

    <!-- MAIN CONTENT -->
<main class="flex-1 flex flex-col">
            <!-- Header -->
            <header class="bg-[#A78BFA] text-black p-4 flex justify-between items-center shadow-md">
                <div class="flex items-center">
                    <i data-lucide="clipboard-list" class="mr-3"></i>
                    <h1 class="text-xl font-semibold">Laporan Transaksi Peminjaman</h1>
                </div>
            </header>

            <div class="flex-1 p-8 overflow-y-auto">
                <h2 class="text-3xl font-bold text-gray-300 mb-6">Daftar Semua Transaksi</h2>
                
                <div class="bg-[#333333] p-6 rounded-xl shadow-lg">
                    <?php if (isset($_GET['status']) && $_GET['status'] == 'return_confirmed'): ?>
                        <div class="bg-green-600/30 text-green-300 p-4 rounded-lg mb-6 flex items-center border border-green-700">
                            <i data-lucide="check-circle" class="w-5 h-5 mr-3"></i>
                            <span>Pengembalian berhasil dikonfirmasi dan stok buku telah ditambahkan kembali.</span>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($transactions)): ?>
                        <div class="overflow-x-auto">
                            <table class="table-auto min-w-full text-sm bg-[#333333] border-collapse">
                                <thead>
                                    <tr class="text-gray-400 border-b border-gray-600 uppercase text-xs tracking-wider">
                                        <th class="py-3 px-4 font-medium text-center">No</th>
                                        <th class="py-3 px-4 font-medium">Peminjam</th>
                                        <th class="py-3 px-4 font-medium">Buku</th>
                                        <th class="py-3 px-4 font-medium">Tgl Pinjam</th>
                                        <th class="py-3 px-4 font-medium">Jatuh Tempo</th>
                                        <th class="py-3 px-4 font-medium">Status Pinjam</th>
                                        <th class="py-3 px-4 font-medium">Denda (Bayar)</th>
                                        <th class="py-3 px-4 font-medium">Aksi Admin</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $counter = 1; ?>
                                    <?php foreach ($transactions as $t): 
                                        $today = date('Y-m-d');
                                        $is_overdue = ($t['due_date'] < $today && $t['status'] == 'Borrowed');
                                        $is_pending_return = ($t['status'] == 'Pending Return');
                                        
                                        // Variabel untuk status pembayaran (Semua dalam lowercase untuk perbandingan)
                                        $payment_status_lower = strtolower($t['payment_status']);
                                        $is_paid = ($payment_status_lower == 'sudah dibayar' || $payment_status_lower == 'paid' || $payment_status_lower == 'dibayar');
                                        $is_fine_unpaid_submitted = ($t['fine_amount'] > 0 && !$is_paid); // TRUE jika ada denda dan BELUM LUNAS
                                        
                                        $status_class = 'bg-gray-500';
                                        if ($t['status'] == 'Returned') $status_class = 'bg-green-600';
                                        if ($t['status'] == 'Borrowed') $status_class = 'bg-purple-600';
                                        if ($is_pending_return) $status_class = 'bg-yellow-600';
                                        if ($is_overdue) $status_class = 'bg-red-600';

                                        $fine_text = ($t['fine_amount'] > 0) ? 'Rp. ' . number_format($t['fine_amount'], 0, ',', '.') : 'Rp. 0';
                                        $payment_class = $is_paid ? 'bg-green-500' : 'bg-red-500';
                                    ?>
                                    <tr class="border-b border-gray-700 hover:bg-[#4F4F4F]">
                                        <td class="py-3 px-4 text-center text-gray-400"><?php echo $counter++; ?></td>
                                        <td class="py-3 px-4 font-semibold text-white"><?php echo htmlspecialchars($t['user_name']); ?></td>
                                        <td class="py-3 px-4 text-gray-300"><?php echo htmlspecialchars($t['title']); ?></td>
                                        <td class="py-3 px-4 text-gray-300"><?php echo date('d M Y', strtotime($t['borrow_date'])); ?></td>
                                        <td class="py-3 px-4 text-sm <?php echo $is_overdue ? 'text-red-400 font-bold' : 'text-gray-300'; ?>">
                                            <?php echo date('d M Y', strtotime($t['due_date'])); ?>
                                        </td>
                                        <td class="py-3 px-4">
                                            <span class="<?php echo $status_class; ?> text-white text-xs font-medium px-2.5 py-0.5 rounded-full block mb-1">Pinjam: <?php echo htmlspecialchars($t['status']); ?></span>
                                            <span class="<?php echo $payment_class; ?> text-white text-xs font-medium px-2.5 py-0.5 rounded-full block">Bayar: <?php echo htmlspecialchars($t['payment_status']); ?></span>
                                        </td>
                                        <td class="py-3 px-4 text-center">
                                            <?php if ($is_pending_return): ?>
                                                <!-- Tombol KONFIRMASI KEMBALI -->
                                                <a href="../php/confirm_return.php?transaction_id=<?php echo $t['transaction_id']; ?>" 
                                                    onclick="return confirm('KONFIRMASI: Verifikasi buku telah dikembalikan oleh <?php echo htmlspecialchars($t['user_name']); ?>?')"
                                                    class="bg-blue-600 hover:bg-blue-700 text-white text-xs py-1.5 px-3 rounded-lg flex items-center justify-center">
                                                    <i data-lucide="check" class="w-4 h-4 mr-1"></i> Konfirmasi Kembali
                                                </a>
                                            <?php elseif ($is_fine_unpaid_submitted): ?>
                                                <!-- TOMBOL CEK DENDA BELUM BAYAR -->
                                                <a href="payment_confirmation.php?transaction_id=<?php echo $t['transaction_id']; ?>" 
                                                    class="bg-red-600 hover:bg-red-700 text-white text-xs py-1.5 px-3 rounded-lg flex items-center justify-center">
                                                    <i data-lucide="dollar-sign" class="w-4 h-4 mr-1"></i> Cek Denda Belum Bayar
                                                </a>
                                            <?php else: ?>
                                                <span class="text-gray-500 text-xs">Selesai / Aktif</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-center text-gray-400 py-10">Tidak ada data transaksi yang ditemukan.</p>
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
