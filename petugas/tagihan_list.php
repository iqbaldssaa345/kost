<?php
/**
 * Verifikasi Pembayaran & Bukti Transfer Petugas
 */
require_once __DIR__ . '/../koneksi.php';
require_once __DIR__ . '/../config/helper.php';

// Cek hak akses petugas / admin
auth_check(['petugas', 'admin']);

// 1. PROSES VERIFIKASI POST (Harus sebelum include header.php)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['btn_petugas_verif_tagihan'])) {
    $id_tagihan = (int)$_POST['id_tagihan'];
    $status_bayar = clean($conn, $_POST['status_pembayaran']);
    $catatan = clean($conn, $_POST['catatan_petugas']);
    $id_petugas = (int)$_SESSION['user_id'];
    $now = date('Y-m-d H:i:s');

    $stmt = mysqli_prepare($conn, "UPDATE tbl_tagihan_pembayaran SET status_pembayaran = ?, catatan_petugas = ?, diverifikasi_oleh = ? WHERE id_tagihan = ?");
    mysqli_stmt_bind_param($stmt, "ssii", $status_bayar, $catatan, $id_petugas, $id_tagihan);

    if (mysqli_stmt_execute($stmt)) {
        // Jika tagihan sewa berstatus lunas, otomatis aktifkan pemesanan booking dan kamar
        $q_t = mysqli_query($conn, "SELECT id_booking, id_kamar FROM tbl_tagihan_pembayaran WHERE id_tagihan = $id_tagihan");
        if ($t_data = mysqli_fetch_assoc($q_t)) {
            if ($status_bayar === 'lunas' && !empty($t_data['id_booking'])) {
                $id_b = (int)$t_data['id_booking'];
                $id_k = (int)$t_data['id_kamar'];
                mysqli_query($conn, "UPDATE tbl_booking SET status_booking = 'aktif', diverifikasi_oleh = $id_petugas, tgl_verifikasi = '$now' WHERE id_booking = $id_b");
                mysqli_query($conn, "UPDATE tbl_kamar SET status = 'terisi' WHERE id_kamar = $id_k");
            }
        }
        set_flash('success', "Status pembayaran berhasil diperbarui menjadi '" . ucfirst(str_replace('_', ' ', $status_bayar)) . "'.");
    } else {
        set_flash('danger', "Gagal memverifikasi tagihan: " . mysqli_error($conn));
    }
    header("Location: tagihan_list.php");
    exit();
}

// Filter Status
$filter = isset($_GET['status']) ? clean($conn, $_GET['status']) : '';
$where = "WHERE 1=1";
if (!empty($filter)) {
    $where .= " AND t.status_pembayaran = '$filter'";
}

// Hitung Badge Jumlah per Status
$q_counts = mysqli_query($conn, "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status_pembayaran = 'menunggu_verifikasi' THEN 1 ELSE 0 END) as count_menunggu,
    SUM(CASE WHEN status_pembayaran = 'lunas' THEN 1 ELSE 0 END) as count_lunas,
    SUM(CASE WHEN status_pembayaran = 'belum_bayar' THEN 1 ELSE 0 END) as count_belum,
    SUM(CASE WHEN status_pembayaran = 'ditolak' THEN 1 ELSE 0 END) as count_ditolak
    FROM tbl_tagihan_pembayaran");
$badge_counts = mysqli_fetch_assoc($q_counts);

// Query Data Tagihan
$tagihan_list = mysqli_query($conn, "SELECT t.*, u.nama_lengkap, u.no_hp, u.email, k.nomor_kamar, k.nama_kamar, v.nama_lengkap as verifikator_nama 
                                     FROM tbl_tagihan_pembayaran t 
                                     JOIN tbl_users u ON t.id_user = u.id_user 
                                     JOIN tbl_kamar k ON t.id_kamar = k.id_kamar 
                                     LEFT JOIN tbl_users v ON t.diverifikasi_oleh = v.id_user 
                                     $where ORDER BY (t.status_pembayaran = 'menunggu_verifikasi') DESC, t.id_tagihan DESC");

$page_title = "Verifikasi Pembayaran & Bukti Transfer";
require_once __DIR__ . '/header.php';
?>

<div class="card">
    <div class="card-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 14px;">
        <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
            <h3 class="card-title"><i class="fa-solid fa-money-check-dollar"></i> Pelayanan Verifikasi Pembayaran & Bukti Transfer</h3>
            
            <div style="display: flex; gap: 6px; flex-wrap: wrap;">
                <a href="tagihan_list.php" class="btn btn-sm <?= empty($filter) ? 'btn-primary' : 'btn-outline' ?>">
                    Semua (<?= (int)($badge_counts['total'] ?? 0) ?>)
                </a>
                <a href="tagihan_list.php?status=menunggu_verifikasi" class="btn btn-sm <?= $filter == 'menunggu_verifikasi' ? 'btn-primary' : 'btn-outline' ?>" style="<?= ($badge_counts['count_menunggu'] ?? 0) > 0 && $filter != 'menunggu_verifikasi' ? 'border-color:#f59e0b; color:#d97706;' : '' ?>">
                    Perlu Verifikasi (<?= (int)($badge_counts['count_menunggu'] ?? 0) ?>)
                </a>
                <a href="tagihan_list.php?status=lunas" class="btn btn-sm <?= $filter == 'lunas' ? 'btn-primary' : 'btn-outline' ?>">
                    Lunas (<?= (int)($badge_counts['count_lunas'] ?? 0) ?>)
                </a>
                <a href="tagihan_list.php?status=belum_bayar" class="btn btn-sm <?= $filter == 'belum_bayar' ? 'btn-primary' : 'btn-outline' ?>">
                    Belum Bayar (<?= (int)($badge_counts['count_belum'] ?? 0) ?>)
                </a>
            </div>
        </div>

        <div style="display: flex; gap: 10px; align-items: center;">
            <a href="tagihan_input.php" class="btn btn-primary btn-sm">
                <i class="fa-solid fa-plus-circle"></i> Input Tagihan Baru
            </a>
            <input type="text" class="form-control table-search" data-target-table="petugasTagihanTable" placeholder="Cari invoice / penyewa..." style="width: 200px; padding: 7px 12px;">
        </div>
    </div>

    <div class="card-body" style="padding: 0;">
        <div class="table-responsive">
            <table class="data-table" id="petugasTagihanTable">
                <thead>
                    <tr>
                        <th>Invoice & Tanggal</th>
                        <th>Penghuni & Kontak</th>
                        <th>Rincian Biaya</th>
                        <th>Nominal</th>
                        <th>Status Pembayaran</th>
                        <th>Bukti Transfer</th>
                        <th>Aksi Pelayanan</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($tagihan_list && mysqli_num_rows($tagihan_list) > 0): ?>
                        <?php while ($t = mysqli_fetch_assoc($tagihan_list)): 
                            $statusBadges = [
                                'belum_bayar' => 'badge-danger',
                                'menunggu_verifikasi' => 'badge-warning',
                                'lunas' => 'badge-success',
                                'ditolak' => 'badge-secondary'
                            ][$t['status_pembayaran']] ?? 'badge-secondary';

                            $bukti_path = '../uploads/bukti/' . ($t['bukti_transfer'] ?? '');
                            $has_bukti = (!empty($t['bukti_transfer']) && file_exists($bukti_path));
                        ?>
                            <tr>
                                <td>
                                    <b style="color: var(--adm-primary);"><?= htmlspecialchars($t['kode_tagihan']) ?></b><br>
                                    <small style="color: var(--adm-muted);"><?= tgl_indo($t['created_at']) ?></small>
                                </td>
                                <td>
                                    <b><?= htmlspecialchars($t['nama_lengkap']) ?></b><br>
                                    <span style="font-size: 0.78rem; background: #e0e7ff; color: #4338ca; padding: 2px 6px; border-radius: 4px; font-weight: 700;">
                                        Kamar <?= htmlspecialchars($t['nomor_kamar']) ?>
                                    </span>
                                    <br>
                                    <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $t['no_hp']) ?>?text=Halo%20<?= urlencode($t['nama_lengkap']) ?>,%20mengenai%20tagihan%20kost%20<?= urlencode($t['kode_tagihan']) ?>" target="_blank" style="color: #10b981; font-size: 0.78rem; font-weight: 600;">
                                        <i class="fa-brands fa-whatsapp"></i> Chat WhatsApp
                                    </a>
                                </td>
                                <td>
                                    <b><?= htmlspecialchars($t['judul_tagihan']) ?></b><br>
                                    <small style="color: var(--adm-muted);">Periode: <?= htmlspecialchars($t['periode_bulan']) ?></small>
                                </td>
                                <td>
                                    <b style="color: #059669; font-size: 0.95rem;"><?= format_rupiah($t['nominal']) ?></b>
                                </td>
                                <td>
                                    <span class="badge <?= $statusBadges ?>"><?= str_replace('_', ' ', ucfirst($t['status_pembayaran'])) ?></span>
                                    <?php if (!empty($t['verifikator_nama'])): ?>
                                        <br><small style="font-size: 0.72rem; color: var(--adm-muted);">Verif: <?= htmlspecialchars($t['verifikator_nama']) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($has_bukti): ?>
                                        <button type="button" class="btn btn-outline btn-sm" onclick="openImageModal('<?= $bukti_path ?>')" style="color: #4f46e5; border-color: #cbd5e1;">
                                            <i class="fa-solid fa-image"></i> Pratinjau Foto
                                        </button>
                                    <?php elseif (!empty($t['bukti_transfer'])): ?>
                                        <span style="color: var(--adm-muted); font-size: 0.78rem;"><i class="fa-solid fa-file"></i> <?= htmlspecialchars($t['bukti_transfer']) ?></span>
                                    <?php else: ?>
                                        <span style="color: #94a3b8; font-size: 0.8rem;">Belum Diunggah</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div style="display: flex; gap: 6px;">
                                        <button type="button" class="btn btn-primary btn-sm" onclick='modalPetugasTagihan(<?= json_encode($t, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>)' title="Verifikasi">
                                            <i class="fa-solid fa-check-double"></i> Verif
                                        </button>
                                        <a href="../user/invoice.php?id=<?= $t['id_tagihan'] ?>" target="_blank" class="btn btn-outline btn-sm" title="Lihat Kuitansi">
                                            <i class="fa-solid fa-print"></i> Kuitansi
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="7" style="text-align: center; color: var(--adm-muted); padding: 24px;">Tidak ada data tagihan pembayaran pada kategori ini.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- MODAL VERIFIKASI TAGIHAN PETUGAS -->
<div class="modal-overlay" id="modalTagihanPetugas">
    <div class="modal-box">
        <form action="tagihan_list.php" method="POST">
            <input type="hidden" name="id_tagihan" id="tp_id_tagihan">

            <div class="modal-header">
                <h3 style="font-size: 1.1rem; font-weight: 700;"><i class="fa-solid fa-check-to-slot"></i> Verifikasi Pembayaran</h3>
                <button type="button" class="btn btn-outline btn-sm" onclick="closeModal('modalTagihanPetugas')">&times;</button>
            </div>
            <div class="modal-body">
                <div style="background: var(--adm-bg); padding: 14px; border-radius: var(--radius-sm); margin-bottom: 16px; border: 1px solid var(--adm-border);">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 4px;">
                        <span style="color: var(--adm-muted); font-size: 0.85rem;">Invoice:</span>
                        <b id="tp_kode" style="color: var(--adm-primary);"></b>
                    </div>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 4px;">
                        <span style="color: var(--adm-muted); font-size: 0.85rem;">Penyewa:</span>
                        <b id="tp_nama"></b>
                    </div>
                    <div style="display: flex; justify-content: space-between;">
                        <span style="color: var(--adm-muted); font-size: 0.85rem;">Total Nominal:</span>
                        <b id="tp_nominal" style="color: #059669; font-size: 1.05rem;"></b>
                    </div>
                </div>

                <div class="form-group">
                    <label>Pilih Hasil Verifikasi Status *</label>
                    <select name="status_pembayaran" id="tp_status" class="form-control" required>
                        <option value="lunas">Lunas (Uang Transfer / Tunai Masuk Sesuai)</option>
                        <option value="menunggu_verifikasi">Menunggu Verifikasi</option>
                        <option value="ditolak">Tolak (Bukti Transfer Palsu / Tidak Masuk)</option>
                        <option value="belum_bayar">Belum Bayar</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Catatan Petugas (Opsional)</label>
                    <textarea name="catatan_petugas" id="tp_catatan" class="form-control" rows="2" placeholder="Contoh: Mutasi BCA masuk an Rian Pratama pk 14.30 WIB"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal('modalTagihanPetugas')">Batal</button>
                <button type="submit" name="btn_petugas_verif_tagihan" class="btn btn-success">
                    <i class="fa-solid fa-check"></i> Simpan Hasil Verifikasi
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function modalPetugasTagihan(t) {
    document.getElementById('tp_id_tagihan').value = t.id_tagihan;
    document.getElementById('tp_kode').innerText = t.kode_tagihan;
    document.getElementById('tp_nama').innerText = t.nama_lengkap + ' (Kamar ' + t.nomor_kamar + ')';
    document.getElementById('tp_nominal').innerText = 'Rp ' + Number(t.nominal).toLocaleString('id-ID');
    document.getElementById('tp_status').value = t.status_pembayaran;
    document.getElementById('tp_catatan').value = t.catatan_petugas || '';
    openModal('modalTagihanPetugas');
}
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
