<?php
/**
 * Verifikasi Pemesanan Pengunjung (Petugas)
 */
require_once __DIR__ . '/../koneksi.php';
require_once __DIR__ . '/../config/helper.php';

auth_check(['petugas', 'admin']);

// 1. PROSES POST SEBELUM HEADER.PHP
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['btn_proses_booking'])) {
    $id_booking = (int)$_POST['id_booking'];
    $status_baru = clean($conn, $_POST['status_booking']);
    $id_kamar = (int)$_POST['id_kamar'];
    $id_petugas = (int)$_SESSION['user_id'];
    $now = date('Y-m-d H:i:s');

    $stmt = mysqli_prepare($conn, "UPDATE tbl_booking SET status_booking = ?, diverifikasi_oleh = ?, tgl_verifikasi = ? WHERE id_booking = ?");
    mysqli_stmt_bind_param($stmt, "sisi", $status_baru, $id_petugas, $now, $id_booking);

    if (mysqli_stmt_execute($stmt)) {
        if ($status_baru === 'aktif') {
            mysqli_query($conn, "UPDATE tbl_kamar SET status = 'terisi' WHERE id_kamar = $id_kamar");
        } elseif (in_array($status_baru, ['selesai', 'ditolak', 'dibatalkan'])) {
            mysqli_query($conn, "UPDATE tbl_kamar SET status = 'tersedia' WHERE id_kamar = $id_kamar");
        }
        set_flash('success', "Pemesanan berhasil diperbarui menjadi status '" . ucfirst($status_baru) . "'.");
    } else {
        set_flash('danger', "Gagal memproses booking: " . mysqli_error($conn));
    }
    header("Location: booking_verifikasi.php");
    exit();
}

$booking_list = mysqli_query($conn, "SELECT b.*, u.nama_lengkap, u.no_hp, u.email, u.pekerjaan, k.nomor_kamar, k.nama_kamar, k.harga_per_bulan 
                                     FROM tbl_booking b 
                                     JOIN tbl_users u ON b.id_user = u.id_user 
                                     JOIN tbl_kamar k ON b.id_kamar = k.id_kamar 
                                     ORDER BY (b.status_booking = 'pending') DESC, b.id_booking DESC");

$page_title = "Verifikasi Pemesanan Pengunjung";
require_once __DIR__ . '/header.php';
?>

<div class="card">
    <div class="card-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
        <h3 class="card-title"><i class="fa-solid fa-user-check"></i> Pelayanan Verifikasi Pemesanan Kamar</h3>
        <input type="text" class="form-control table-search" data-target-table="verifBookingTable" placeholder="Cari nama / kode booking..." style="width: 240px; padding: 7px 12px;">
    </div>

    <div class="card-body" style="padding: 0;">
        <div class="table-responsive">
            <table class="data-table" id="verifBookingTable">
                <thead>
                    <tr>
                        <th>Kode & Tanggal</th>
                        <th>Calon Penghuni</th>
                        <th>Pilihan Kamar</th>
                        <th>Durasi Sewa</th>
                        <th>Total Biaya</th>
                        <th>Status</th>
                        <th>Aksi Petugas</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($booking_list && mysqli_num_rows($booking_list) > 0): ?>
                        <?php while ($b = mysqli_fetch_assoc($booking_list)): 
                            $statusBadge = [
                                'pending' => 'badge-warning',
                                'disetujui' => 'badge-info',
                                'aktif' => 'badge-success',
                                'selesai' => 'badge-secondary',
                                'ditolak' => 'badge-danger',
                                'dibatalkan' => 'badge-secondary'
                            ][$b['status_booking']] ?? 'badge-secondary';
                        ?>
                            <tr>
                                <td>
                                    <b style="color: var(--adm-primary);"><?= htmlspecialchars($b['kode_booking']) ?></b><br>
                                    <small style="color: var(--adm-muted);"><?= tgl_indo($b['tgl_booking']) ?></small>
                                </td>
                                <td>
                                    <b><?= htmlspecialchars($b['nama_lengkap']) ?></b><br>
                                    <small style="color: var(--adm-muted);"><?= htmlspecialchars($b['pekerjaan']) ?></small><br>
                                    <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $b['no_hp']) ?>?text=Halo%20<?= urlencode($b['nama_lengkap']) ?>,%20mengenai%20booking%20kost%20<?= urlencode($b['kode_booking']) ?>" target="_blank" style="color: #10b981; font-size: 0.8rem; font-weight: 600;">
                                        <i class="fa-brands fa-whatsapp"></i> <?= htmlspecialchars($b['no_hp']) ?>
                                    </a>
                                </td>
                                <td>
                                    <b>Kamar <?= htmlspecialchars($b['nomor_kamar']) ?></b><br>
                                    <small style="color: var(--adm-muted);"><?= htmlspecialchars($b['nama_kamar']) ?></small>
                                </td>
                                <td>
                                    <b><?= $b['durasi_bulan'] ?> Bulan</b><br>
                                    <small style="color: var(--adm-muted);"><?= tgl_indo($b['tgl_mulai_sewa']) ?> s/d <?= tgl_indo($b['tgl_selesai_sewa']) ?></small>
                                </td>
                                <td><b style="color: #059669;"><?= format_rupiah($b['total_biaya']) ?></b></td>
                                <td><span class="badge <?= $statusBadge ?>"><?= ucfirst($b['status_booking']) ?></span></td>
                                <td>
                                    <button type="button" class="btn btn-primary btn-sm" onclick='modalVerifBooking(<?= json_encode($b, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>)'>
                                        <i class="fa-solid fa-pen-to-square"></i> Proses
                                    </button>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="7" style="text-align: center; color: var(--adm-muted); padding: 24px;">Tidak ada data pemesanan booking.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- MODAL PROSES BOOKING -->
<div class="modal-overlay" id="modalBooking">
    <div class="modal-box">
        <form action="booking_verifikasi.php" method="POST">
            <input type="hidden" name="id_booking" id="vb_id_booking">
            <input type="hidden" name="id_kamar" id="vb_id_kamar">

            <div class="modal-header">
                <h3 style="font-size: 1.1rem; font-weight: 700;"><i class="fa-solid fa-user-check"></i> Proses Pemesanan Kamar</h3>
                <button type="button" class="btn btn-outline btn-sm" onclick="closeModal('modalBooking')">&times;</button>
            </div>
            <div class="modal-body">
                <div style="background: var(--adm-bg); padding: 12px; border-radius: var(--radius-sm); margin-bottom: 16px; border: 1px solid var(--adm-border);">
                    <div>Kode Booking: <b id="vb_kode" style="color: var(--adm-primary);"></b></div>
                    <div>Pemesan: <b id="vb_nama"></b></div>
                    <div>Kamar: <b id="vb_kamar"></b></div>
                    <div>Total Biaya: <b id="vb_biaya" style="color: #059669;"></b></div>
                </div>

                <div class="form-group">
                    <label>Status Pemesanan *</label>
                    <select name="status_booking" id="vb_status" class="form-control" required>
                        <option value="pending">Pending (Menunggu Verifikasi)</option>
                        <option value="disetujui">Disetujui (Menunggu Pembayaran)</option>
                        <option value="aktif">Aktif (Penghuni Masuk / Check-in)</option>
                        <option value="ditolak">Tolak Pemesanan</option>
                        <option value="selesai">Selesai (Masa Sewa Berakhir)</option>
                        <option value="dibatalkan">Dibatalkan</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal('modalBooking')">Batal</button>
                <button type="submit" name="btn_proses_booking" class="btn btn-primary">
                    <i class="fa-solid fa-check"></i> Simpan Status
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function modalVerifBooking(b) {
    document.getElementById('vb_id_booking').value = b.id_booking;
    document.getElementById('vb_id_kamar').value = b.id_kamar;
    document.getElementById('vb_kode').innerText = b.kode_booking;
    document.getElementById('vb_nama').innerText = b.nama_lengkap;
    document.getElementById('vb_kamar').innerText = 'Kamar ' + b.nomor_kamar + ' (' + b.nama_kamar + ')';
    document.getElementById('vb_biaya').innerText = 'Rp ' + Number(b.total_biaya).toLocaleString('id-ID');
    document.getElementById('vb_status').value = b.status_booking;
    openModal('modalBooking');
}
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
