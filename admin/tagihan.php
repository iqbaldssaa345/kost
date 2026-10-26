<?php
/**
 * Data Tagihan & Pembayaran (Admin)
 */
require_once __DIR__ . '/../koneksi.php';
require_once __DIR__ . '/../config/helper.php';

auth_check(['admin']);

// 1. PROSES VERIFIKASI PEMBAYARAN (Sebelum header.php)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['btn_verifikasi_tagihan'])) {
    $id_tagihan = (int)$_POST['id_tagihan'];
    $status_bayar = clean($conn, $_POST['status_pembayaran']);
    $catatan_petugas = clean($conn, $_POST['catatan_petugas']);
    $id_admin = (int)$_SESSION['user_id'];
    $now = date('Y-m-d H:i:s');

    $stmt = mysqli_prepare($conn, "UPDATE tbl_tagihan_pembayaran SET status_pembayaran = ?, catatan_petugas = ?, diverifikasi_oleh = ? WHERE id_tagihan = ?");
    mysqli_stmt_bind_param($stmt, "ssii", $status_bayar, $catatan_petugas, $id_admin, $id_tagihan);

    if (mysqli_stmt_execute($stmt)) {
        // Jika tagihan terkait booking dan status menjadi lunas, pastikan status booking disetujui / aktif
        $q_t = mysqli_query($conn, "SELECT id_booking, id_kamar FROM tbl_tagihan_pembayaran WHERE id_tagihan = $id_tagihan");
        if ($t_data = mysqli_fetch_assoc($q_t)) {
            if ($status_bayar === 'lunas' && !empty($t_data['id_booking'])) {
                $id_b = (int)$t_data['id_booking'];
                $id_k = (int)$t_data['id_kamar'];
                mysqli_query($conn, "UPDATE tbl_booking SET status_booking = 'aktif', diverifikasi_oleh = $id_admin, tgl_verifikasi = '$now' WHERE id_booking = $id_b");
                mysqli_query($conn, "UPDATE tbl_kamar SET status = 'terisi' WHERE id_kamar = $id_k");
            }
        }

        set_flash('success', "Status tagihan berhasil diperbarui menjadi '" . ucfirst(str_replace('_', ' ', $status_bayar)) . "'.");
    } else {
        set_flash('danger', "Gagal memverifikasi tagihan: " . mysqli_error($conn));
    }
    header("Location: tagihan.php");
    exit();
}

// 2. PROSES TAMBAH TAGIHAN BARU MANUAL
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['btn_tambah_tagihan'])) {
    $id_user = (int)$_POST['id_user'];
    $id_kamar = (int)$_POST['id_kamar'];
    $judul = clean($conn, $_POST['judul_tagihan']);
    $jenis = clean($conn, $_POST['jenis_biaya']);
    $periode = clean($conn, $_POST['periode_bulan']);
    $nominal = (float)$_POST['nominal'];
    $jatuh_tempo = clean($conn, $_POST['tgl_jatuh_tempo']);
    $kode = generate_kode('INV');

    $stmt = mysqli_prepare($conn, "INSERT INTO tbl_tagihan_pembayaran (kode_tagihan, id_user, id_kamar, judul_tagihan, jenis_biaya, periode_bulan, nominal, tgl_jatuh_tempo, status_pembayaran) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'belum_bayar')");
    mysqli_stmt_bind_param($stmt, "siisssds", $kode, $id_user, $id_kamar, $judul, $jenis, $periode, $nominal, $jatuh_tempo);

    if (mysqli_stmt_execute($stmt)) {
        set_flash('success', "Tagihan baru senilai " . format_rupiah($nominal) . " berhasil dibuat untuk penghuni.");
    } else {
        set_flash('danger', "Gagal membuat tagihan: " . mysqli_error($conn));
    }
    header("Location: tagihan.php");
    exit();
}

// Filter Status
$filter_status = isset($_GET['status']) ? clean($conn, $_GET['status']) : '';
$where = "WHERE 1=1";
if (!empty($filter_status)) {
    $where .= " AND t.status_pembayaran = '$filter_status'";
}

$page_title = "Data Tagihan & Pembayaran";
require_once __DIR__ . '/header.php';

$tagihan_list = mysqli_query($conn, "SELECT t.*, u.nama_lengkap, u.no_hp, k.nomor_kamar, k.nama_kamar, v.nama_lengkap as verifikator_nama 
                                     FROM tbl_tagihan_pembayaran t 
                                     JOIN tbl_users u ON t.id_user = u.id_user 
                                     JOIN tbl_kamar k ON t.id_kamar = k.id_kamar 
                                     LEFT JOIN tbl_users v ON t.diverifikasi_oleh = v.id_user 
                                     $where ORDER BY (t.status_pembayaran = 'menunggu_verifikasi') DESC, t.id_tagihan DESC");

// Ambil list user dan kamar untuk form tambah tagihan
$penghuni_list = mysqli_query($conn, "SELECT u.id_user, u.nama_lengkap, k.id_kamar, k.nomor_kamar 
                                      FROM tbl_users u 
                                      JOIN tbl_booking b ON u.id_user = b.id_user 
                                      JOIN tbl_kamar k ON b.id_kamar = k.id_kamar 
                                      WHERE b.status_booking = 'aktif' GROUP BY u.id_user");
?>

<div class="card">
    <div class="card-header" style="flex-wrap: wrap; gap: 14px;">
        <div style="display: flex; align-items: center; gap: 12px; flex: 1;">
            <h3 class="card-title"><i class="fa-solid fa-receipt"></i> Monitoring Seluruh Tagihan & Pembayaran</h3>
            <div style="display: flex; gap: 6px;">
                <a href="tagihan.php" class="btn btn-sm <?= empty($filter_status) ? 'btn-primary' : 'btn-outline' ?>">Semua</a>
                <a href="tagihan.php?status=menunggu_verifikasi" class="btn btn-sm <?= $filter_status == 'menunggu_verifikasi' ? 'btn-primary' : 'btn-outline' ?>">
                    Perlu Verifikasi
                </a>
                <a href="tagihan.php?status=lunas" class="btn btn-sm <?= $filter_status == 'lunas' ? 'btn-primary' : 'btn-outline' ?>">Lunas</a>
                <a href="tagihan.php?status=belum_bayar" class="btn btn-sm <?= $filter_status == 'belum_bayar' ? 'btn-primary' : 'btn-outline' ?>">Belum Bayar</a>
            </div>
        </div>

        <div style="display: flex; gap: 10px; align-items: center;">
            <button type="button" class="btn btn-primary btn-sm" onclick="openModal('modalTambahTagihan')">
                <i class="fa-solid fa-file-invoice-dollar"></i> Buat Tagihan Baru
            </button>
            <input type="text" class="form-control table-search" data-target-table="tagihanTable" placeholder="Cari tagihan/penyewa..." style="width: 200px; padding: 7px 12px;">
        </div>
    </div>

    <div class="card-body" style="padding: 0;">
        <div class="table-responsive">
            <table class="data-table" id="tagihanTable">
                <thead>
                    <tr>
                        <th>Kode Invoice</th>
                        <th>Penyewa & Kamar</th>
                        <th>Rincian Tagihan</th>
                        <th>Nominal</th>
                        <th>Jatuh Tempo</th>
                        <th>Status</th>
                        <th>Bukti Transfer</th>
                        <th>Aksi</th>
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

                            $bukti_path = '../uploads/bukti/' . $t['bukti_transfer'];
                            $has_bukti = (!empty($t['bukti_transfer']) && file_exists($bukti_path));
                        ?>
                            <tr>
                                <td>
                                    <b style="color: var(--adm-primary); font-size: 0.85rem;"><?= htmlspecialchars($t['kode_tagihan']) ?></b><br>
                                    <small style="color: var(--adm-muted);"><?= tgl_indo($t['created_at']) ?></small>
                                </td>
                                <td>
                                    <b><?= htmlspecialchars($t['nama_lengkap']) ?></b><br>
                                    <span style="font-size: 0.78rem; background: #e0e7ff; color: #4338ca; padding: 1px 6px; border-radius: 4px; font-weight: 600;">
                                        Kamar <?= htmlspecialchars($t['nomor_kamar']) ?>
                                    </span>
                                </td>
                                <td>
                                    <b style="font-size: 0.88rem;"><?= htmlspecialchars($t['judul_tagihan']) ?></b><br>
                                    <small style="color: var(--adm-muted);">Periode: <?= htmlspecialchars($t['periode_bulan']) ?></small>
                                </td>
                                <td>
                                    <b style="color: #059669; font-size: 0.95rem;"><?= format_rupiah($t['nominal']) ?></b>
                                </td>
                                <td>
                                    <span><?= tgl_indo($t['tgl_jatuh_tempo']) ?></span>
                                </td>
                                <td>
                                    <span class="badge <?= $statusBadges ?>"><?= str_replace('_', ' ', ucfirst($t['status_pembayaran'])) ?></span>
                                    <?php if (!empty($t['verifikator_nama'])): ?>
                                        <br><small style="font-size: 0.72rem; color: var(--adm-muted);">Verif: <?= htmlspecialchars($t['verifikator_nama']) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($has_bukti): ?>
                                        <button type="button" class="btn btn-outline btn-sm" onclick="openImageModal('<?= $bukti_path ?>')" style="color: var(--adm-primary);">
                                            <i class="fa-solid fa-image"></i> Lihat Bukti
                                        </button>
                                    <?php elseif (!empty($t['bukti_transfer'])): ?>
                                        <span style="font-size: 0.75rem; color: var(--adm-muted);"><i class="fa-solid fa-file"></i> <?= htmlspecialchars($t['bukti_transfer']) ?></span>
                                    <?php else: ?>
                                        <span style="color: #94a3b8; font-size: 0.8rem;">Belum Upload</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div style="display: flex; gap: 6px;">
                                        <button type="button" class="btn btn-primary btn-sm" onclick='modalVerif(<?= json_encode($t) ?>)' title="Verifikasi">
                                            <i class="fa-solid fa-check-double"></i> Verifikasi
                                        </button>
                                        <a href="../user/invoice.php?id=<?= $t['id_tagihan'] ?>" target="_blank" class="btn btn-outline btn-sm" title="Cetak Kuitansi">
                                            <i class="fa-solid fa-print"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="8" style="text-align: center; color: var(--adm-muted);">Tidak ada data tagihan ditemukan.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- MODAL VERIFIKASI PEMBAYARAN -->
<div class="modal-overlay" id="modalVerifikasiTagihan">
    <div class="modal-box">
        <form action="tagihan.php" method="POST">
            <input type="hidden" name="id_tagihan" id="verif_id_tagihan">
            
            <div class="modal-header">
                <h3 style="font-size: 1.1rem; font-weight: 700;">Verifikasi Pembayaran Tagihan</h3>
                <button type="button" class="btn btn-outline btn-sm" onclick="closeModal('modalVerifikasiTagihan')">&times;</button>
            </div>
            <div class="modal-body">
                <div style="background: var(--adm-bg); border-radius: var(--radius-sm); padding: 14px; margin-bottom: 16px;">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 4px;">
                        <span style="color: var(--adm-muted); font-size: 0.85rem;">Invoice:</span>
                        <b id="verif_kode_tagihan" style="color: var(--adm-primary);"></b>
                    </div>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 4px;">
                        <span style="color: var(--adm-muted); font-size: 0.85rem;">Penyewa:</span>
                        <b id="verif_penyewa"></b>
                    </div>
                    <div style="display: flex; justify-content: space-between;">
                        <span style="color: var(--adm-muted); font-size: 0.85rem;">Nominal:</span>
                        <b id="verif_nominal" style="color: #059669; font-size: 1.05rem;"></b>
                    </div>
                </div>

                <div class="form-group">
                    <label>Pilih Status Pembayaran *</label>
                    <select name="status_pembayaran" id="verif_status_select" class="form-control" required>
                        <option value="lunas">Lunas (Uang Diterima & Valid)</option>
                        <option value="menunggu_verifikasi">Menunggu Verifikasi</option>
                        <option value="ditolak">Ditolak (Bukti Palsu / Tidak Sesuai)</option>
                        <option value="belum_bayar">Belum Bayar</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Catatan Petugas / Admin (Opsional)</label>
                    <textarea name="catatan_petugas" id="verif_catatan" class="form-control" rows="2" placeholder="Contoh: Transfer BCA diterima an Ahmad"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal('modalVerifikasiTagihan')">Batal</button>
                <button type="submit" name="btn_verifikasi_tagihan" class="btn btn-success"><i class="fa-solid fa-check"></i> Simpan Hasil Verifikasi</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL TAMBAH TAGIHAN MANUAL -->
<div class="modal-overlay" id="modalTambahTagihan">
    <div class="modal-box">
        <form action="tagihan.php" method="POST">
            <div class="modal-header">
                <h3 style="font-size: 1.1rem; font-weight: 700;">Buat Tagihan Manual</h3>
                <button type="button" class="btn btn-outline btn-sm" onclick="closeModal('modalTambahTagihan')">&times;</button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label>Pilih Penghuni Kost *</label>
                    <select name="user_kamar" class="form-control" required onchange="setTenantData(this)">
                        <option value="">-- Pilih Penghuni Aktif --</option>
                        <?php if ($penghuni_list && mysqli_num_rows($penghuni_list) > 0): ?>
                            <?php while ($p = mysqli_fetch_assoc($penghuni_list)): ?>
                                <option value="<?= $p['id_user'] ?>" data-kamar="<?= $p['id_kamar'] ?>">
                                    <?= htmlspecialchars($p['nama_lengkap']) ?> (Kamar <?= htmlspecialchars($p['nomor_kamar']) ?>)
                                </option>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </select>
                    <input type="hidden" name="id_user" id="form_id_user">
                    <input type="hidden" name="id_kamar" id="form_id_kamar">
                </div>

                <div class="form-group">
                    <label>Judul / Rincian Tagihan *</label>
                    <input type="text" name="judul_tagihan" class="form-control" required placeholder="Contoh: Iuran Listrik & Air Bulan September">
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Jenis Biaya *</label>
                        <select name="jenis_biaya" class="form-control" required>
                            <option value="sewa_kost">Sewa Kost Bulanan</option>
                            <option value="listrik">Tagihan Listrik / Tambahan Watt</option>
                            <option value="kebersihan">Iuran Kebersihan / Sampah</option>
                            <option value="denda">Denda Keterlambatan / Kerusakan</option>
                            <option value="lainnya">Lainnya</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Periode Bulan *</label>
                        <input type="text" name="periode_bulan" class="form-control" required placeholder="September 2026" value="<?= date('F Y') ?>">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Nominal Tagihan (Rp) *</label>
                        <input type="number" name="nominal" class="form-control" required min="0" step="5000" placeholder="50000">
                    </div>

                    <div class="form-group">
                        <label>Tanggal Jatuh Tempo *</label>
                        <input type="date" name="tgl_jatuh_tempo" class="form-control" required value="<?= date('Y-m-d', strtotime('+7 days')) ?>">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal('modalTambahTagihan')">Batal</button>
                <button type="submit" name="btn_tambah_tagihan" class="btn btn-primary"><i class="fa-solid fa-plus"></i> Simpan Tagihan</button>
            </div>
        </form>
    </div>
</div>

<script>
function modalVerif(t) {
    document.getElementById('verif_id_tagihan').value = t.id_tagihan;
    document.getElementById('verif_kode_tagihan').innerText = t.kode_tagihan;
    document.getElementById('verif_penyewa').innerText = t.nama_lengkap + ' (' + t.nomor_kamar + ')';
    document.getElementById('verif_nominal').innerText = 'Rp ' + Number(t.nominal).toLocaleString('id-ID');
    document.getElementById('verif_status_select').value = t.status_pembayaran;
    document.getElementById('verif_catatan').value = t.catatan_petugas || '';
    openModal('modalVerifikasiTagihan');
}

function setTenantData(selectElem) {
    const selectedOption = selectElem.options[selectElem.selectedIndex];
    document.getElementById('form_id_user').value = selectElem.value;
    document.getElementById('form_id_kamar').value = selectedOption.dataset.kamar || '';
}
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
