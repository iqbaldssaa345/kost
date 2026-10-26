<?php
/**
 * Halaman Registrasi Akun Pengunjung / Pencari Kost
 * Sistem Informasi Manajemen Kost Lengkap
 */
require_once __DIR__ . '/koneksi.php';
require_once __DIR__ . '/config/helper.php';

$profil = get_profil_kost($conn);
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_lengkap = isset($_POST['nama_lengkap']) ? trim($_POST['nama_lengkap']) : '';
    $username     = isset($_POST['username']) ? trim(strtolower($_POST['username'])) : '';
    $email        = isset($_POST['email']) ? trim(strtolower($_POST['email'])) : '';
    $no_hp        = isset($_POST['no_hp']) ? trim($_POST['no_hp']) : '';
    $pekerjaan    = isset($_POST['pekerjaan']) ? trim($_POST['pekerjaan']) : 'Mahasiswa / Karyawan';
    $alamat_asal  = isset($_POST['alamat_asal']) ? trim($_POST['alamat_asal']) : '';
    $password     = isset($_POST['password']) ? $_POST['password'] : '';
    $confirm_pass = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';

    // Validasi Form
    if (empty($nama_lengkap) || empty($username) || empty($email) || empty($no_hp) || empty($password)) {
        $error = "Semua kolom bertanda bintang (*) wajib diisi.";
    } elseif (!preg_match('/^[a-zA-Z0-9_]{3,30}$/', $username)) {
        $error = "Username hanya boleh huruf, angka, dan underscore (_) tanpa spasi (3-30 karakter).";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Format alamat email tidak valid.";
    } elseif (strlen($password) < 6) {
        $error = "Password minimal harus 6 karakter.";
    } elseif ($password !== $confirm_pass) {
        $error = "Konfirmasi password tidak cocok dengan password yang dimasukkan.";
    } else {
        // Cek duplikasi username atau email di database
        $stmt_cek = mysqli_prepare($conn, "SELECT id_user FROM tbl_users WHERE username = ? OR email = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt_cek, "ss", $username, $email);
        mysqli_stmt_execute($stmt_cek);
        $res_cek = mysqli_stmt_get_result($stmt_cek);

        if (mysqli_num_rows($res_cek) > 0) {
            $error = "Username atau email tersebut sudah terdaftar. Silakan gunakan yang lain atau login ke akun Anda.";
        } else {
            // Hash Password menggunakan MD5 untuk kompatibilitas penuh seed data
            $pass_hash = md5($password);

            $stmt_ins = mysqli_prepare($conn, "INSERT INTO tbl_users (username, password, nama_lengkap, email, no_hp, role, pekerjaan, alamat_asal, status_aktif) VALUES (?, ?, ?, ?, ?, 'pengunjung', ?, ?, 'aktif')");
            mysqli_stmt_bind_param($stmt_ins, "sssssss", $username, $pass_hash, $nama_lengkap, $email, $no_hp, $pekerjaan, $alamat_asal);

            if (mysqli_stmt_execute($stmt_ins)) {
                set_flash('success', "Pendaftaran akun berhasil! Silakan masuk menggunakan username '{$username}'.");
                header("Location: login.php");
                exit();
            } else {
                $error = "Terjadi kesalahan saat mendaftarkan akun: " . mysqli_error($conn);
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Akun Baru - <?= htmlspecialchars($profil['nama_kost']) ?></title>
    <link rel="stylesheet" href="assets/css/style.css?v=<?= time() ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body style="background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%); min-height: 100vh; display: flex; flex-direction: column;">

    <div class="auth-wrapper" style="flex: 1; display: flex; align-items: center; justify-content: center; padding: 40px 15px;">
        <div class="auth-card" style="width: 100%; max-width: 540px; background: #ffffff; border-radius: 16px; box-shadow: 0 10px 25px -5px rgba(15,23,42,0.1); border: 1px solid #e2e8f0; padding: 36px;">
            
            <div class="auth-header" style="text-align: center; margin-bottom: 24px;">
                <a href="index.php" style="display: inline-flex; align-items: center; gap: 8px; font-weight: 800; font-size: 1.3rem; color: var(--primary);">
                    <i class="fa-solid fa-hotel"></i> <?= htmlspecialchars($profil['nama_kost']) ?>
                </a>
                <h2 style="font-size: 1.6rem; font-weight: 800; color: #0f172a; margin-top: 10px; margin-bottom: 4px;">Pendaftaran Akun Baru</h2>
                <p style="font-size: 0.9rem; color: #64748b;">Buat akun untuk memesan kamar kost dan memantau tagihan</p>
            </div>

            <?php if (!empty($error)): ?>
                <div class="alert-box danger" style="margin-bottom: 18px;">
                    <i class="fa-solid fa-circle-exclamation"></i> <?= $error ?>
                </div>
            <?php endif; ?>

            <form action="register.php" method="POST">
                <div class="form-group" style="margin-bottom: 16px;">
                    <label for="nama_lengkap" style="font-size: 0.88rem; font-weight: 600; color: #334155; margin-bottom: 6px; display: block;">
                        <i class="fa-solid fa-id-card"></i> Nama Lengkap *
                    </label>
                    <input type="text" name="nama_lengkap" id="nama_lengkap" class="form-control" required placeholder="Contoh: Rian Pratama" value="<?= isset($_POST['nama_lengkap']) ? htmlspecialchars($_POST['nama_lengkap']) : '' ?>">
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 16px;">
                    <div class="form-group" style="margin-bottom: 0;">
                        <label for="username" style="font-size: 0.88rem; font-weight: 600; color: #334155; margin-bottom: 6px; display: block;">
                            <i class="fa-solid fa-user"></i> Username *
                        </label>
                        <input type="text" name="username" id="username" class="form-control" required placeholder="rian123" value="<?= isset($_POST['username']) ? htmlspecialchars($_POST['username']) : '' ?>">
                    </div>

                    <div class="form-group" style="margin-bottom: 0;">
                        <label for="no_hp" style="font-size: 0.88rem; font-weight: 600; color: #334155; margin-bottom: 6px; display: block;">
                            <i class="fa-brands fa-whatsapp"></i> No. WhatsApp *
                        </label>
                        <input type="tel" name="no_hp" id="no_hp" class="form-control" required placeholder="08123456789" value="<?= isset($_POST['no_hp']) ? htmlspecialchars($_POST['no_hp']) : '' ?>">
                    </div>
                </div>

                <div class="form-group" style="margin-bottom: 16px;">
                    <label for="email" style="font-size: 0.88rem; font-weight: 600; color: #334155; margin-bottom: 6px; display: block;">
                        <i class="fa-solid fa-envelope"></i> Alamat Email Aktif *
                    </label>
                    <input type="email" name="email" id="email" class="form-control" required placeholder="rian@gmail.com" value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>">
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 16px;">
                    <div class="form-group" style="margin-bottom: 0;">
                        <label for="pekerjaan" style="font-size: 0.88rem; font-weight: 600; color: #334155; margin-bottom: 6px; display: block;">
                            <i class="fa-solid fa-briefcase"></i> Pekerjaan / Profesi
                        </label>
                        <input type="text" name="pekerjaan" id="pekerjaan" class="form-control" placeholder="Mahasiswa / Karyawan" value="<?= isset($_POST['pekerjaan']) ? htmlspecialchars($_POST['pekerjaan']) : '' ?>">
                    </div>

                    <div class="form-group" style="margin-bottom: 0;">
                        <label for="alamat_asal" style="font-size: 0.88rem; font-weight: 600; color: #334155; margin-bottom: 6px; display: block;">
                            <i class="fa-solid fa-location-dot"></i> Kota Asal
                        </label>
                        <input type="text" name="alamat_asal" id="alamat_asal" class="form-control" placeholder="Yogyakarta" value="<?= isset($_POST['alamat_asal']) ? htmlspecialchars($_POST['alamat_asal']) : '' ?>">
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 22px;">
                    <div class="form-group" style="margin-bottom: 0;">
                        <label for="reg_password" style="font-size: 0.88rem; font-weight: 600; color: #334155; margin-bottom: 6px; display: block;">
                            <i class="fa-solid fa-lock"></i> Password *
                        </label>
                        <input type="password" name="password" id="reg_password" class="form-control" required placeholder="Min 6 karakter">
                    </div>

                    <div class="form-group" style="margin-bottom: 0;">
                        <label for="confirm_password" style="font-size: 0.88rem; font-weight: 600; color: #334155; margin-bottom: 6px; display: block;">
                            <i class="fa-solid fa-lock-open"></i> Ulangi Password *
                        </label>
                        <input type="password" name="confirm_password" id="confirm_password" class="form-control" required placeholder="Ulangi password">
                    </div>
                </div>

                <button type="submit" class="btn btn-primary btn-block" style="padding: 12px; font-size: 1rem; border-radius: 10px;">
                    <i class="fa-solid fa-user-plus"></i> Daftar Akun Sekarang
                </button>
            </form>

            <div style="margin-top: 22px; padding-top: 18px; border-top: 1px solid #e2e8f0; text-align: center;">
                <p style="font-size: 0.9rem; color: #475569;">
                    Sudah memiliki akun? <a href="login.php" style="color: var(--primary); font-weight: 700;">Masuk disini</a>
                </p>
                <div style="margin-top: 10px;">
                    <a href="index.php" style="font-size: 0.85rem; color: #64748b; text-decoration: none;">
                        <i class="fa-solid fa-arrow-left"></i> Kembali ke Katalog Kamar
                    </a>
                </div>
            </div>

        </div>
    </div>

</body>
</html>
