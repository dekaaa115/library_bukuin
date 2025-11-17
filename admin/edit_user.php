<?php
// admin/edit_user.php
session_start();
require_once "../php/config.php";

// Pengecekan Admin
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== 'admin') {
    header("location: ../login.php");
    exit;
}

// =====================
// AMBIL DATA ADMIN
// =====================
$sql_admin = "SELECT full_name, profile_image_url FROM users WHERE id = ?";
if ($stmt_admin = mysqli_prepare($link, $sql_admin)) {
    mysqli_stmt_bind_param($stmt_admin, "i", $_SESSION["id"]);
    mysqli_stmt_execute($stmt_admin);
    $result_admin = mysqli_stmt_get_result($stmt_admin);

    if ($admin_data = mysqli_fetch_assoc($result_admin)) {
        $admin_name = $admin_data["full_name"] ?? "Admin";
        $admin_profile = $admin_data["profile_image_url"] ?? "uploads/default.png";
    } else {
        $admin_name = "Admin";
        $admin_profile = "uploads/default.png";
    }
    mysqli_stmt_close($stmt_admin);
}

// =====================
// AMBIL USER YANG DIEDIT
// =====================
$user_id_to_edit = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($user_id_to_edit === 0) {
    header("location: data-anggota.php");
    exit;
}

$sql = "SELECT id, full_name, nickname, email, kelas, phone_number, address, role, gender, is_verified 
        FROM users WHERE id = ?";
$user_data = [];

if ($stmt = mysqli_prepare($link, $sql)) {
    mysqli_stmt_bind_param($stmt, "i", $user_id_to_edit);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if (!$user_data = mysqli_fetch_assoc($result)) {
        header("location: data-anggota.php?error=user_not_found");
        exit;
    }
    mysqli_stmt_close($stmt);
}

// =====================
// Pesan Status
// =====================
$status_message = '';
$message_type = '';
if (isset($_GET['status']) && $_GET['status'] == 'success') {
    $status_message = "Data anggota berhasil diperbarui!";
    $message_type = 'success';
}
if (isset($_GET['error'])) {
    $status_message = "Error: " . htmlspecialchars($_GET['error']);
    $message_type = 'error';
}

// =====================
// Variabel Input
// =====================
$full_name = htmlspecialchars($user_data['full_name']);
$nickname = htmlspecialchars($user_data['nickname']);
$phone_number = htmlspecialchars($user_data['phone_number']);
$address = htmlspecialchars($user_data['address']);
$kelas = htmlspecialchars($user_data['kelas']);
$gender = htmlspecialchars($user_data['gender']);
$role = htmlspecialchars($user_data['role']);
$is_verified = $user_data['is_verified'];

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Anggota: <?php echo $full_name; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
</head>

<body class="bg-[#212121] text-white">

<div class="flex h-screen">

    <!-- SIDEBAR -->
    <nav class="w-64 bg-[#333333] p-6 flex flex-col justify-between">
        <div>
            <div class="flex flex-col items-center mb-10">

                <img src="../<?php echo $admin_profile; ?>" 
                     onerror="this.src='../uploads/default.png'"
                     class="rounded-full w-24 h-24 mb-4 border-2 border-green-500 object-cover">

                <h3 class="font-bold text-lg"><?php echo $admin_name; ?></h3>
                <p class="text-sm bg-green-500 px-3 py-1 rounded-full mt-2">Administrator</p>
            </div>

            <!-- MENU -->
            <ul>
                <li class="nav-item rounded-lg mb-2">
                    <a href="data-buku.php" class="flex items-center p-3 rounded-lg">
                        <i data-lucide="book-copy" class="mr-3"></i> Data Buku
                    </a>
                </li>

                <li class="nav-item rounded-lg mb-2">
                    <a href="data-anggota.php" class="flex items-center p-3 rounded-lg active-nav bg-[#A78BFA] text-black">
                        <i data-lucide="users" class="mr-3"></i> Data Anggota
                    </a>
                </li>

                <li class="nav-item rounded-lg mb-2">
                    <a href="transaksi.php" class="flex items-center p-3 rounded-lg">
                        <i data-lucide="arrow-right-left" class="mr-3"></i> Transaksi
                    </a>
                </li>

                <li class="nav-item rounded-lg mb-2">
                    <a href="laporan.php" class="flex items-center p-3 rounded-lg">
                        <i data-lucide="clipboard-list" class="mr-3"></i> Laporan
                    </a>
                </li>

                <li class="nav-item rounded-lg mb-2">
                    <a href="profil-admin.php" class="flex items-center p-3 rounded-lg">
                        <i data-lucide="user-cog" class="mr-3"></i> Edit Profil
                    </a>
                </li>
            </ul>
        </div>

        <div>
            <a href="../php/logout.php" class="flex items-center p-3 rounded-lg nav-item">
                <i data-lucide="log-out" class="mr-3"></i> Logout
            </a>
        </div>
    </nav>

    <!-- MAIN -->
    <main class="flex-1 flex flex-col">

        <!-- HEADER -->
        <header class="bg-[#A78BFA] text-black p-4 flex justify-between items-center shadow-md">
            <div class="flex items-center">
                <i data-lucide="user-cog" class="mr-3"></i>
                <h1 class="text-xl font-semibold">Edit Data Anggota</h1>
            </div>

            <a href="profil-admin.php">
                <img src="../<?php echo $admin_profile; ?>" class="w-10 h-10 rounded-full object-cover border">
            </a>
        </header>

        <!-- CONTENT -->
        <div class="flex-1 p-8 overflow-y-auto">

            <h2 class="text-3xl font-bold text-gray-300 mb-6">Mengedit: <?php echo $full_name; ?></h2>

            <div class="bg-[#333333] p-8 rounded-xl shadow-lg max-w-4xl mx-auto">

                <?php if ($status_message): ?>
                    <div class="p-4 rounded-lg mb-6 flex items-center 
                    <?php echo $message_type == 'success' ? 'bg-green-600/30 text-green-300 border border-green-700' : 'bg-red-600/30 text-red-300 border border-red-700'; ?>">
                        <i data-lucide="<?php echo $message_type == 'success' ? 'check-circle' : 'alert-triangle'; ?>" class="w-5 h-5 mr-3"></i>
                        <span><?php echo $status_message; ?></span>
                    </div>
                <?php endif; ?>

                <form action="../php/edit_user_process.php" method="POST">
                    <input type="hidden" name="user_id" value="<?php echo $user_id_to_edit; ?>">

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                        <div>
                            <label class="block mb-2 text-gray-400">Nama Lengkap</label>
                            <input type="text" name="full_name" value="<?php echo $full_name; ?>" 
                                   class="w-full bg-[#4F4F4F] text-white p-3 rounded-lg border border-[#4F4F4F] focus:border-[#A78BFA]">
                        </div>

                        <div>
                            <label class="block mb-2 text-gray-400">Nickname</label>
                            <input type="text" name="nickname" value="<?php echo $nickname; ?>" 
                                   class="w-full bg-[#4F4F4F] text-white p-3 rounded-lg border border-[#4F4F4F] focus:border-[#A78BFA]">
                        </div>

                        <div>
                            <label class="block mb-2 text-gray-400">Kelas</label>
                            <input type="text" name="kelas" value="<?php echo $kelas; ?>" 
                                   class="w-full bg-[#4F4F4F] text-white p-3 rounded-lg border border-[#4F4F4F] focus:border-[#A78BFA]">
                        </div>

                        <div>
                            <label class="block mb-2 text-gray-400">Gender</label>
                            <select name="gender" class="w-full bg-[#4F4F4F] text-white p-3 rounded-lg border border-[#4F4F4F] focus:border-[#A78BFA]">
                                <option value="Male" <?php echo ($gender == 'Male') ? 'selected' : ''; ?>>Male</option>
                                <option value="Female" <?php echo ($gender == 'Female') ? 'selected' : ''; ?>>Female</option>
                            </select>
                        </div>

                        <div>
                            <label class="block mb-2 text-gray-400">No Telepon</label>
                            <input type="text" name="phone_number" value="<?php echo $phone_number; ?>"
                                   class="w-full bg-[#4F4F4F] text-white p-3 rounded-lg border border-[#4F4F4F] focus:border-[#A78BFA]">
                        </div>

                        <div>
                            <label class="block mb-2 text-gray-400">Status Verifikasi</label>
                            <select name="is_verified" class="w-full bg-[#4F4F4F] text-white p-3 rounded-lg border border-[#4F4F4F] focus:border-[#A78BFA]">
                                <option value="1" <?php echo ($is_verified == 1) ? 'selected' : ''; ?>>Verified</option>
                                <option value="0" <?php echo ($is_verified == 0) ? 'selected' : ''; ?>>Unverified</option>
                            </select>
                        </div>

                        <div class="md:col-span-2">
                            <label class="block mb-2 text-gray-400">Alamat</label>
                            <textarea name="address" class="w-full bg-[#4F4F4F] text-white p-3 rounded-lg border border-[#4F4F4F] focus:border-[#A78BFA]"><?php echo $address; ?></textarea>
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 mt-6">
                        <a href="data-anggota.php" class="bg-gray-600 px-4 py-2 rounded-lg">Batal</a>
                        <button class="bg-[#A78BFA] text-black px-4 py-2 rounded-lg font-semibold">
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
