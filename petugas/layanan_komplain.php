<?php
/**
 * Layanan & Penanganan Keluhan Fasilitas (Petugas)
 */
require_once __DIR__ . '/../koneksi.php';
require_once __DIR__ . '/../config/helper.php';

auth_check(['petugas', 'admin']);

// 1. PROSES POST HARUS SEBELUM HEADER.PHP
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['btn_respon_komplain'])) {
    $id_pengaduan = (int)$_POST['id_pengaduan'];
    $status = clean($conn, $_POST['status']);
    $respon = clean($conn, $_POST['respon_petugas']);
    $now = ($status === 'selesai') ? date('Y-m-d H:i:s') : null;

    if ($now) {
        $stmt = mysqli_prepare($conn, "UPDATE tbl_pengaduan_layanan SET status=?, respon_petugas=?, tgl_selesai=? WHERE id_pengaduan=?");
        mysqli_stmt_bind_param($stmt, "sssi", $status, $respon, $now, $id_pengaduan);
    } else {
        $stmt = mysqli_prepare($conn, "UPDATE tbl_pengaduan_layanan SET status=?, respon_petugas=? WHERE id_pengaduan=?");
        mysqli_stmt_bind_param($stmt, "ssi", $status, $respon, $id_pengaduan);
    }

    if (mysqli_stmt_execute($stmt)) {
        set_flash('success', 'Tanggapan dan status penanganan keluhan berhasil disimpan.');
    } else {
        set_flash('danger', 'Gagal menyimpan tanggapan: ' . mysqli_error($conn));
    }
    header("Location: layanan_komplain.php");
    exit();
}

$komplain_list = mysqli_query($conn, "SELECT p.*, u.nama_lengkap, u.no_hp, k.nomor_kamar, k.nama_kamar 
                                      FROM tbl_pengaduan_layanan p 
                                      JOIN tbl_users u ON p.id_user = u.id_user 
                                      JOIN tbl_kamar k ON p.id_kamar = k.id_kamar 
                                      ORDER BY (p.status = 'menunggu') DESC, p.id_pengaduan DESC");

$page_title = "Layanan & Penanganan Keluhan";
require_once __DIR__ . '/header.php';
?>

<div class="card">
    <div class="card-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
        <h3 class="card-title"><i class="fa-solid fa-screwdriver-wrench"></i> Daftar Pengaduan & Layanan Penghuni</h3>
        <input type="text" class="form-control table-search" data-target-table="komplainTable" placeholder="Cari keluhan / nama penghuni..." style="width: 240px; padding: 7px 12px;">
    </div>
    <div class="card-body" style="padding: 0;">
        <div class="table-responsive">
            <table class="data-table" id="komplainTable">
                <thead>
                    <tr>
                        <th>Tanggal Lapor</th>
                        <th>Penghuni & Kamar</th>
                        <th>Keluhan Fasilitas</th>
                        <th>Status</th>
                        <th>Tanggapan Petugas</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($komplain_list && mysqli_num_rows($komplain_list) > 0): ?>
                        <?php while ($k = mysqli_fetch_assoc($komplain_list)): 
                            $badge = [
                                'menunggu' => 'badge-danger',
                                'diproses' => 'badge-warning',
                                'selesai' => 'badge-success',
                                'ditolak' => 'badge-secondary'
                            ][$k['status']] ?? 'badge-secondary';
                        ?>
                            <tr>
                                <td><?= tgl_jam_indo($k['tgl_lapor']) ?></td>
                                <td>
                                    <b><?= htmlspecialchars($k['nama_lengkap']) ?></b><br>
                                    <span style="font-size: 0.78rem; background: #e0e7ff; color: #4338ca; padding: 2px 6px; border-radius: 4px; font-weight: 700;">
                                        Kamar <?= htmlspecialchars($k['nomor_kamar']) ?>
                                    </span>
                                </td>
                                <td>
                                    <b style="color: var(--adm-text);"><?= htmlspecialchars($k['judul_keluhan']) ?></b><br>
                                    <small style="color: var(--adm-muted);"><?= nl2br(htmlspecialchars($k['deskripsi'])) ?></small>
                                </td>
                                <td><span class="badge <?= $badge ?>"><?= ucfirst($k['status']) ?></span></td>
                                <td>
                                    <small><?= !empty($k['respon_petugas']) ? htmlspecialchars($k['respon_petugas']) : '<i style="color:#94a3b8;">Belum ada tanggapan</i>' ?></small>
                                </td>
                                <td>
                                    <button type="button" class="btn btn-primary btn-sm" onclick='modalRespon(<?= json_encode($k, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>)'>
                                        <i class="fa-solid fa-reply"></i> Respon
                                    </button>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="6" style="text-align: center; color: var(--adm-muted); padding: 24px;">Tidak ada laporan pengaduan fasilitas.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- MODAL RESPON KOMPLAIN -->
<div class="modal-overlay" id="modalResponKomplain">
    <div class="modal-box">
        <form action="layanan_komplain.php" method="POST">
            <input type="hidden" name="id_pengaduan" id="rk_id">

            <div class="modal-header">
                <h3 style="font-size: 1.1rem; font-weight: 700;"><i class="fa-solid fa-wrench"></i> Tanggapan Layanan & Komplain</h3>
                <button type="button" class="btn btn-outline btn-sm" onclick="closeModal('modalResponKomplain')">&times;</button>
            </div>
            <div class="modal-body">
                <div style="background: var(--adm-bg); padding: 12px; border-radius: var(--radius-sm); margin-bottom: 14px; border: 1px solid var(--adm-border);">
                    <div style="margin-bottom: 4px;">Penghuni: <b id="rk_nama"></b></div>
                    <div>Keluhan: <b id="rk_judul" style="color: var(--adm-primary);"></b></div>
                </div>

                <div class="form-group">
                    <label>Status Penanganan *</label>
                    <select name="status" id="rk_status" class="form-control" required>
                        <option value="diproses">Sedang Diproses (Petugas menuju lokasi / beli alat)</option>
                        <option value="selesai">Selesai (Perbaikan telah tuntas)</option>
                        <option value="menunggu">Menunggu</option>
                        <option value="ditolak">Ditolak</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Tanggapan / Solusi Petugas *</label>
                    <textarea name="respon_petugas" id="rk_respon" class="form-control" rows="3" required placeholder="Tuliskan tindakan atau perbaikan yang telah dilakukan..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal('modalResponKomplain')">Batal</button>
                <button type="submit" name="btn_respon_komplain" class="btn btn-primary">
                    <i class="fa-solid fa-check"></i> Simpan Tanggapan
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function modalRespon(k) {
    document.getElementById('rk_id').value = k.id_pengaduan;
    document.getElementById('rk_nama').innerText = k.nama_lengkap + ' (Kamar ' + k.nomor_kamar + ')';
    document.getElementById('rk_judul').innerText = k.judul_keluhan;
    document.getElementById('rk_status').value = k.status;
    document.getElementById('rk_respon').value = k.respon_petugas || '';
    openModal('modalResponKomplain');
}
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
