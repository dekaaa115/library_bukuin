<?php
// profil-user.php
session_start();
require_once "../php/config.php";

// Cek login
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../login.php");
    exit;
}

$user_id = $_SESSION['id'];
$user_data = [];

// Ambil data pengguna saat ini
$sql = "SELECT id, full_name, nickname, phone_number, address, profile_image_url, role, kelas, gender FROM users WHERE id = ?";
if ($stmt = mysqli_prepare($link, $sql)) {
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    if (mysqli_stmt_execute($stmt)) {
        $result = mysqli_stmt_get_result($stmt);
        if (mysqli_num_rows($result) == 1) {
            $user_data = mysqli_fetch_assoc($result);
        } else {
            // Pengguna tidak ditemukan, kemungkinan data session rusak
            header("location: ../php/logout.php");
            exit;
        }
    }
    mysqli_stmt_close($stmt);
}

// Ambil pesan dari URL (jika ada)
$status_message = '';
$message_type = '';
if (isset($_GET['status']) && $_GET['status'] == 'success') {
    $status_message = "Profil berhasil diperbarui!";
    $message_type = 'success';
}
if (isset($_GET['error'])) {
    $status_message = "Error: " . htmlspecialchars($_GET['error']);
    $message_type = 'error';
}

// Data default untuk input
$full_name = htmlspecialchars($user_data['full_name'] ?? '');
$nickname = htmlspecialchars($user_data['nickname'] ?? '');
$phone_number = htmlspecialchars($user_data['phone_number'] ?? '');
$address = htmlspecialchars($user_data['address'] ?? '');
$kelas = htmlspecialchars($user_data['kelas'] ?? '');
$role = htmlspecialchars($user_data['role'] ?? 'user');
$gender = htmlspecialchars($user_data['gender'] ?? '');
// Pastikan URL gambar profil dari session juga digunakan di sini
$profile_image_url = htmlspecialchars($_SESSION['profile_image_url'] ?? 'https://placehold.co/100x100/A78BFA/FFFFFF?text=' . substr($user_data['full_name'], 0, 1));
// Jika URL profil_image_url di session belum update, kita gunakan dari database
if (!empty($user_data['profile_image_url'])) {
    $profile_image_url = htmlspecialchars($user_data['profile_image_url']);
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profil - Buku in</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="bg-[#212121] text-white">

    <div class="flex h-screen bg-[#212121] text-white">
        <!-- Sidebar Navigation -->
        <nav class="w-64 bg-[#333333] p-6 flex flex-col justify-between">
            <div>
                <div class="flex flex-col items-center mb-10">
                    <!-- FOTO PROFIL DI SIDEBAR -->
                    <img src="../<?php echo $profile_image_url; ?>" onerror="this.onerror=null; this.src='https://placehold.co/100x100/A78BFA/FFFFFF?text=<?php echo substr($full_name, 0, 1); ?>';" alt="User Profile" class="rounded-full w-24 h-24 mb-4 border-2 border-purple-400 object-cover">
                    <h3 class="font-bold text-lg"><?php echo $full_name; ?></h3>
                    <p class="text-sm bg-blue-500 px-3 py-1 rounded-full mt-2"><?php echo ucfirst($role); ?></p>
                </div>
                <ul>
                    <li class="nav-item rounded-lg mb-2"><a href="daftar-buku.php" class="flex items-center p-3 rounded-lg"><i data-lucide="book-open" class="mr-3"></i>Daftar Buku</a></li>
                    <li class="nav-item rounded-lg mb-2 active-nav"><a href="profil-user.php" class="flex items-center p-3 rounded-lg"><i data-lucide="file-pen-line" class="mr-3"></i>Edit Profil</a></li>
                    <li class="nav-item rounded-lg mb-2"><a href="transaksi.php" class="flex items-center p-3 rounded-lg"><i data-lucide="qr-code" class="mr-3"></i>Transaksi</a></li>
                    <li class="nav-item rounded-lg mb-2"><a href="payment.php" class="flex items-center p-3 rounded-lg"><i data-lucide="wallet" class="mr-3"></i>Pembayaran</a></li>
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
                    <i data-lucide="file-pen-line" class="mr-3"></i>
                    <h1 class="text-xl font-semibold">Buku in - Edit Profil</h1>
                </div>
            </header>

            <div class="flex-1 p-8 overflow-y-auto">
                <h2 class="text-3xl font-bold text-gray-300 mb-6">Edit Data Profil</h2>
                
                <div class="bg-[#333333] p-8 rounded-xl shadow-lg max-w-3xl mx-auto">
                    
                    <?php if ($status_message): ?>
                        <div class="p-4 rounded-lg mb-6 flex items-center 
                            <?php echo $message_type == 'success' ? 'bg-green-600/30 text-green-300 border border-green-700' : 'bg-red-600/30 text-red-300 border border-red-700'; ?>">
                            <i data-lucide="<?php echo $message_type == 'success' ? 'check-circle' : 'alert-triangle'; ?>" class="w-5 h-5 mr-3"></i>
                            <span><?php echo $status_message; ?></span>
                        </div>
                    <?php endif; ?>

                    <!-- FORM DENGAN ENCTYPE UNTUK UPLOAD FILE -->
                    <form action="../php/update_profile_process.php" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="existing_image_url" value="<?php echo $profile_image_url; ?>">

                        <!-- Foto Profil dan Info Dasar -->
                        <div class="flex flex-col items-center mb-8 pb-6 border-b border-gray-600">
                            <!-- FOTO UTAMA DI TENGAH -->
                            <img src="../<?php echo $profile_image_url; ?>" onerror="this.onerror=null; this.src='https://placehold.co/100x100/A78BFA/FFFFFF?text=<?php echo substr($full_name, 0, 1); ?>'" alt="Current Profile" class="rounded-full w-32 h-32 mb-4 border-4 border-purple-500 object-cover">
                            
                            <label for="profile_image" class="cursor-pointer bg-purple-600 hover:bg-purple-700 text-white text-sm font-medium py-2 px-4 rounded-full transition-colors flex items-center">
                                <i data-lucide="image" class="w-4 h-4 mr-2"></i> Ganti Foto Profil
                            </label>
                            <input type="file" id="profile_image" name="profile_image" class="hidden">
                            <p class="text-xs text-gray-500 mt-2">Max 2MB. Format: JPG, PNG.</p>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                            
                            <!-- Full Name -->
                            <div>
                                <label for="full_name" class="block text-gray-400 mb-2 font-medium">Nama Lengkap</label>
                                <input type="text" id="full_name" name="full_name" value="<?php echo $full_name; ?>" required class="w-full bg-[#4F4F4F] text-white border border-[#4F4F4F] p-3 rounded-lg focus:ring-purple-500 focus:border-purple-500">
                            </div>

                            <!-- Username -->
                            <div>
                                <label for="nickname" class="block text-gray-400 mb-2 font-medium">Username</label>
                                <input type="text" id="nickname" name="nickname" value="<?php echo $nickname; ?>" required class="w-full bg-[#4F4F4F] text-white border border-[#4F4F4F] p-3 rounded-lg focus:ring-purple-500 focus:border-purple-500">
                            </div>

                            <!-- Gender -->
                            <div>
                                <label for="gender" class="block text-gray-400 mb-2 font-medium">Gender</label>
                                <select id="gender" name="gender" class="w-full bg-[#4F4F4F] text-white border border-[#4F4F4F] p-3 rounded-lg focus:ring-purple-500 focus:border-purple-500">
                                    <option value="Male" <?php echo ($gender == 'Male') ? 'selected' : ''; ?>>Male</option>
                                    <option value="Female" <?php echo ($gender == 'Female') ? 'selected' : ''; ?>>Female</option>
                                </select>
                            </div>

                            <!-- Kelas (Display only) -->
                            <div>
                                <label class="block text-gray-400 mb-2 font-medium">Kelas</label>
                                <input type="text" id="kelas" name="kelas" value="<?php echo $kelas; ?>" required class="w-full bg-[#4F4F4F] text-white border border-[#4F4F4F] p-3 rounded-lg focus:ring-purple-500 focus:border-purple-500">
                            </div>

                            <!-- Phone Number -->
                            <div>
                                <label for="phone_number" class="block text-gray-400 mb-2 font-medium">No Telepon</label>
                                <input type="text" id="phone_number" name="phone_number" value="<?php echo $phone_number; ?>" required class="w-full bg-[#4F4F4F] text-white border border-[#4F4F4F] p-3 rounded-lg focus:ring-purple-500 focus:border-purple-500">
                            </div>
                            
                            <!-- Address -->
                            <div class="md:col-span-2">
                                <label for="address" class="block text-gray-400 mb-2 font-medium">Alamat</label>
                                <textarea id="address" name="address" rows="3" required class="w-full bg-[#4F4F4F] text-white border border-[#4F4F4F] p-3 rounded-lg focus:ring-purple-500 focus:border-purple-500"><?php echo $address; ?></textarea>
                            </div>
                        </div>

                        <div class="flex justify-end">
                            <button type="submit" class="bg-purple-600 hover:bg-purple-700 text-white font-bold py-2 px-6 rounded-lg transition-colors flex items-center">
                                <i data-lucide="save" class="w-5 h-5 mr-2"></i>
                                Simpan Perubahan
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