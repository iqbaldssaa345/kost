<?php
/**
 * Layanan & Komplain Fasilitas (Pengunjung/Penyewa)
 */
require_once __DIR__ . '/../koneksi.php';
require_once __DIR__ . '/../config/helper.php';

auth_check();

$id_user = (int)$_SESSION['user_id'];

// Ambil kamar aktif user
$q_aktif = mysqli_query($conn, "SELECT id_kamar FROM tbl_booking WHERE id_user = $id_user AND status_booking = 'aktif' LIMIT 1");
$kamar_data = mysqli_fetch_assoc($q_aktif);
$id_kamar_aktif = $kamar_data['id_kamar'] ?? 0;

$error = '';

// 1. PROSES KIRIM KOMPLAIN BARU (Sebelum header.php)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['btn_kirim_komplain'])) {
    $judul = clean($conn, $_POST['judul_keluhan']);
    $deskripsi = clean($conn, $_POST['deskripsi']);
    $id_k = (int)$_POST['id_kamar'];

    if ($id_k <= 0) {
        $error = "Anda harus memiliki kamar sewa aktif untuk dapat mengajukan keluhan fasilitas.";
    } elseif (empty($judul) || empty($deskripsi)) {
        $error = "Silakan lengkapi judul keluhan dan rincian masalah fasilitas Anda.";
    } else {
        $stmt = mysqli_prepare($conn, "INSERT INTO tbl_pengaduan_layanan (id_user, id_kamar, judul_keluhan, deskripsi, status) VALUES (?, ?, ?, ?, 'menunggu')");
        mysqli_stmt_bind_param($stmt, "iiss", $id_user, $id_k, $judul, $deskripsi);

        if (mysqli_stmt_execute($stmt)) {
            set_flash('success', 'Laporan keluhan fasilitas Anda berhasil dikirim ke petugas kost.');
            header("Location: komplain.php");
            exit();
        } else {
            $error = "Gagal mengirim keluhan: " . mysqli_error($conn);
        }
    }
}

// Ambil riwayat komplain user
$my_komplain = mysqli_query($conn, "SELECT p.*, k.nomor_kamar 
                                    FROM tbl_pengaduan_layanan p 
                                    JOIN tbl_kamar k ON p.id_kamar = k.id_kamar 
                                    WHERE p.id_user = $id_user 
                                    ORDER BY p.id_pengaduan DESC");

$page_title = "Layanan & Komplain Fasilitas";
require_once __DIR__ . '/header.php';
?>

<div style="display: grid; grid-template-columns: 0.8fr 1.2fr; gap: 24px; align-items: start;">
    
    <!-- FORM PENGAJUAN KOMPLAIN -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><i class="fa-solid fa-pen"></i> Ajukan Keluhan / Layanan</h3>
        </div>
        <div class="card-body">
            
            <?php if (!empty($error)): ?>
                <div class="alert-box danger"><?= $error ?></div>
            <?php endif; ?>

            <?php if ($id_kamar_aktif > 0): ?>
                <form action="komplain.php" method="POST">
                    <input type="hidden" name="id_kamar" value="<?= $id_kamar_aktif ?>">

                    <div class="form-group">
                        <label>Judul Masalah / Fasilitas Rusak *</label>
                        <input type="text" name="judul_keluhan" class="form-control" required placeholder="Contoh: AC kamar tidak dingin, Kran shower bocor">
                    </div>

                    <div class="form-group">
                        <label>Deskripsi Kendala *</label>
                        <textarea name="deskripsi" class="form-control" rows="4" required placeholder="Jelaskan secara detail masalah yang dialami di kamar Anda..."></textarea>
                    </div>

                    <button type="submit" name="btn_kirim_komplain" class="btn btn-primary btn-block" style="padding: 12px;">
                        <i class="fa-solid fa-paper-plane"></i> Kirim Laporan ke Petugas
                    </button>
                </form>
            <?php else: ?>
                <div class="alert-box info">
                    <i class="fa-solid fa-info-circle"></i> Anda belum menempati kamar kost aktif untuk dapat mengajukan keluhan fasilitas kamar.
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- RIWAYAT KOMPLAIN -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><i class="fa-solid fa-clock-rotate-left"></i> Riwayat Pengaduan & Tanggapan</h3>
        </div>
        <div class="card-body" style="padding: 0;">
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Keluhan</th>
                            <th>Status</th>
                            <th>Tanggapan Petugas</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($my_komplain && mysqli_num_rows($my_komplain) > 0): ?>
                            <?php while ($k = mysqli_fetch_assoc($my_komplain)): 
                                $badge = [
                                    'menunggu' => 'badge-danger',
                                    'diproses' => 'badge-warning',
                                    'selesai' => 'badge-success',
                                    'ditolak' => 'badge-secondary'
                                ] [$k['status']] ?? 'badge-secondary';
                            ?>
                                <tr>
                                    <td><small><?= tgl_indo($k['tgl_lapor']) ?></small></td>
                                    <td>
                                        <b><?= htmlspecialchars($k['judul_keluhan']) ?></b><br>
                                        <small style="color: var(--adm-muted);"><?= nl2br(htmlspecialchars($k['deskripsi'])) ?></small>
                                    </td>
                                    <td><span class="badge <?= $badge ?>"><?= ucfirst($k['status']) ?></span></td>
                                    <td>
                                        <small><?= !empty($k['respon_petugas']) ? htmlspecialchars($k['respon_petugas']) : '<i style="color:#94a3b8;">Menunggu respon petugas</i>' ?></small>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="4" style="text-align: center; color: var(--adm-muted);">Belum ada laporan keluhan yang diajukan.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<?php require_once __DIR__ . '/footer.php'; ?>
