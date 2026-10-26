<?php
/**
 * Halaman Lupa Password
 * Sistem Informasi Manajemen Kost Lengkap
 */
require_once __DIR__ . '/koneksi.php';
require_once __DIR__ . '/config/helper.php';

$profil = get_profil_kost($conn);
$error = '';
$success = false;
$reset_data = null;

// Jika pengguna sudah login, arahkan ke dashboard masing-masing
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

// Proses Permintaan Reset Password
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $akun_input = isset($_POST['akun_input']) ? trim($_POST['akun_input']) : '';

    if (empty($akun_input)) {
        $error = "Silakan masukkan alamat email atau username Anda.";
    } else {
        $akun_clean = mysqli_real_escape_string($conn, $akun_input);

        // Cari pengguna berdasarkan email atau username
        $stmt = mysqli_prepare($conn, "SELECT id_user, username, nama_lengkap, email, no_hp, status_aktif FROM tbl_users WHERE (email = ? OR username = ?) LIMIT 1");
        mysqli_stmt_bind_param($stmt, "ss", $akun_clean, $akun_clean);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if ($user = mysqli_fetch_assoc($result)) {
            if ($user['status_aktif'] !== 'aktif') {
                $error = "Akun dengan username/email tersebut saat ini sedang dinonaktifkan. Silakan hubungi pengelola kost.";
            } else {
                $user_email = $user['email'];
                $user_nama  = $user['nama_lengkap'];
                
                // Generate token unik acak dan kode OTP 6 digit
                $token = bin2hex(random_bytes(32));
                $otp   = str_pad((string)mt_rand(100000, 999999), 6, '0', STR_PAD_LEFT);
                $expires_at = date('Y-m-d H:i:s', strtotime('+30 minutes'));

                // Hapus token lama untuk email ini jika ada
                $stmt_del = mysqli_prepare($conn, "DELETE FROM tbl_password_resets WHERE email = ?");
                mysqli_stmt_bind_param($stmt_del, "s", $user_email);
                mysqli_stmt_execute($stmt_del);

                // Simpan token baru ke database
                $stmt_ins = mysqli_prepare($conn, "INSERT INTO tbl_password_resets (email, token, otp, expires_at) VALUES (?, ?, ?, ?)");
                mysqli_stmt_bind_param($stmt_ins, "ssss", $user_email, $token, $otp, $expires_at);

                if (mysqli_stmt_execute($stmt_ins)) {
                    // Siapkan URL Reset Password
                    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
                    $host = $_SERVER['HTTP_HOST'];
                    $base_dir = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
                    $reset_url = $protocol . $host . $base_dir . "/reset_password.php?token=" . urlencode($token);

                    // Coba kirim via fungsi mail() PHP standar jika server mendukung
                    $subjek = "Instruksi Reset Password - " . $profil['nama_kost'];
                    $pesan = "Halo {$user_nama},\n\n";
                    $pesan .= "Kami menerima permintaan untuk mereset password akun Anda di {$profil['nama_kost']}.\n";
                    $pesan .= "Silakan klik tautan berikut untuk membuat password baru (berlaku selama 30 menit):\n";
                    $pesan .= "{$reset_url}\n\n";
                    $pesan .= "Atau masukkan kode verifikasi OTP berikut: {$otp}\n\n";
                    $pesan .= "Jika Anda tidak meminta reset password ini, abaikan pesan ini.\n\n";
                    $pesan .= "Salam,\n" . $profil['nama_kost'];
                    $headers = "From: no-reply@" . ($host ? $host : 'griyakost.com') . "\r\n" .
                               "Reply-To: " . ($profil['email'] ?? 'admin@griyakost.com') . "\r\n" .
                               "X-Mailer: PHP/" . phpversion();
                    @mail($user_email, $subjek, $pesan, $headers);

                    $success = true;
                    $reset_data = [
                        'nama' => $user_nama,
                        'email' => $user_email,
                        'token' => $token,
                        'otp' => $otp,
                        'url' => $reset_url,
                        'expired' => $expires_at
                    ];
                } else {
                    $error = "Terjadi kesalahan saat memproses permintaan: " . mysqli_error($conn);
                }
            }
        } else {
            $error = "Akun dengan email atau username '<strong>" . htmlspecialchars($akun_input) . "</strong>' tidak ditemukan di database.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lupa Password - <?= htmlspecialchars($profil['nama_kost']) ?></title>
    <link rel="stylesheet" href="assets/css/style.css?v=<?= time() ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body style="background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%); min-height: 100vh; display: flex; flex-direction: column;">

    <div class="auth-wrapper" style="flex: 1; display: flex; align-items: center; justify-content: center; padding: 40px 15px;">
        <div class="auth-card" style="width: 100%; max-width: 500px; background: #ffffff; border-radius: 16px; box-shadow: 0 10px 25px -5px rgba(15,23,42,0.1); border: 1px solid #e2e8f0; padding: 36px;">
            
            <div class="auth-header" style="text-align: center; margin-bottom: 24px;">
                <a href="index.php" style="display: inline-flex; align-items: center; gap: 8px; font-weight: 800; font-size: 1.3rem; color: var(--primary);">
                    <i class="fa-solid fa-hotel"></i> <?= htmlspecialchars($profil['nama_kost']) ?>
                </a>
                <div style="width: 56px; height: 56px; background: #eef2ff; color: var(--primary); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; margin: 16px auto 12px auto;">
                    <i class="fa-solid fa-key"></i>
                </div>
                <h2 style="font-size: 1.5rem; font-weight: 800; color: #0f172a; margin-bottom: 6px;">Lupa Kata Sandi?</h2>
                <p style="font-size: 0.9rem; color: #64748b; line-height: 1.5;">
                    Jangan khawatir! Masukkan username atau alamat email yang terdaftar untuk mereset kata sandi akun Anda.
                </p>
            </div>

            <?php if (!empty($error)): ?>
                <div class="alert-box danger" style="margin-bottom: 20px; background: #fee2e2; border: 1px solid #fca5a5; color: #991b1b; padding: 12px 16px; border-radius: 10px; font-size: 0.9rem; display: flex; align-items: center; gap: 10px;">
                    <i class="fa-solid fa-circle-exclamation" style="font-size: 1.1rem; flex-shrink: 0;"></i>
                    <div><?= $error ?></div>
                </div>
            <?php endif; ?>

            <?php if ($success && $reset_data): ?>
                <div style="background: #f0fdf4; border: 1px solid #86efac; color: #166534; padding: 18px; border-radius: 12px; margin-bottom: 24px;">
                    <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 8px;">
                        <i class="fa-solid fa-circle-check" style="font-size: 1.3rem; color: #16a34a;"></i>
                        <h4 style="margin: 0; font-size: 1.05rem; font-weight: 700; color: #14532d;">Permintaan Reset Terkirim!</h4>
                    </div>
                    <p style="font-size: 0.88rem; margin: 0 0 14px 0; color: #15803d; line-height: 1.45;">
                        Tautan reset kata sandi telah berhasil digenerate untuk akun <strong><?= htmlspecialchars($reset_data['email']) ?></strong>.
                    </p>

                    <!-- Card Info Simulasi / Direct Reset untuk Localhost / AppServ -->
                    <div style="background: #ffffff; border: 1px dashed #22c55e; border-radius: 10px; padding: 14px; margin-top: 10px; box-shadow: 0 2px 4px rgba(0,0,0,0.02);">
                        <div style="font-size: 0.8rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: #047857; margin-bottom: 8px; display: flex; align-items: center; justify-content: space-between;">
                            <span><i class="fa-solid fa-shield-halved"></i> Tautan Reset Cepat (Localhost)</span>
                            <span style="font-size: 0.75rem; background: #dcfce7; padding: 2px 8px; border-radius: 20px;">Berlaku 30 Menit</span>
                        </div>
                        
                        <div style="margin-bottom: 12px;">
                            <label style="font-size: 0.78rem; color: #64748b; font-weight: 600; display: block; margin-bottom: 4px;">Kode OTP Verifikasi:</label>
                            <div style="font-size: 1.35rem; font-weight: 800; letter-spacing: 4px; color: var(--primary); background: #f8fafc; padding: 6px 12px; border-radius: 6px; text-align: center; border: 1px solid #e2e8f0;">
                                <?= htmlspecialchars($reset_data['otp']) ?>
                            </div>
                        </div>

                        <a href="<?= htmlspecialchars($reset_data['url']) ?>" class="btn btn-primary btn-block" style="padding: 10px 16px; font-size: 0.92rem; border-radius: 8px; display: flex; align-items: center; justify-content: center; gap: 8px; font-weight: 700; text-decoration: none;">
                            <i class="fa-solid fa-arrow-right"></i> Lanjut Buat Password Baru
                        </a>
                    </div>
                </div>

                <div style="text-align: center; margin-top: 18px;">
                    <a href="login.php" class="btn btn-outline-secondary" style="font-size: 0.9rem; padding: 8px 18px; border-radius: 8px; display: inline-flex; align-items: center; gap: 8px; border: 1px solid #cbd5e1; color: #475569; text-decoration: none;">
                        <i class="fa-solid fa-arrow-left"></i> Kembali ke Halaman Login
                    </a>
                </div>

            <?php else: ?>

                <form action="lupa_password.php" method="POST">
                    <div class="form-group" style="margin-bottom: 22px;">
                        <label for="akun_input" style="font-size: 0.88rem; font-weight: 600; color: #334155; margin-bottom: 8px; display: block;">
                            <i class="fa-solid fa-envelope"></i> Email atau Username Akun *
                        </label>
                        <input type="text" name="akun_input" id="akun_input" class="form-control" placeholder="Contoh: user1@gmail.com atau user1" required autofocus value="<?= isset($_POST['akun_input']) ? htmlspecialchars($_POST['akun_input']) : '' ?>" style="padding: 12px 14px; font-size: 0.95rem; border-radius: 8px;">
                        <small style="color: #64748b; font-size: 0.8rem; display: block; margin-top: 6px;">
                            Masukkan email atau username yang digunakan saat mendaftar kost.
                        </small>
                    </div>

                    <button type="submit" class="btn btn-primary btn-block" style="padding: 12px; font-size: 1rem; border-radius: 10px; font-weight: 700; display: flex; align-items: center; justify-content: center; gap: 8px;">
                        <i class="fa-solid fa-paper-plane"></i> Kirim Link Reset Password
                    </button>
                </form>

                <div style="margin-top: 24px; padding-top: 18px; border-top: 1px solid #e2e8f0; text-align: center;">
                    <p style="font-size: 0.9rem; color: #475569; margin-bottom: 12px;">
                        Sudah ingat password Anda? <a href="login.php" style="color: var(--primary); font-weight: 700;">Masuk di sini</a>
                    </p>
                    <div>
                        <a href="index.php" style="font-size: 0.85rem; color: #64748b; text-decoration: none;">
                            <i class="fa-solid fa-arrow-left"></i> Kembali ke Halaman Utama
                        </a>
                    </div>
                </div>

                <!-- Bantuan Admin via WhatsApp jika lupa email -->
                <?php if (!empty($profil['no_wa'])): ?>
                <div style="margin-top: 20px; background: #f8fafc; border-radius: 10px; padding: 12px; border: 1px solid #e2e8f0; text-align: center;">
                    <span style="font-size: 0.82rem; color: #64748b; display: block; margin-bottom: 6px;">Tidak ingat email atau butuh bantuan admin?</span>
                    <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $profil['no_wa']) ?>?text=Halo%20Admin%20<?= urlencode($profil['nama_kost']) ?>,%20saya%20mengalami%20kendala%20lupa%20password%20akun%20kost." target="_blank" style="display: inline-flex; align-items: center; gap: 6px; font-size: 0.84rem; color: #16a34a; font-weight: 700; text-decoration: none;">
                        <i class="fa-brands fa-whatsapp" style="font-size: 1rem;"></i> Hubungi Bantuan via WhatsApp
                    </a>
                </div>
                <?php endif; ?>

            <?php endif; ?>

        </div>
    </div>

</body>
</html>
