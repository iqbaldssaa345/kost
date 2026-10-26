<?php
/**
 * Pengaturan Profil Kost (Admin)
 */
require_once __DIR__ . '/../koneksi.php';
require_once __DIR__ . '/../config/helper.php';

auth_check(['admin']);

$upload_dir = __DIR__ . '/../uploads/profil/';
if (!file_exists($upload_dir)) {
    @mkdir($upload_dir, 0777, true);
}

$profil = get_profil_kost($conn);

// 1. PROSES UPDATE PROFIL KOST (Sebelum header.php)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['btn_update_profil'])) {
    $nama_kost = clean($conn, $_POST['nama_kost']);
    $slogan = clean($conn, $_POST['slogan']);
    $deskripsi = clean($conn, $_POST['deskripsi']);
    $alamat = clean($conn, $_POST['alamat']);
    $kota = clean($conn, $_POST['kota']);
    $no_telepon = clean($conn, $_POST['no_telepon']);
    $no_wa = clean($conn, $_POST['no_wa']);
    $email = clean($conn, $_POST['email']);
    $nama_bank = clean($conn, $_POST['nama_bank']);
    $no_rekening = clean($conn, $_POST['no_rekening']);
    $atas_nama = clean($conn, $_POST['atas_nama']);

    $logo = $profil['logo'];
    // Upload Logo Baru jika ada
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
        $ext = pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION);
        if (in_array(strtolower($ext), ['jpg', 'jpeg', 'png', 'webp', 'svg'])) {
            $new_logo = 'logo_' . time() . '.' . $ext;
            if (move_uploaded_file($_FILES['logo']['tmp_name'], $upload_dir . $new_logo)) {
                $logo = $new_logo;
            }
        }
    }

    $stmt = mysqli_prepare($conn, "UPDATE tbl_pengaturan SET nama_kost=?, slogan=?, deskripsi=?, alamat=?, kota=?, no_telepon=?, no_wa=?, email=?, nama_bank=?, no_rekening=?, atas_nama=?, logo=? WHERE id=1");
    mysqli_stmt_bind_param($stmt, "ssssssssssss", $nama_kost, $slogan, $deskripsi, $alamat, $kota, $no_telepon, $no_wa, $email, $nama_bank, $no_rekening, $atas_nama, $logo);

    if (mysqli_stmt_execute($stmt)) {
        set_flash('success', 'Profil dan informasi kost berhasil diperbarui.');
    } else {
        set_flash('danger', 'Gagal memperbarui profil: ' . mysqli_error($conn));
    }
    header("Location: profil_kost.php");
    exit();
}

$page_title = "Pengaturan Profil Kost";
require_once __DIR__ . '/header.php';
?>

<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fa-solid fa-hotel"></i> Informasi & Profil Bisnis Kost</h3>
    </div>
    <div class="card-body">
        <form action="profil_kost.php" method="POST" enctype="multipart/form-data">
            
            <div class="form-row">
                <div class="form-group">
                    <label>Nama Kost *</label>
                    <input type="text" name="nama_kost" class="form-control" required value="<?= htmlspecialchars($profil['nama_kost']) ?>">
                </div>

                <div class="form-group">
                    <label>Slogan / Tagline Kost</label>
                    <input type="text" name="slogan" class="form-control" value="<?= htmlspecialchars($profil['slogan']) ?>">
                </div>
            </div>

            <div class="form-group">
                <label>Deskripsi Profil Kost (Ditampilkan di Halaman Depan)</label>
                <textarea name="deskripsi" class="form-control" rows="3"><?= htmlspecialchars($profil['deskripsi']) ?></textarea>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Alamat Lengkap *</label>
                    <input type="text" name="alamat" class="form-control" required value="<?= htmlspecialchars($profil['alamat']) ?>">
                </div>

                <div class="form-group">
                    <label>Kota / Wilayah *</label>
                    <input type="text" name="kota" class="form-control" required value="<?= htmlspecialchars($profil['kota']) ?>">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Nomor WhatsApp Pengelola *</label>
                    <input type="text" name="no_wa" class="form-control" required placeholder="081234567890" value="<?= htmlspecialchars($profil['no_wa']) ?>">
                    <small class="form-text">Format: 0812... (Akan langsung terhubung ke tombol chat WA pengunjung)</small>
                </div>

                <div class="form-group">
                    <label>Nomor Telepon Kantor / Rumah</label>
                    <input type="text" name="no_telepon" class="form-control" value="<?= htmlspecialchars($profil['no_telepon']) ?>">
                </div>

                <div class="form-group">
                    <label>Email Kontak</label>
                    <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($profil['email']) ?>">
                </div>
            </div>

            <hr style="border:0; border-top: 1px solid var(--adm-border); margin: 24px 0;">
            <h4 style="margin-bottom: 14px; color: var(--adm-primary);"><i class="fa-solid fa-credit-card"></i> Informasi Rekening Pembayaran</h4>

            <div class="form-row">
                <div class="form-group">
                    <label>Nama Bank *</label>
                    <input type="text" name="nama_bank" class="form-control" required placeholder="Bank BCA / Mandiri / BRI" value="<?= htmlspecialchars($profil['nama_bank']) ?>">
                </div>

                <div class="form-group">
                    <label>Nomor Rekening *</label>
                    <input type="text" name="no_rekening" class="form-control" required placeholder="123-456-7890" value="<?= htmlspecialchars($profil['no_rekening']) ?>">
                </div>

                <div class="form-group">
                    <label>Atas Nama (Pemilik Rekening) *</label>
                    <input type="text" name="atas_nama" class="form-control" required placeholder="Pengelola Kost" value="<?= htmlspecialchars($profil['atas_nama']) ?>">
                </div>
            </div>

            <div style="margin-top: 24px; display: flex; justify-content: flex-end;">
                <button type="submit" name="btn_update_profil" class="btn btn-primary" style="padding: 12px 28px;">
                    <i class="fa-solid fa-floppy-disk"></i> Simpan Pengaturan Profil
                </button>
            </div>

        </form>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
