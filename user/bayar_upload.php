<?php
/**
 * Pembayaran & Upload Bukti (Pengunjung/Penyewa)
 */
require_once __DIR__ . '/../koneksi.php';
require_once __DIR__ . '/../config/helper.php';

auth_check();

$upload_dir = __DIR__ . '/../uploads/bukti/';
if (!file_exists($upload_dir)) {
    @mkdir($upload_dir, 0777, true);
}

$id_user = (int)$_SESSION['user_id'];
$id_tagihan = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$q_tag = mysqli_query($conn, "SELECT t.*, k.nomor_kamar, k.nama_kamar 
                             FROM tbl_tagihan_pembayaran t 
                             JOIN tbl_kamar k ON t.id_kamar = k.id_kamar 
                             WHERE t.id_tagihan = $id_tagihan AND t.id_user = $id_user LIMIT 1");

if (!$q_tag || mysqli_num_rows($q_tag) == 0) {
    header("Location: tagihan_saya.php");
    exit();
}

$tagihan = mysqli_fetch_assoc($q_tag);
$error = '';

// 1. PROSES UPLOAD BUKTI BAYAR (Sebelum header.php)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['btn_upload_bukti'])) {
    $metode = clean($conn, $_POST['metode_pembayaran']);
    $catatan = clean($conn, $_POST['catatan_pembayar']);

    if (!isset($_FILES['bukti_transfer']) || $_FILES['bukti_transfer']['error'] !== UPLOAD_ERR_OK) {
        $error = "Silakan pilih foto / file bukti transfer pembayaran Anda.";
    } else {
        $ext = pathinfo($_FILES['bukti_transfer']['name'], PATHINFO_EXTENSION);
        $allowed = ['jpg', 'jpeg', 'png', 'webp', 'pdf'];

        if (!in_array(strtolower($ext), $allowed)) {
            $error = "Format file tidak didukung. Harap upload gambar (JPG, PNG, WEBP) atau PDF.";
        } else {
            $new_name = 'bukti_' . $id_tagihan . '_' . time() . '.' . $ext;
            if (move_uploaded_file($_FILES['bukti_transfer']['tmp_name'], $upload_dir . $new_name)) {
                $now = date('Y-m-d H:i:s');
                
                // Update Tagihan
                $stmt = mysqli_prepare($conn, "UPDATE tbl_tagihan_pembayaran SET status_pembayaran = 'menunggu_verifikasi', bukti_transfer = ?, tgl_bayar = ?, metode_pembayaran = ?, catatan_pembayar = ? WHERE id_tagihan = ?");
                mysqli_stmt_bind_param($stmt, "ssssi", $new_name, $now, $metode, $catatan, $id_tagihan);

                if (mysqli_stmt_execute($stmt)) {
                    set_flash('success', 'Bukti transfer berhasil diunggah! Petugas akan segera memverifikasi pembayaran Anda.');
                    header("Location: tagihan_saya.php");
                    exit();
                } else {
                    $error = "Gagal memperbarui database: " . mysqli_error($conn);
                }
            } else {
                $error = "Gagal mengunggah file ke server. Periksa izin folder uploads.";
            }
        }
    }
}

$page_title = "Pembayaran & Upload Bukti";
require_once __DIR__ . '/header.php';
?>

<div style="margin-bottom: 18px;">
    <a href="tagihan_saya.php" class="btn btn-outline btn-sm"><i class="fa-solid fa-arrow-left"></i> Kembali ke Tagihan Saya</a>
</div>

<?php if (!empty($error)): ?>
    <div class="alert-box danger"><i class="fa-solid fa-circle-exclamation"></i> <?= $error ?></div>
<?php endif; ?>

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px; align-items: start;">
    
    <!-- KOLOM KIRI: PETUNJUK TRANSFER BANK & TOTAL -->
    <div>
        <div class="card" style="margin-bottom: 20px;">
            <div class="card-header" style="background: #eef2ff;">
                <h3 class="card-title" style="color: #4338ca;"><i class="fa-solid fa-credit-card"></i> Rekening Pembayaran Resmi</h3>
            </div>
            <div class="card-body">
                <p style="color: var(--adm-muted); font-size: 0.9rem; margin-bottom: 16px;">
                    Silakan lakukan transfer sejumlah nominal yang tertera ke rekening bank pengelola kost berikut:
                </p>

                <div style="background: var(--adm-bg); border-radius: var(--radius-md); padding: 18px; border: 1px solid var(--adm-border); margin-bottom: 18px;">
                    <div style="font-size: 0.8rem; text-transform: uppercase; color: var(--adm-muted); font-weight: 700;">Nama Bank</div>
                    <div style="font-size: 1.15rem; font-weight: 800; color: var(--adm-text); margin-bottom: 10px;"><?= htmlspecialchars($profil['nama_bank']) ?></div>

                    <div style="font-size: 0.8rem; text-transform: uppercase; color: var(--adm-muted); font-weight: 700;">Nomor Rekening</div>
                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 10px;">
                        <span style="font-size: 1.35rem; font-weight: 800; color: var(--adm-primary); letter-spacing: 1px;" id="rekNum"><?= htmlspecialchars($profil['no_rekening']) ?></span>
                        <button type="button" class="btn btn-outline btn-sm" onclick="navigator.clipboard.writeText('<?= $profil['no_rekening'] ?>'); alert('Nomor rekening disalin!');">
                            <i class="fa-solid fa-copy"></i> Salin
                        </button>
                    </div>

                    <div style="font-size: 0.8rem; text-transform: uppercase; color: var(--adm-muted); font-weight: 700;">Atas Nama</div>
                    <div style="font-size: 1rem; font-weight: 700; color: var(--adm-text);"><?= htmlspecialchars($profil['atas_nama']) ?></div>
                </div>

                <div style="border-top: 1px solid var(--adm-border); padding-top: 16px;">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 6px;">
                        <span style="color: var(--adm-muted);">Nomor Invoice:</span>
                        <b><?= htmlspecialchars($tagihan['kode_tagihan']) ?></b>
                    </div>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 6px;">
                        <span style="color: var(--adm-muted);">Rincian:</span>
                        <b><?= htmlspecialchars($tagihan['judul_tagihan']) ?></b>
                    </div>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 6px;">
                        <span style="color: var(--adm-muted);">Kamar:</span>
                        <b>Kamar <?= htmlspecialchars($tagihan['nomor_kamar']) ?></b>
                    </div>
                    <div style="display: flex; justify-content: space-between; margin-top: 12px; padding-top: 12px; border-top: 1px dashed var(--adm-border);">
                        <span style="font-size: 1.05rem; font-weight: 700;">Total Harus Dibayar:</span>
                        <span style="font-size: 1.3rem; font-weight: 800; color: #059669;"><?= format_rupiah($tagihan['nominal']) ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- KOLOM KANAN: FORM UPLOAD BUKTI -->
    <div>
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fa-solid fa-cloud-arrow-up"></i> Formulir Unggah Bukti Pembayaran</h3>
            </div>
            <div class="card-body">
                
                <?php if ($tagihan['status_pembayaran'] === 'menunggu_verifikasi'): ?>
                    <div class="alert-box warning">
                        <i class="fa-solid fa-clock"></i> Anda telah mengunggah bukti pembayaran. Petugas sedang memverifikasi data Anda. Anda dapat mengunggah ulang jika bukti salah.
                    </div>
                <?php endif; ?>

                <form action="bayar_upload.php?id=<?= $id_tagihan ?>" method="POST" enctype="multipart/form-data">
                    
                    <div class="form-group">
                        <label>Metode Pembayaran *</label>
                        <select name="metode_pembayaran" class="form-control" required>
                            <option value="Transfer Bank BCA">Transfer Bank BCA</option>
                            <option value="Transfer Bank Mandiri">Transfer Bank Mandiri</option>
                            <option value="Transfer Bank BRI">Transfer Bank BRI</option>
                            <option value="Transfer Bank BNI">Transfer Bank BNI</option>
                            <option value="QRIS / E-Wallet (GoPay/OVO/Dana)">QRIS / E-Wallet (GoPay/OVO/Dana)</option>
                            <option value="Tunai ke Petugas">Tunai Langsung ke Petugas</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Unggah Foto / Screenshot Bukti Transfer *</label>
                        <input type="file" name="bukti_transfer" class="form-control image-preview-input admin-img-input" data-preview-target="previewBukti" accept="image/*,.pdf" required>
                        <small class="form-text">Format didukung: JPG, PNG, WEBP, PDF (Maksimal 5MB)</small>
                        
                        <div style="margin-top: 12px;">
                            <img id="previewBukti" src="#" alt="Pratinjau Bukti" style="display: none; max-height: 200px; border-radius: 8px; border: 1px solid var(--adm-border);">
                            
                            <?php if (!empty($tagihan['bukti_transfer']) && file_exists('../uploads/bukti/' . $tagihan['bukti_transfer'])): ?>
                                <div style="margin-top: 8px;">
                                    <small style="color: var(--adm-muted);">Bukti Yang Diunggah Sebelumnya:</small><br>
                                    <img src="../uploads/bukti/<?= $tagihan['bukti_transfer'] ?>" alt="Bukti Lama" style="max-height: 140px; border-radius: 6px; margin-top: 4px; border: 1px solid var(--adm-border); cursor:pointer;" onclick="openImageModal('../uploads/bukti/<?= $tagihan['bukti_transfer'] ?>')">
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Catatan Pembayaran (Opsional)</label>
                        <textarea name="catatan_pembayar" class="form-control" rows="2" placeholder="Contoh: Sudah transfer via BCA Mobile an Rian Pratama pk 14:00"><?= htmlspecialchars($tagihan['catatan_pembayar'] ?? '') ?></textarea>
                    </div>

                    <button type="submit" name="btn_upload_bukti" class="btn btn-primary btn-block" style="padding: 12px; margin-top: 14px;">
                        <i class="fa-solid fa-paper-plane"></i> Kirim Bukti Pembayaran
                    </button>
                </form>
            </div>
        </div>
    </div>

</div>

<?php require_once __DIR__ . '/footer.php'; ?>
