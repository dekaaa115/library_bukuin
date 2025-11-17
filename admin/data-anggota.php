<?php
// Initialize the session
session_start();

// Include database configuration
require_once "../php/config.php";

// Check if the user is logged in and is an admin, otherwise redirect
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== 'admin') {
    header("location: ../login.php");
    exit;
}

// Tentukan halaman aktif
$current_page = basename($_SERVER['PHP_SELF']);

// ---------------------------------------------
// AMBIL DATA PROFIL ADMIN UNTUK SIDEBAR & HEADER
// ---------------------------------------------
$user_id = $_SESSION["id"];
$sql_admin = "SELECT full_name, profile_image_url FROM users WHERE id = ?";
$stmt_admin = mysqli_prepare($link, $sql_admin);
mysqli_stmt_bind_param($stmt_admin, "i", $user_id);
mysqli_stmt_execute($stmt_admin);
$result_admin = mysqli_stmt_get_result($stmt_admin);
$admin_data = mysqli_fetch_assoc($result_admin);

$full_name = $admin_data["full_name"] ?? "Admin";
$profile_image_url = $admin_data["profile_image_url"] ?? "uploads/default.png";

// ---------------------------------------------
// AMBIL DATA ANGGOTA
// ---------------------------------------------
$users = [];
$sql = "SELECT id, full_name, kelas, address, phone_number FROM users WHERE role='user' ORDER BY id ASC";

if ($result = mysqli_query($link, $sql)) {
    while ($row = mysqli_fetch_assoc($result)) {
        $users[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Anggota - Admin Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
</head>

<body class="bg-[#212121] text-white">

<div class="flex h-screen">

    <!-- SIDEBAR PREMIUM -->
    <nav class="w-64 bg-[#333333] p-6 flex flex-col justify-between">

        <div>
            <div class="flex flex-col items-center mb-10">
                <img 
                    src="../<?php echo $profile_image_url; ?>"
                    onerror="this.onerror=null; this.src='https://placehold.co/100x100/A78BFA/FFFFFF?text=A';"
                    alt="Admin Profile"
                    class="rounded-full w-24 h-24 mb-4 border-2 border-green-500 object-cover"
                >

                <h3 class="font-bold text-lg"><?php echo htmlspecialchars($full_name); ?></h3>

                <p class="text-sm bg-green-500 px-3 py-1 rounded-full mt-2">
                    Administrator
                </p>
            </div>

            <ul>
                <li class="rounded-lg mb-2 <?php echo ($current_page == 'data-buku.php') ? 'bg-[#A78BFA] text-black' : 'hover:bg-[#4F4F4F]'; ?>">
                    <a href="data-buku.php" class="flex items-center p-3 rounded-lg">
                        <i data-lucide="book-open" class="mr-3"></i>Data Buku
                    </a>
                </li>

                <li class="rounded-lg mb-2 <?php echo ($current_page == 'data-anggota.php') ? 'bg-[#A78BFA] text-black' : 'hover:bg-[#4F4F4F]'; ?>">
                    <a href="data-anggota.php" class="flex items-center p-3 rounded-lg">
                        <i data-lucide="users" class="mr-3"></i>Data Anggota
                    </a>
                </li>

                <li class="rounded-lg mb-2 <?php echo ($current_page == 'transaksi.php') ? 'bg-[#A78BFA] text-black' : 'hover:bg-[#4F4F4F]'; ?>">
                    <a href="transaksi.php" class="flex items-center p-3 rounded-lg">
                        <i data-lucide="clipboard-list" class="mr-3"></i>Transaksi
                    </a>
                </li>

                <li class="rounded-lg mb-2 <?php echo ($current_page == 'laporan.php') ? 'bg-[#A78BFA] text-black' : 'hover:bg-[#4F4F4F]'; ?>">
                    <a href="laporan.php" class="flex items-center p-3 rounded-lg">
                        <i data-lucide="bar-chart-3" class="mr-3"></i>Laporan
                    </a>
                </li>

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
                <i data-lucide="users" class="mr-3"></i>
                <h1 class="text-xl font-semibold">Data Anggota</h1>
            </div>

            <div class="flex items-center gap-3">
                <span><?php echo htmlspecialchars($full_name); ?></span>

                <a href="profil-admin.php">
                    <img 
                        src="../<?php echo $profile_image_url; ?>"
                        onerror="this.onerror=null; this.src='https://placehold.co/40x40/A78BFA/FFFFFF?text=A';"
                        alt="Admin"
                        class="rounded-full w-10 h-10 object-cover border border-black cursor-pointer"
                    >
                </a>
            </div>
        </header>

        <!-- Content -->
        <div class="flex-1 p-8 overflow-y-auto">
            <h2 class="text-3xl font-bold text-gray-300 mb-6">Daftar Anggota</h2>

            <div class="bg-[#333333] p-6 rounded-xl shadow-lg">
                <div class="overflow-x-auto">

                    <table class="w-full text-left text-gray-300 table-auto">
                        <thead>
                            <tr class="border-b border-gray-600 text-gray-400 uppercase text-xs tracking-wider">
                                <th class="p-4">No</th>
                                <th class="p-4">Nama Anggota</th>
                                <th class="p-4">Kelas</th>
                                <th class="p-4">Alamat</th>
                                <th class="p-4">No Telepon</th>
                                <th class="p-4 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>

                        <?php if (!empty($users)): ?>
                            <?php foreach ($users as $i => $user): ?>
                                <tr class="border-b border-gray-700 hover:bg-[#4F4F4F]">
                                    <td class="p-4"><?php echo $i + 1; ?></td>

                                    <td class="p-4 font-semibold text-white">
                                        <?php echo htmlspecialchars($user['full_name']); ?>
                                    </td>

                                    <td class="p-4">
                                        <?php echo htmlspecialchars($user['kelas']); ?>
                                    </td>

                                    <td class="p-4">
                                        <?php echo htmlspecialchars($user['address']); ?>
                                    </td>

                                    <td class="p-4">
                                        <?php echo htmlspecialchars($user['phone_number']); ?>
                                    </td>

                                    <td class="p-4 flex gap-3 justify-center">
                                        <a href="edit_user.php?id=<?php echo $user['id']; ?>" class="text-blue-400 hover:text-blue-300">
                                            <i data-lucide="edit" class="w-5 h-5"></i>
                                        </a>

                                        <a href="../php/delete_user_process.php?id=<?php echo $user['id']; ?>"
                                           onclick="return confirm('Yakin ingin menghapus pengguna ini?')"
                                           class="text-red-500 hover:text-red-400">
                                            <i data-lucide="trash-2" class="w-5 h-5"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>

                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="p-4 text-center text-gray-400">
                                    Tidak ada data anggota.
                                </td>
                            </tr>
                        <?php endif; ?>

                        </tbody>
                    </table>

                </div>
            </div>

        </div>
    </main>

</div>

<script>
    lucide.createIcons();
</script>

</body>
</html>
