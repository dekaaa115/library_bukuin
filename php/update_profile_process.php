<?php
// update_profile_process.php
session_start();
require_once "config.php";

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../login.php");
    exit;
}

// Cek apakah metode request adalah POST dan form dikirim
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $id = $_SESSION["id"]; // ID pengguna yang sedang login
    
    // Ambil data dari form
    $full_name      = trim($_POST['full_name']);
    $nickname       = trim($_POST['nickname']);
    $gender         = trim($_POST['gender']);
    $kelas          = trim($_POST['kelas']);
    $phone_number   = trim($_POST['phone_number']);
    $address        = trim($_POST['address']);
    $existing_image = $_POST['existing_image_url']; // Hidden field dari form

    $update_parts = [];
    $bind_types = "";
    $bind_params = [];
    $error = '';
    $new_image_url = null;

    // 1. LOGIKA UPLOAD FOTO PROFIL
    // ... [Logic upload foto sama] ...
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0) {
        
        // --- Setting Upload ---
        $target_dir = "../uploads/profiles/";
        if (!is_dir($target_dir)) { mkdir($target_dir, 0777, true); }

        $file_extension = pathinfo($_FILES["profile_image"]["name"], PATHINFO_EXTENSION);
        $new_file_name = "profile_" . $id . "_" . time() . "." . $file_extension;
        $target_file = $target_dir . $new_file_name;
        
        // Coba upload file
        if (move_uploaded_file($_FILES["profile_image"]["tmp_name"], $target_file)) {
            $new_image_url = "uploads/profiles/" . $new_file_name; 
            
            // Siapkan query untuk update image
            $update_parts[] = "profile_image_url = ?";
            $bind_types .= "s";
            $bind_params[] = &$new_image_url;

            // Hapus gambar lama (jika bukan placeholder)
            if ($existing_image && strpos($existing_image, 'placehold.co') === false && file_exists("../" . $existing_image)) {
                unlink("../" . $existing_image);
            }
        } else {
            $error = "Gagal mengunggah foto profil.";
        }
    }

    // 2. Cek apakah username sudah digunakan oleh orang lain
    if (empty($error)) {
        $sql_check_nick = "SELECT id FROM users WHERE nickname = ? AND id != ?";
        if ($stmt_check = mysqli_prepare($link, $sql_check_nick)) {
            mysqli_stmt_bind_param($stmt_check, "si", $nickname, $id);
            mysqli_stmt_execute($stmt_check);
            mysqli_stmt_store_result($stmt_check);
            if (mysqli_stmt_num_rows($stmt_check) > 0) {
                $error = "Username sudah digunakan oleh pengguna lain.";
            }
            mysqli_stmt_close($stmt_check);
        }
    }

    // 3. Update data utama
    if (empty($error)) {
        $update_parts[] = "full_name = ?"; $bind_types .= "s"; $bind_params[] = &$full_name;
        $update_parts[] = "nickname = ?"; $bind_types .= "s"; $bind_params[] = &$nickname;
        $update_parts[] = "gender = ?"; $bind_types .= "s"; $bind_params[] = &$gender;
        $update_parts[] = "kelas = ?"; $bind_types .= "s"; $bind_params[] = &$kelas;
        $update_parts[] = "phone_number = ?"; $bind_types .= "s"; $bind_params[] = &$phone_number;
        $update_parts[] = "address = ?"; $bind_types .= "s"; $bind_params[] = &$address;
        
        // === FIX KRITIS UNTUK ADMIN ROLE ===
        // Karena form Admin tidak mengirimkan kolom 'role', dan user tidak boleh mengubah role, 
        // kita ambil role dari session dan simpan kembali.
        $role_to_save = $_SESSION['role']; 
        $update_parts[] = "role = ?"; $bind_types .= "s"; $bind_params[] = &$role_to_save;
        // ===================================
        
        if (empty($update_parts)) {
            header("Location: ../user/profil-user.php?status=no_changes");
            exit;
        }

        $sql_update = "UPDATE users SET " . implode(', ', $update_parts) . " WHERE id = ?";
        $bind_types .= "i";
        $bind_params[] = &$id;

        if ($stmt = mysqli_prepare($link, $sql_update)) {
            
            // Bind parameter secara dinamis
            $bind_param_method = new ReflectionMethod('mysqli_stmt', 'bind_param');
            $bind_param_method->invokeArgs($stmt, array_merge([$bind_types], $bind_params));

            if (mysqli_stmt_execute($stmt)) {
                // Update Session data (WAJIB agar sidebar langsung berubah)
                $_SESSION['full_name'] = $full_name;
                // Update foto profil di session hanya jika ada upload baru
                if ($new_image_url) {
                    $_SESSION['profile_image_url'] = $new_image_url;
                }
                
                // Redirect ke halaman Admin jika Admin yang sedang login
                if ($_SESSION['role'] == 'admin') {
                     header("Location: ../admin/profil-admin.php?status=success");
                } else {
                     header("Location: ../user/profil-user.php?status=success");
                }
            } else {
                $error = "Gagal memperbarui data: " . mysqli_error($link);
            }
            mysqli_stmt_close($stmt);
        } else {
            $error = "Gagal menyiapkan query update.";
        }
    }

    if (!empty($error)) {
        // Redirect berdasarkan role
        if ($_SESSION['role'] == 'admin') {
            header("Location: ../admin/profil-admin.php?error=" . urlencode($error));
        } else {
            header("Location: ../user/profil-user.php?error=" . urlencode($error));
        }
    }
    exit;
}

// Jika diakses tanpa POST, kembalikan ke halaman profil
if ($_SESSION['role'] == 'admin') {
    header("Location: ../admin/profil-admin.php");
} else {
    header("Location: ../user/profil-user.php");
}
exit;
?>