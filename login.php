<?php
/**
 * Halaman Login Terpadu (Admin, Petugas, Pengunjung)
 * Sistem Informasi Manajemen Kost Lengkap
 */
require_once __DIR__ . '/koneksi.php';
require_once __DIR__ . '/config/helper.php';

$profil = get_profil_kost($conn);
$error = '';
$redirect = isset($_GET['redirect']) ? trim($_GET['redirect']) : '';

// Jika pengguna sudah login, arahkan langsung ke portalnya
if (isset($_SESSION['user_id']) && isset($_SESSION['role'])) {
    if ($_SESSION['role'] === 'admin') {
        header("Location: admin/index.php");
    } elseif ($_SESSION['role'] === 'petugas') {
        header("Location: petugas/index.php");
    } else {
        header("Location: user/index.php");
    }
    exit();
}

// Tangani Pesan Parameter URL
if (isset($_GET['msg'])) {
    $msg = $_GET['msg'];
    if ($msg === 'silakan_login') {
        $error = "Silakan login terlebih dahulu untuk mengakses halaman tersebut.";
    } elseif ($msg === 'akses_ditolak') {
        $error = "Akses ditolak! Akun Anda tidak memiliki izin untuk halaman tersebut.";
    } elseif ($msg === 'harus_login_untuk_booking') {
        $error = "Silakan masuk ke akun Anda terlebih dahulu untuk memesan kamar kost.";
    } elseif ($msg === 'anda_telah_logout') {
        set_flash('success', 'Anda telah berhasil keluar dari sistem.');
    }
}

// Proses Submit Form Login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username_input = isset($_POST['username']) ? trim($_POST['username']) : '';
    $password_input = isset($_POST['password']) ? trim($_POST['password']) : '';

    if (empty($username_input) || empty($password_input)) {
        $error = "Username/Email dan Password wajib diisi.";
    } else {
        $username_clean = mysqli_real_escape_string($conn, $username_input);

        // Cari data pengguna di database
        $stmt = mysqli_prepare($conn, "SELECT * FROM tbl_users WHERE (username = ? OR email = ?) LIMIT 1");
        mysqli_stmt_bind_param($stmt, "ss", $username_clean, $username_clean);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if ($user = mysqli_fetch_assoc($result)) {
            // Verifikasi Password (Mendukung MD5, BCRYPT password_verify, atau Plain Text aman)
            $password_cocok = false;
            $db_pass = $user['password'];

            if (md5($password_input) === $db_pass) {
                $password_cocok = true;
            } elseif (password_verify($password_input, $db_pass)) {
                $password_cocok = true;
            } elseif ($password_input === $db_pass) {
                $password_cocok = true;
            }

            if ($password_cocok) {
                if ($user['status_aktif'] !== 'aktif') {
                    $error = "Akun Anda saat ini dinonaktifkan. Silakan hubungi pengelola kost.";
                } else {
                    // Set Data Sesi Login
                    $_SESSION['user_id'] = (int)$user['id_user'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['nama_lengkap'] = $user['nama_lengkap'];
                    $_SESSION['role'] = $user['role'];
                    $_SESSION['foto'] = $user['foto'];

                    // Arahkan ke URL redirect atau Dashboard sesuai Role
                    if (!empty($redirect) && strpos($redirect, 'http') === false) {
                        header("Location: " . $redirect);
                    } else {
                        if ($user['role'] === 'admin') {
                            header("Location: admin/index.php");
                        } elseif ($user['role'] === 'petugas') {
                            header("Location: petugas/index.php");
                        } else {
                            header("Location: user/index.php");
                        }
                    }
                    exit();
                }
            } else {
                $error = "Password yang Anda masukkan salah. Silakan coba lagi.";
            }
        } else {
            $error = "Username atau Email tersebut tidak terdaftar.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Pengguna - <?= htmlspecialchars($profil['nama_kost']) ?></title>
    <link rel="stylesheet" href="assets/css/style.css?v=<?= time() ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body style="background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%); min-height: 100vh; display: flex; flex-direction: column;">

    <div class="auth-wrapper" style="flex: 1; display: flex; align-items: center; justify-content: center; padding: 40px 15px;">
        <div class="auth-card" style="width: 100%; max-width: 480px; background: #ffffff; border-radius: 16px; box-shadow: 0 10px 25px -5px rgba(15,23,42,0.1); border: 1px solid #e2e8f0; padding: 36px;">
            
            <div class="auth-header" style="text-align: center; margin-bottom: 24px;">
                <a href="index.php" style="display: inline-flex; align-items: center; gap: 8px; font-weight: 800; font-size: 1.3rem; color: var(--primary);">
                    <i class="fa-solid fa-hotel"></i> <?= htmlspecialchars($profil['nama_kost']) ?>
                </a>
                <h2 style="font-size: 1.6rem; font-weight: 800; color: #0f172a; margin-top: 10px; margin-bottom: 4px;">Selamat Datang</h2>
                <p style="font-size: 0.9rem; color: #64748b;">Masuk ke akun Anda untuk mengelola sewa & tagihan kost</p>
            </div>

            <?php if (!empty($error)): ?>
                <div class="alert-box danger" style="margin-bottom: 18px;">
                    <i class="fa-solid fa-circle-exclamation"></i> <?= $error ?>
                </div>
            <?php endif; ?>

            <?php show_flash(); ?>

            <form action="login.php<?= !empty($redirect) ? '?redirect=' . urlencode($redirect) : '' ?>" method="POST">
                <div class="form-group" style="margin-bottom: 16px;">
                    <label for="username" style="font-size: 0.88rem; font-weight: 600; color: #334155; margin-bottom: 6px; display: block;">
                        <i class="fa-solid fa-user"></i> Username atau Email *
                    </label>
                    <input type="text" name="username" id="username" class="form-control" placeholder="Masukkan username / email" required autofocus value="<?= isset($_POST['username']) ? htmlspecialchars($_POST['username']) : '' ?>">
                </div>

                <div class="form-group" style="margin-bottom: 20px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                        <label for="password" style="font-size: 0.88rem; font-weight: 600; color: #334155;">
                            <i class="fa-solid fa-lock"></i> Password *
                        </label>
                        <span id="togglePasswordBtn" style="font-size: 0.8rem; color: var(--primary); cursor: pointer; font-weight: 600;" onclick="togglePasswordVisibility()">
                            <i class="fa-solid fa-eye" id="eyeIcon"></i> Tampilkan
                        </span>
                    </div>
                    <input type="password" name="password" id="password" class="form-control" placeholder="Masukkan password" required>
                    <div style="display: flex; justify-content: flex-end; margin-top: 6px;">
                        <a href="lupa_password.php" style="font-size: 0.82rem; color: var(--primary); text-decoration: none; font-weight: 600;">
                            <i class="fa-solid fa-key" style="font-size: 0.75rem;"></i> Lupa Password?
                        </a>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary btn-block" style="padding: 12px; font-size: 1rem; border-radius: 10px;">
                    <i class="fa-solid fa-right-to-bracket"></i> Masuk Sekarang
                </button>
            </form>

            <div style="margin-top: 24px; padding-top: 18px; border-top: 1px solid #e2e8f0; text-align: center;">
                <p style="font-size: 0.9rem; color: #475569;">
                    Belum punya akun? <a href="register.php" style="color: var(--primary); font-weight: 700;">Daftar Akun Baru</a>
                </p>
                <div style="margin-top: 10px;">
                    <a href="index.php" style="font-size: 0.85rem; color: #64748b; text-decoration: none;">
                        <i class="fa-solid fa-arrow-left"></i> Kembali ke Katalog Kamar
                    </a>
                </div>
            </div>

        </div>
    </div>

    <script>
    function togglePasswordVisibility() {
        const passInput = document.getElementById('password');
        const eyeIcon = document.getElementById('eyeIcon');
        const toggleText = document.getElementById('togglePasswordBtn');

        if (passInput.type === 'password') {
            passInput.type = 'text';
            eyeIcon.className = 'fa-solid fa-eye-slash';
            toggleText.innerHTML = '<i class="fa-solid fa-eye-slash"></i> Sembunyikan';
        } else {
            passInput.type = 'password';
            eyeIcon.className = 'fa-solid fa-eye';
            toggleText.innerHTML = '<i class="fa-solid fa-eye"></i> Tampilkan';
        }
    }
    </script>
</body>
</html>
