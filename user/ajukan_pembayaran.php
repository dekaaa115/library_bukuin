<?php
// ajukan_pembayaran.php
session_start();
require_once "../php/config.php";

// Cek login
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../login.php");
    exit;
}

$user_id = $_SESSION['id'];
$unpaid_fines = [];

// Query: Ambil semua transaksi dengan denda yang Belum Dibayar ATAU status 'Diajukan'
$sql = "
    SELECT 
        t.id AS transaction_id, 
        t.fine_amount, 
        b.title
    FROM 
        transactions t
    INNER JOIN 
        books b ON t.book_id = b.id
    WHERE 
        t.user_id = ? AND t.fine_amount > 0 AND (t.payment_status = 'Belum Dibayar' OR t.payment_status = 'Diajukan')
    ORDER BY 
        t.fine_amount DESC";

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

// Kumpulkan semua ID Transaksi untuk dikirim ke proses
$transaction_ids = implode(',', array_column($unpaid_fines, 'transaction_id'));

// Jika tidak ada denda, kembalikan
if ($total_fine <= 0) {
    header("location: payment.php");
    exit;
}

// Informasi Pembayaran (Data Admin/Perpustakaan)
$payment_info = [
    'bank' => [
        ['name' => 'Bank Mandiri', 'account' => '137-00-1234567-8', 'holder' => 'Perpus Buku In'],
        ['name' => 'Bank BRI', 'account' => '002-123-45678-9', 'holder' => 'Perpus Buku In'],
    ],
    'ewallet' => [
        ['name' => 'DANA', 'number' => '0812-3456-7890'],
        ['name' => 'GoPay', 'number' => '0812-3456-7890'],
        ['name' => 'QRIS Code', 'number' => 'Scan Code Below'],
    ],
];

// Placeholder untuk QRIS Code
$qris_placeholder = "https://placehold.co/300x300/A78BFA/FFFFFF?text=QRIS+TOTAL+DENDA"; 

// File proses untuk menerima bukti pembayaran
$process_file = "../php/payment_update_process.php"; 
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ajukan Pembayaran Denda</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body class="bg-[#212121] text-white">

    <div class="flex h-screen bg-[#212121] text-white">
        <!-- Sidebar Navigation -->
        <nav class="w-64 bg-[#333333] p-6 flex flex-col justify-between">
            <a href="payment.php" class="text-white hover:text-purple-400 mb-4 flex items-center">
                 <i data-lucide="arrow-left" class="mr-2"></i> Kembali ke Daftar Denda
            </a>
            <!-- ... [Sidebar content] ... -->
        </nav>
        
        <!-- Main Content -->
        <main class="flex-1 flex flex-col">
            <header class="bg-[#A78BFA] text-black p-4 flex justify-between items-center shadow-md">
                <div class="flex items-center">
                    <i data-lucide="wallet" class="mr-3"></i>
                    <h1 class="text-xl font-semibold">Ajukan Pembayaran Denda</h1>
                </div>
            </header>

            <div class="flex-1 p-8 overflow-y-auto">
                <div class="bg-[#333333] p-8 rounded-xl shadow-lg max-w-4xl mx-auto">
                    <h2 class="text-3xl font-bold mb-4 text-center">Total Pembayaran:</h2>
                    
                    <!-- Total Denda Box -->
                    <div class="bg-red-900/30 p-4 rounded-lg mb-6 border border-red-700 text-center">
                        <span class="text-3xl font-extrabold text-red-400">Rp. <?php echo number_format($total_fine, 0, ',', '.'); ?></span>
                        <p class="text-sm text-gray-400 mt-1">Pembayaran harus sesuai dengan jumlah ini.</p>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        
                        <!-- Kolom Kiri: Transfer Bank -->
                        <div class="lg:col-span-2 bg-[#4F4F4F] p-6 rounded-xl">
                            <h3 class="text-xl font-bold mb-4 border-b border-gray-600 pb-2 flex items-center">
                                <i data-lucide="banknote" class="w-5 h-5 mr-2"></i> Transfer Bank
                            </h3>
                            <div class="space-y-4">
                                <?php foreach ($payment_info['bank'] as $bank): ?>
                                    <div class="border border-gray-600 p-3 rounded-lg">
                                        <p class="text-sm text-gray-400"><?php echo htmlspecialchars($bank['name']); ?></p>
                                        <p class="text-lg font-semibold text-white"><?php echo htmlspecialchars($bank['account']); ?></p>
                                        <p class="text-xs text-purple-400">A/N: <?php echo htmlspecialchars($bank['holder']); ?></p>
                                    </div>
                                <?php endforeach; ?>

                            </div>
                            
                            <h3 class="text-xl font-bold mt-6 mb-4 border-b border-gray-600 pb-2 flex items-center">
                                <i data-lucide="wallet" class="w-5 h-5 mr-2"></i> E-Wallet & QRIS
                            </h3>
                            <div class="grid grid-cols-2 gap-4">
                                <?php foreach ($payment_info['ewallet'] as $wallet): ?>
                                    <div class="border border-gray-600 p-3 rounded-lg">
                                        <p class="text-sm text-gray-400"><?php echo htmlspecialchars($wallet['name']); ?></p>
                                        <p class="text-lg font-semibold text-white"><?php echo htmlspecialchars($wallet['number']); ?></p>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                        </div>
                        
                        <!-- Kolom Kanan: Upload Bukti Bayar -->
                        <div class="lg:col-span-1 bg-[#4F4F4F] p-6 rounded-xl flex flex-col justify-between">
                            <div>
                                <h3 class="text-xl font-bold mb-4 border-b border-gray-600 pb-2">Unggah Bukti Bayar</h3>
                                <div class="mb-4 text-center">
                                    <!-- QRIS Code Example -->
                                    <img src="<?php echo $qris_placeholder; ?>" alt="QRIS Code" class="w-32 h-32 mx-auto rounded-lg mb-3">
                                    <p class="text-xs text-gray-400">Gunakan QRIS untuk transfer cepat.</p>
                                </div>
                            </div>

                            <form id="uploadForm" action="<?php echo $process_file; ?>" method="POST" enctype="multipart/form-data" class="mt-4">
                                <input type="hidden" name="transaction_ids" value="<?php echo $transaction_ids; ?>">
                                <input type="hidden" name="total_fine" value="<?php echo $total_fine; ?>">

                                <div class="mb-4">
                                    <label for="proof" class="block text-sm font-medium text-gray-400 mb-2">Pilih Bukti Transfer:</label>
                                    <input type="file" id="proof" name="proof" required
                                           class="w-full bg-[#333333] text-white rounded-lg py-2 px-4 border border-gray-600 file:mr-4 file:py-1 file:px-2 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-purple-500 file:text-white hover:file:bg-purple-600">
                                </div>
                                
                                <button type="submit" id="submitButton" class="bg-purple-600 hover:bg-purple-700 text-white font-bold py-3 px-6 rounded-lg w-full transition-colors shadow-md flex items-center justify-center">
                                    <i data-lucide="upload-cloud" class="w-5 h-5 mr-2" id="buttonIcon"></i> 
                                    Ajukan Konfirmasi Lunas
                                </button>
                            </form>
                        </div>
                        
                    </div>
                </div>
            </div>
        </main>
    </div>
    <script>
        lucide.createIcons();
        
        // LOGIKA JAVASCRIPT UNTUK MENCEGAH SPAMMING
        document.getElementById('uploadForm').addEventListener('submit', function(event) {
            const button = document.getElementById('submitButton');
            const icon = document.getElementById('buttonIcon');
            
            // 1. Menonaktifkan tombol
            button.disabled = true;
            button.classList.remove('bg-purple-600', 'hover:bg-purple-700');
            button.classList.add('bg-gray-500', 'cursor-not-allowed');

            // 2. Mengubah teks dan ikon
            icon.setAttribute('data-lucide', 'loader-2');
            icon.classList.add('animate-spin');
            button.innerHTML = '<i data-lucide="loader-2" class="w-5 h-5 mr-2 animate-spin"></i> Diajukan & Memproses...';
            
            // Memastikan Lucide Icons me-render ikon baru
            lucide.createIcons();
        });
    </script>
</body>
</html>