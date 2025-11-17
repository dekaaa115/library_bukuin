<?php
// admin/laporan.php
session_start();
require_once "../php/config.php";

// Pengecekan Admin
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== 'admin') {
    header("location: ../login.php");
    exit;
}

// Ambil nama + foto profil admin untuk sidebar
$user_id = $_SESSION["id"];
$user_data = [];

$sql = "SELECT full_name, profile_image_url FROM users WHERE id = ?";
if ($stmt = mysqli_prepare($link, $sql)) {
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $user_data = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
}

$full_name = htmlspecialchars($user_data['full_name'] ?? 'Administrator');
$profile_image_url = htmlspecialchars($user_data['profile_image_url'] ?? 'https://placehold.co/100x100/A78BFA/FFFFFF?text=A');

$today = date('Y-m-d');
$stats = [];
$overdue_borrows = [];
$fine_per_day = 2000.00;

// Statistik
$result = mysqli_query($link, "SELECT COUNT(id) FROM books");
$stats['total_books'] = mysqli_fetch_array($result)[0];

$result = mysqli_query($link, "SELECT COUNT(id) FROM users WHERE role = 'user'");
$stats['total_users'] = mysqli_fetch_array($result)[0];

$result = mysqli_query($link, "SELECT COUNT(id) FROM transactions WHERE status='Borrowed' OR status='Pending Return'");
$stats['active_borrows'] = mysqli_fetch_array($result)[0];

$result = mysqli_query($link, "SELECT SUM(fine_amount) FROM transactions WHERE fine_amount > 0 AND (payment_status != 'Sudah Dibayar')");
$stats['unpaid_fines'] = mysqli_fetch_array($result)[0] ?? 0;

// Overdue
$sql_overdue = "
    SELECT 
        t.id AS transaction_id, 
        t.due_date, 
        u.full_name AS user_name,
        b.title
    FROM transactions t
    INNER JOIN users u ON t.user_id = u.id
    INNER JOIN books b ON t.book_id = b.id
    WHERE t.status = 'Borrowed' AND t.due_date < '$today'
    ORDER BY t.due_date ASC
    LIMIT 10";

if ($result = mysqli_query($link, $sql_overdue)) {
    while ($row = mysqli_fetch_assoc($result)) {
        $overdue_borrows[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Sistem - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>

    <!-- PENTING: SELARAS DENGAN PROFIL-ADMIN -->
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
                <img src="../<?php echo $profile_image_url; ?>" 
                     onerror="this.onerror=null; this.src='https://placehold.co/100x100/A78BFA/FFFFFF?text=A';"
                     class="rounded-full w-24 h-24 mb-4 border-2 border-green-500 object-cover">
                <h3 class="font-bold text-lg"><?php echo $full_name; ?></h3>
                <p class="text-sm bg-green-500 px-3 py-1 rounded-full mt-2">Administrator</p>
            </div>

            <ul>
                <li class="nav-item rounded-lg mb-2">
                    <a href="data-buku.php" class="flex items-center p-3 rounded-lg">
                        <i data-lucide="book-open" class="mr-3"></i>Data Buku
                    </a>
                </li>

                <li class="nav-item rounded-lg mb-2">
                    <a href="data-anggota.php" class="flex items-center p-3 rounded-lg">
                        <i data-lucide="users" class="mr-3"></i>Data Anggota
                    </a>
                </li>

                <li class="nav-item rounded-lg mb-2">
                    <a href="transaksi.php" class="flex items-center p-3 rounded-lg">
                        <i data-lucide="clipboard-list" class="mr-3"></i>Transaksi
                    </a>
                </li>

                <li class="nav-item rounded-lg mb-2 active-nav">
                    <a href="laporan.php" class="flex items-center p-3 rounded-lg">
                        <i data-lucide="bar-chart-3" class="mr-3"></i>Laporan
                    </a>
                </li>

                <li class="nav-item rounded-lg mb-2">
                    <a href="profil-admin.php" class="flex items-center p-3 rounded-lg">
                        <i data-lucide="user-cog" class="mr-3"></i>Edit Profil
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
                <i data-lucide="bar-chart-3" class="mr-3"></i>
                <h1 class="text-xl font-semibold">Laporan dan Statistik Sistem</h1>
            </div>
        </header>

        <div class="flex-1 p-8 overflow-y-auto">
            <h2 class="text-3xl font-bold text-gray-300 mb-6">Ringkasan Perpustakaan</h2>

            <!-- Statistik Card -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-10">
                <div class="bg-[#333333] p-5 rounded-xl shadow-lg border-l-4 border-purple-500">
                    <p class="text-sm text-gray-400">Total Buku</p>
                    <h3 class="text-2xl font-bold"><?php echo number_format($stats['total_books']); ?></h3>
                </div>

                <div class="bg-[#333333] p-5 rounded-xl shadow-lg border-l-4 border-blue-500">
                    <p class="text-sm text-gray-400">Pinjaman Aktif</p>
                    <h3 class="text-2xl font-bold"><?php echo number_format($stats['active_borrows']); ?></h3>
                </div>

                <div class="bg-[#333333] p-5 rounded-xl shadow-lg border-l-4 border-green-500">
                    <p class="text-sm text-gray-400">Total Anggota</p>
                    <h3 class="text-2xl font-bold"><?php echo number_format($stats['total_users']); ?></h3>
                </div>

                <div class="bg-[#333333] p-5 rounded-xl shadow-lg border-l-4 border-red-500">
                    <p class="text-sm text-gray-400">Denda Belum Dibayar</p>
                    <h3 class="text-2xl font-bold text-red-400">Rp. <?php echo number_format($stats['unpaid_fines'], 0, ',', '.'); ?></h3>
                </div>
            </div>

            <!-- Overdue Table -->
            <div class="bg-[#333333] p-6 rounded-xl shadow-lg">
                <h3 class="text-xl font-bold text-red-400 mb-4 border-b border-gray-600 pb-2 flex items-center">
                    <i data-lucide="alert-triangle" class="w-5 h-5 mr-2"></i> 
                    Pinjaman Lewat Jatuh Tempo (Overdue)
                </h3>

                <?php if (!empty($overdue_borrows)): ?>
                    <div class="overflow-x-auto">
                        <table class="table-auto min-w-full text-sm bg-[#333333] border-collapse">
                            <thead>
                                <tr class="text-gray-400 border-b border-gray-600 uppercase text-xs tracking-wider">
                                    <th class="py-3 px-4 font-medium">Buku</th>
                                    <th class="py-3 px-4 font-medium">Peminjam</th>
                                    <th class="py-3 px-4 font-medium">Jatuh Tempo</th>
                                    <th class="py-3 px-4 font-medium">Hari Terlambat</th>
                                    <th class="py-3 px-4 font-medium">Denda Estimasi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($overdue_borrows as $b): ?>
                                    <?php 
                                        $due_date = new DateTime($b['due_date']);
                                        $current_date = new DateTime();
                                        $days_overdue = $current_date->diff($due_date)->days;
                                        $estimated_fine = $days_overdue * $fine_per_day;
                                    ?>
                                    <tr class="border-b border-gray-700 hover:bg-[#4F4F4F]">
                                        <td class="py-3 px-4 font-semibold"><?php echo htmlspecialchars($b['title']); ?></td>
                                        <td class="py-3 px-4 text-yellow-400"><?php echo htmlspecialchars($b['user_name']); ?></td>
                                        <td class="py-3 px-4 text-red-400"><?php echo date('d M Y', strtotime($b['due_date'])); ?></td>
                                        <td class="py-3 px-4 text-red-400 font-bold"><?php echo $days_overdue; ?> hari</td>
                                        <td class="py-3 px-4 text-red-400 font-bold">Rp. <?php echo number_format($estimated_fine, 0, ',', '.'); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-5">
                        <i data-lucide="check-circle" class="w-8 h-8 text-green-500 mx-auto mb-2"></i>
                        <p class="text-gray-400">Tidak ada pinjaman yang melewati jatuh tempo.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
</div>

<script> lucide.createIcons(); </script>
</body>
</html>
