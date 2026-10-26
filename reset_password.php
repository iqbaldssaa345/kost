<?php
/**
 * Halaman Reset / Buat Password Baru
 * Sistem Informasi Manajemen Kost Lengkap
 */
require_once __DIR__ . '/koneksi.php';
require_once __DIR__ . '/config/helper.php';

$profil = get_profil_kost($conn);
$error = '';
$token_param = isset($_GET['token']) ? trim($_GET['token']) : (isset($_POST['token']) ? trim($_POST['token']) : '');
$otp_param   = isset($_GET['otp']) ? trim($_GET['otp']) : (isset($_POST['otp']) ? trim($_POST['otp']) : '');

$valid_reset = false;
$reset_record = null;
$user_info = null;

// Cek dan validasi token atau OTP
if (!empty($token_param) || !empty($otp_param)) {
    $now = date('Y-m-d H:i:s');
    
    if (!empty($token_param)) {
        $stmt_check = mysqli_prepare($conn, "SELECT * FROM tbl_password_resets WHERE token = ? AND expires_at > ? LIMIT 1");
        mysqli_stmt_bind_param($stmt_check, "ss", $token_param, $now);
    } else {
        $stmt_check = mysqli_prepare($conn, "SELECT * FROM tbl_password_resets WHERE otp = ? AND expires_at > ? LIMIT 1");
        mysqli_stmt_bind_param($stmt_check, "ss", $otp_param, $now);
    }
    
    mysqli_stmt_execute($stmt_check);
    $res_check = mysqli_stmt_get_result($stmt_check);

    if ($reset_record = mysqli_fetch_assoc($res_check)) {
        $valid_reset = true;
        
        // Ambil data user
        $stmt_u = mysqli_prepare($conn, "SELECT id_user, username, nama_lengkap, email, role FROM tbl_users WHERE email = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt_u, "s", $reset_record['email']);
        mysqli_stmt_execute($stmt_u);
        $res_u = mysqli_stmt_get_result($stmt_u);
        $user_info = mysqli_fetch_assoc($res_u);
    } else {
        $error = "Tautan atau kode reset password tidak valid atau sudah kedaluwarsa (lebih dari 30 menit). Silakan ajukan permohonan reset ulang.";
    }
} else {
    $error = "Token atau kode verifikasi tidak ditemukan. Silakan masukkan permohonan reset kata sandi terlebih dahulu.";
}

// Tangani submit perubahan password
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $valid_reset && $user_info) {
    $password_baru = isset($_POST['password']) ? $_POST['password'] : '';
    $konfirmasi_password = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';

    if (empty($password_baru) || empty($konfirmasi_password)) {
        $error = "Semua kolom password wajib diisi.";
    } elseif (strlen($password_baru) < 6) {
        $error = "Password baru minimal harus 6 karakter.";
    } elseif ($password_baru !== $konfirmasi_password) {
        $error = "Konfirmasi password tidak cocok dengan password baru yang dimasukkan.";
    } else {
        // Hash password baru (menggunakan MD5 kompatibel)
        $pass_hash = md5($password_baru);

        // Update password pengguna
        $stmt_update = mysqli_prepare($conn, "UPDATE tbl_users SET password = ? WHERE id_user = ?");
        mysqli_stmt_bind_param($stmt_update, "si", $pass_hash, $user_info['id_user']);

        if (mysqli_stmt_execute($stmt_update)) {
            // Hapus token yang sudah digunakan
            $user_email = $user_info['email'];
            $stmt_del = mysqli_prepare($conn, "DELETE FROM tbl_password_resets WHERE email = ?");
            mysqli_stmt_bind_param($stmt_del, "s", $user_email);
            mysqli_stmt_execute($stmt_del);

            set_flash('success', "Kata sandi akun Anda berhasil diperbarui! Silakan login menggunakan kata sandi baru.");
            header("Location: login.php");
            exit();
        } else {
            $error = "Gagal memperbarui kata sandi: " . mysqli_error($conn);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password Baru - <?= htmlspecialchars($profil['nama_kost']) ?></title>
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
                <div style="width: 56px; height: 56px; background: #dbeafe; color: #2563eb; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; margin: 16px auto 12px auto;">
                    <i class="fa-solid fa-lock-open"></i>
                </div>
                <h2 style="font-size: 1.5rem; font-weight: 800; color: #0f172a; margin-bottom: 6px;">Buat Password Baru</h2>
                <p style="font-size: 0.9rem; color: #64748b; line-height: 1.5;">
                    Silakan masukkan kata sandi baru yang kuat dan mudah Anda ingat.
                </p>
            </div>

            <?php if (!empty($error) && !$valid_reset): ?>
                <div style="background: #fff1f2; border: 1px solid #fecdd3; color: #9f1239; padding: 18px; border-radius: 12px; margin-bottom: 20px; text-align: center;">
                    <i class="fa-solid fa-circle-exclamation" style="font-size: 2rem; color: #e11d48; margin-bottom: 10px; display: block;"></i>
                    <h4 style="margin: 0 0 6px 0; font-size: 1rem; font-weight: 700; color: #881337;">Tautan Tidak Valid</h4>
                    <p style="font-size: 0.88rem; margin: 0 0 16px 0; line-height: 1.45;">
                        <?= $error ?>
                    </p>
                    <a href="lupa_password.php" class="btn btn-primary" style="padding: 10px 18px; font-size: 0.88rem; border-radius: 8px; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; font-weight: 700;">
                        <i class="fa-solid fa-rotate-left"></i> Ajukan Reset Ulang
                    </a>
                </div>

                <div style="text-align: center; margin-top: 18px;">
                    <a href="login.php" style="font-size: 0.85rem; color: #64748b; text-decoration: none;">
                        <i class="fa-solid fa-arrow-left"></i> Kembali ke Halaman Login
                    </a>
                </div>

            <?php else: ?>

                <?php if (!empty($error)): ?>
                    <div class="alert-box danger" style="margin-bottom: 20px; background: #fee2e2; border: 1px solid #fca5a5; color: #991b1b; padding: 12px 16px; border-radius: 10px; font-size: 0.9rem; display: flex; align-items: center; gap: 10px;">
                        <i class="fa-solid fa-circle-exclamation" style="font-size: 1.1rem; flex-shrink: 0;"></i>
                        <div><?= $error ?></div>
                    </div>
                <?php endif; ?>

                <?php if ($user_info): ?>
                <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 12px 16px; margin-bottom: 20px; display: flex; align-items: center; gap: 12px;">
                    <div style="width: 38px; height: 38px; border-radius: 50%; background: #e2e8f0; color: #475569; display: flex; align-items: center; justify-content: center; font-size: 1.1rem; font-weight: 700;">
                        <?= strtoupper(substr($user_info['nama_lengkap'], 0, 1)) ?>
                    </div>
                    <div>
                        <div style="font-size: 0.88rem; font-weight: 700; color: #1e293b;"><?= htmlspecialchars($user_info['nama_lengkap']) ?></div>
                        <div style="font-size: 0.8rem; color: #64748b;">@<?= htmlspecialchars($user_info['username']) ?> &bull; <?= htmlspecialchars($user_info['email']) ?></div>
                    </div>
                </div>
                <?php endif; ?>

                <form action="reset_password.php" method="POST" id="formResetPass">
                    <input type="hidden" name="token" value="<?= htmlspecialchars($token_param) ?>">
                    <input type="hidden" name="otp" value="<?= htmlspecialchars($otp_param) ?>">

                    <div class="form-group" style="margin-bottom: 18px;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                            <label for="new_password" style="font-size: 0.88rem; font-weight: 600; color: #334155;">
                                <i class="fa-solid fa-lock"></i> Password Baru *
                            </label>
                            <span id="toggleNewPassBtn" style="font-size: 0.8rem; color: var(--primary); cursor: pointer; font-weight: 600;" onclick="toggleNewPass()">
                                <i class="fa-solid fa-eye" id="eyeIconNew"></i> Tampilkan
                            </span>
                        </div>
                        <input type="password" name="password" id="new_password" class="form-control" placeholder="Minimal 6 karakter" required autofocus minlength="6" onkeyup="checkPasswordStrength(this.value)" style="padding: 12px 14px; font-size: 0.95rem; border-radius: 8px;">
                        
                        <!-- Password Strength Indicator -->
                        <div style="margin-top: 8px;">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px;">
                                <span style="font-size: 0.75rem; color: #64748b;">Kekuatan Password:</span>
                                <span id="strengthText" style="font-size: 0.75rem; font-weight: 700; color: #94a3b8;">-</span>
                            </div>
                            <div style="height: 4px; background: #e2e8f0; border-radius: 4px; overflow: hidden;">
                                <div id="strengthBar" style="height: 100%; width: 0%; transition: all 0.3s ease; background: #94a3b8;"></div>
                            </div>
                        </div>
                    </div>

                    <div class="form-group" style="margin-bottom: 24px;">
                        <label for="confirm_password" style="font-size: 0.88rem; font-weight: 600; color: #334155; margin-bottom: 6px; display: block;">
                            <i class="fa-solid fa-lock-open"></i> Konfirmasi Password Baru *
                        </label>
                        <input type="password" name="confirm_password" id="confirm_password" class="form-control" placeholder="Ulangi password baru" required minlength="6" style="padding: 12px 14px; font-size: 0.95rem; border-radius: 8px;">
                    </div>

                    <button type="submit" class="btn btn-primary btn-block" style="padding: 12px; font-size: 1rem; border-radius: 10px; font-weight: 700; display: flex; align-items: center; justify-content: center; gap: 8px;">
                        <i class="fa-solid fa-check"></i> Simpan Password Baru
                    </button>
                </form>

                <div style="margin-top: 24px; padding-top: 18px; border-top: 1px solid #e2e8f0; text-align: center;">
                    <a href="login.php" style="font-size: 0.85rem; color: #64748b; text-decoration: none;">
                        <i class="fa-solid fa-arrow-left"></i> Batalkan & Kembali ke Login
                    </a>
                </div>

            <?php endif; ?>

        </div>
    </div>

    <script>
    function toggleNewPass() {
        const passInput = document.getElementById('new_password');
        const confirmInput = document.getElementById('confirm_password');
        const eyeIcon = document.getElementById('eyeIconNew');
        const toggleText = document.getElementById('toggleNewPassBtn');

        if (passInput.type === 'password') {
            passInput.type = 'text';
            if (confirmInput) confirmInput.type = 'text';
            eyeIcon.className = 'fa-solid fa-eye-slash';
            toggleText.innerHTML = '<i class="fa-solid fa-eye-slash"></i> Sembunyikan';
        } else {
            passInput.type = 'password';
            if (confirmInput) confirmInput.type = 'password';
            eyeIcon.className = 'fa-solid fa-eye';
            toggleText.innerHTML = '<i class="fa-solid fa-eye"></i> Tampilkan';
        }
    }

    function checkPasswordStrength(val) {
        const bar = document.getElementById('strengthBar');
        const text = document.getElementById('strengthText');
        if (!bar || !text) return;

        if (val.length === 0) {
            bar.style.width = '0%';
            bar.style.background = '#94a3b8';
            text.innerText = '-';
            text.style.color = '#94a3b8';
            return;
        }

        let score = 0;
        if (val.length >= 6) score += 1;
        if (val.length >= 8) score += 1;
        if (/[A-Z]/.test(val)) score += 1;
        if (/[0-9]/.test(val)) score += 1;
        if (/[^A-Za-z0-9]/.test(val)) score += 1;

        if (score <= 2) {
            bar.style.width = '33%';
            bar.style.background = '#ef4444';
            text.innerText = 'Lemah';
            text.style.color = '#ef4444';
        } else if (score <= 3) {
            bar.style.width = '66%';
            bar.style.background = '#f59e0b';
            text.innerText = 'Sedang';
            text.style.color = '#f59e0b';
        } else {
            bar.style.width = '100%';
            bar.style.background = '#10b981';
            text.innerText = 'Kuat & Aman';
            text.style.color = '#10b981';
        }
    }
    </script>
</body>
</html>
