<?php
/**
 * Pengaturan Profil Saya (Pengunjung/Penyewa)
 */
require_once __DIR__ . '/../koneksi.php';
require_once __DIR__ . '/../config/helper.php';

auth_check();

$id_user = (int)$_SESSION['user_id'];
$user = get_current_user_data($conn);

$error = '';

// 1. PROSES UPDATE PROFIL USER (Sebelum header.php)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['btn_update_user_profil'])) {
    $nama = clean($conn, $_POST['nama_lengkap']);
    $email = clean($conn, $_POST['email']);
    $no_hp = clean($conn, $_POST['no_hp']);
    $pekerjaan = clean($conn, $_POST['pekerjaan']);
    $alamat = clean($conn, $_POST['alamat_asal']);
    $password_baru = $_POST['password_baru'];

    if (empty($nama) || empty($email) || empty($no_hp)) {
        $error = "Nama lengkap, email, dan no HP wajib diisi.";
    } else {
        if (!empty($password_baru)) {
            if (strlen($password_baru) < 6) {
                $error = "Password baru minimal 6 karakter.";
            } else {
                $pass_hash = md5($password_baru);
                $stmt = mysqli_prepare($conn, "UPDATE tbl_users SET nama_lengkap=?, email=?, no_hp=?, pekerjaan=?, alamat_asal=?, password=? WHERE id_user=?");
                mysqli_stmt_bind_param($stmt, "ssssssi", $nama, $email, $no_hp, $pekerjaan, $alamat, $pass_hash, $id_user);
                mysqli_stmt_execute($stmt);
                set_flash('success', 'Profil dan password berhasil diperbarui.');
            }
        } else {
            $stmt = mysqli_prepare($conn, "UPDATE tbl_users SET nama_lengkap=?, email=?, no_hp=?, pekerjaan=?, alamat_asal=? WHERE id_user=?");
            mysqli_stmt_bind_param($stmt, "sssssi", $nama, $email, $no_hp, $pekerjaan, $alamat, $id_user);
            mysqli_stmt_execute($stmt);
            set_flash('success', 'Profil akun Anda berhasil diperbarui.');
        }

        if (empty($error)) {
            header("Location: profil.php");
            exit();
        }
    }
}

$page_title = "Pengaturan Profil Saya";
require_once __DIR__ . '/header.php';
?>

<div class="card" style="max-width: 680px; margin: 0 auto;">
    <div class="card-header">
        <h3 class="card-title"><i class="fa-solid fa-user-pen"></i> Edit Informasi Akun & Password</h3>
    </div>
    <div class="card-body">
        
        <?php if (!empty($error)): ?>
            <div class="alert-box danger"><i class="fa-solid fa-circle-exclamation"></i> <?= $error ?></div>
        <?php endif; ?>

        <form action="profil.php" method="POST">
            <div class="form-row">
                <div class="form-group">
                    <label>Username (Tetap)</label>
                    <input type="text" class="form-control" value="<?= htmlspecialchars($user['username']) ?>" disabled style="background: var(--adm-bg); color: var(--adm-muted);">
                </div>
                <div class="form-group">
                    <label>Role Pengguna</label>
                    <input type="text" class="form-control" value="<?= ucfirst($user['role']) ?>" disabled style="background: var(--adm-bg); color: var(--adm-muted);">
                </div>
            </div>

            <div class="form-group">
                <label>Nama Lengkap *</label>
                <input type="text" name="nama_lengkap" class="form-control" required value="<?= htmlspecialchars($user['nama_lengkap']) ?>">
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Email *</label>
                    <input type="email" name="email" class="form-control" required value="<?= htmlspecialchars($user['email']) ?>">
                </div>
                <div class="form-group">
                    <label>No. HP / WhatsApp *</label>
                    <input type="tel" name="no_hp" class="form-control" required value="<?= htmlspecialchars($user['no_hp']) ?>">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Pekerjaan / Status</label>
                    <input type="text" name="pekerjaan" class="form-control" value="<?= htmlspecialchars($user['pekerjaan'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>Alamat / Kota Asal</label>
                    <input type="text" name="alamat_asal" class="form-control" value="<?= htmlspecialchars($user['alamat_asal'] ?? '') ?>">
                </div>
            </div>

            <hr style="border: 0; border-top: 1px solid var(--adm-border); margin: 20px 0;">
            <h4 style="margin-bottom: 12px; font-size: 1rem; color: var(--adm-primary);"><i class="fa-solid fa-lock"></i> Ganti Password Akun</h4>

            <div class="form-group">
                <label>Password Baru (Kosongkan jika tidak ingin mengubah)</label>
                <input type="password" name="password_baru" class="form-control" placeholder="Ketik minimal 6 karakter...">
            </div>

            <div style="margin-top: 24px; display: flex; justify-content: flex-end;">
                <button type="submit" name="btn_update_user_profil" class="btn btn-primary" style="padding: 12px 24px;">
                    <i class="fa-solid fa-floppy-disk"></i> Simpan Perubahan
                </button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
