<?php
/**
 * Data Pemesanan & Sewa Kost (Admin)
 */
require_once __DIR__ . '/../koneksi.php';
require_once __DIR__ . '/../config/helper.php';

auth_check(['admin']);

// 1. PROSES UPDATE STATUS BOOKING (Sebelum header.php)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['btn_update_booking'])) {
    $id_booking = (int)$_POST['id_booking'];
    $status_baru = clean($conn, $_POST['status_booking']);
    $id_kamar = (int)$_POST['id_kamar'];
    $id_admin = (int)$_SESSION['user_id'];
    $now = date('Y-m-d H:i:s');

    $stmt = mysqli_prepare($conn, "UPDATE tbl_booking SET status_booking = ?, diverifikasi_oleh = ?, tgl_verifikasi = ? WHERE id_booking = ?");
    mysqli_stmt_bind_param($stmt, "sisi", $status_baru, $id_admin, $now, $id_booking);
    
    if (mysqli_stmt_execute($stmt)) {
        // Sinkronisasi status kamar
        if ($status_baru === 'aktif') {
            mysqli_query($conn, "UPDATE tbl_kamar SET status = 'terisi' WHERE id_kamar = $id_kamar");
        } elseif (in_array($status_baru, ['selesai', 'ditolak', 'dibatalkan'])) {
            // Cek apakah ada booking aktif lain di kamar ini
            $cek_lain = mysqli_query($conn, "SELECT id_booking FROM tbl_booking WHERE id_kamar = $id_kamar AND status_booking = 'aktif' AND id_booking != $id_booking");
            if (mysqli_num_rows($cek_lain) == 0) {
                mysqli_query($conn, "UPDATE tbl_kamar SET status = 'tersedia' WHERE id_kamar = $id_kamar");
            }
        }

        set_flash('success', "Status booking berhasil diubah menjadi '" . ucfirst($status_baru) . "'.");
    } else {
        set_flash('danger', "Gagal mengubah status: " . mysqli_error($conn));
    }
    header("Location: booking.php");
    exit();
}

// Filter Status
$filter_status = isset($_GET['status']) ? clean($conn, $_GET['status']) : '';
$where = "WHERE 1=1";
if (!empty($filter_status)) {
    $where .= " AND b.status_booking = '$filter_status'";
}

$booking_list = mysqli_query($conn, "SELECT b.*, u.nama_lengkap, u.no_hp, u.email, k.nomor_kamar, k.nama_kamar, k.harga_per_bulan, v.nama_lengkap as verifikator_nama 
                                     FROM tbl_booking b 
                                     JOIN tbl_users u ON b.id_user = u.id_user 
                                     JOIN tbl_kamar k ON b.id_kamar = k.id_kamar 
                                     LEFT JOIN tbl_users v ON b.diverifikasi_oleh = v.id_user 
                                     $where ORDER BY b.id_booking DESC");

$page_title = "Data Pemesanan & Sewa Kost";
require_once __DIR__ . '/header.php';
?>

<div class="card">
    <div class="card-header" style="flex-wrap: wrap; gap: 14px;">
        <div style="display: flex; align-items: center; gap: 12px; flex: 1;">
            <h3 class="card-title"><i class="fa-solid fa-calendar-check"></i> Seluruh Riwayat Pemesanan Kamar</h3>
            <div style="display: flex; gap: 6px;">
                <a href="booking.php" class="btn btn-sm <?= empty($filter_status) ? 'btn-primary' : 'btn-outline' ?>">Semua</a>
                <a href="booking.php?status=pending" class="btn btn-sm <?= $filter_status == 'pending' ? 'btn-primary' : 'btn-outline' ?>">Pending</a>
                <a href="booking.php?status=aktif" class="btn btn-sm <?= $filter_status == 'aktif' ? 'btn-primary' : 'btn-outline' ?>">Aktif</a>
                <a href="booking.php?status=selesai" class="btn btn-sm <?= $filter_status == 'selesai' ? 'btn-primary' : 'btn-outline' ?>">Selesai</a>
            </div>
        </div>

        <input type="text" class="form-control table-search" data-target-table="bookingTable" placeholder="Cari pemesan / kode..." style="width: 220px; padding: 7px 12px;">
    </div>

    <div class="card-body" style="padding: 0;">
        <div class="table-responsive">
            <table class="data-table" id="bookingTable">
                <thead>
                    <tr>
                        <th>Kode & Tanggal</th>
                        <th>Penyewa / Pemesan</th>
                        <th>Kamar</th>
                        <th>Periode Sewa</th>
                        <th>Total Biaya</th>
                        <th>Catatan</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($booking_list && mysqli_num_rows($booking_list) > 0): ?>
                        <?php while ($b = mysqli_fetch_assoc($booking_list)): 
                            $statusBadges = [
                                'pending' => 'badge-warning',
                                'disetujui' => 'badge-info',
                                'aktif' => 'badge-success',
                                'selesai' => 'badge-secondary',
                                'ditolak' => 'badge-danger',
                                'dibatalkan' => 'badge-danger'
                            ][$b['status_booking']] ?? 'badge-secondary';
                        ?>
                            <tr>
                                <td>
                                    <b style="color: var(--adm-primary); font-size: 0.85rem;"><?= htmlspecialchars($b['kode_booking']) ?></b><br>
                                    <small style="color: var(--adm-muted);"><?= tgl_jam_indo($b['tgl_booking']) ?></small>
                                </td>
                                <td>
                                    <b><?= htmlspecialchars($b['nama_lengkap']) ?></b><br>
                                    <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $b['no_hp']) ?>?text=Halo%20<?= urlencode($b['nama_lengkap']) ?>,%20mengenai%20booking%20kost%20<?= urlencode($b['kode_booking']) ?>" target="_blank" style="color: #10b981; font-size: 0.8rem; font-weight: 600;">
                                        <i class="fa-brands fa-whatsapp"></i> <?= htmlspecialchars($b['no_hp']) ?>
                                    </a>
                                </td>
                                <td>
                                    <b><?= htmlspecialchars($b['nomor_kamar']) ?></b><br>
                                    <small style="color: var(--adm-muted);"><?= htmlspecialchars($b['nama_kamar']) ?></small>
                                </td>
                                <td>
                                    <span><?= tgl_indo($b['tgl_mulai_sewa']) ?></span> s/d <span><?= tgl_indo($b['tgl_selesai_sewa']) ?></span><br>
                                    <small style="font-weight: 700; color: var(--adm-primary);">(<?= $b['durasi_bulan'] ?> Bulan)</small>
                                </td>
                                <td>
                                    <b style="font-size: 0.95rem; color: #059669;"><?= format_rupiah($b['total_biaya']) ?></b>
                                </td>
                                <td>
                                    <small style="color: var(--adm-muted);"><?= htmlspecialchars($b['catatan'] ?? '-') ?></small>
                                </td>
                                <td>
                                    <span class="badge <?= $statusBadges ?>"><?= ucfirst($b['status_booking']) ?></span>
                                    <?php if (!empty($b['verifikator_nama'])): ?>
                                        <br><small style="font-size: 0.72rem; color: var(--adm-muted);">Oleh: <?= htmlspecialchars($b['verifikator_nama']) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <button type="button" class="btn btn-primary btn-sm" onclick='modalUbahStatus(<?= json_encode($b) ?>)'>
                                        <i class="fa-solid fa-sliders"></i> Ubah Status
                                    </button>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="8" style="text-align: center; color: var(--adm-muted);">Tidak ada data pemesanan kamar.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- MODAL UBAH STATUS BOOKING -->
<div class="modal-overlay" id="modalStatusBooking">
    <div class="modal-box">
        <form action="booking.php" method="POST">
            <input type="hidden" name="id_booking" id="modal_id_booking">
            <input type="hidden" name="id_kamar" id="modal_id_kamar">
            
            <div class="modal-header">
                <h3 style="font-size: 1.1rem; font-weight: 700;">Ubah Status Pemesanan</h3>
                <button type="button" class="btn btn-outline btn-sm" onclick="closeModal('modalStatusBooking')">&times;</button>
            </div>
            <div class="modal-body">
                <p style="margin-bottom: 16px; font-size: 0.92rem; color: var(--adm-muted);">
                    Kode Booking: <b id="modal_kode_booking" style="color: var(--adm-primary);"></b><br>
                    Pemesan: <b id="modal_nama_pemesan" style="color: var(--adm-text);"></b>
                </p>

                <div class="form-group">
                    <label>Pilih Status Pemesanan Baru *</label>
                    <select name="status_booking" id="modal_select_status" class="form-control" required>
                        <option value="pending">Pending (Menunggu)</option>
                        <option value="disetujui">Disetujui (Menunggu Masuk/Bayar)</option>
                        <option value="aktif">Aktif (Penghuni Sedang Menempati Kamar)</option>
                        <option value="selesai">Selesai (Masa Sewa Berakhir & Kamar Kosong)</option>
                        <option value="ditolak">Ditolak (Booking Ditolak)</option>
                        <option value="dibatalkan">Dibatalkan (Batal Sewa)</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal('modalStatusBooking')">Batal</button>
                <button type="submit" name="btn_update_booking" class="btn btn-primary"><i class="fa-solid fa-check"></i> Simpan Status</button>
            </div>
        </form>
    </div>
</div>

<script>
function modalUbahStatus(b) {
    document.getElementById('modal_id_booking').value = b.id_booking;
    document.getElementById('modal_id_kamar').value = b.id_kamar;
    document.getElementById('modal_kode_booking').innerText = b.kode_booking;
    document.getElementById('modal_nama_pemesan').innerText = b.nama_lengkap + ' (' + b.nomor_kamar + ')';
    document.getElementById('modal_select_status').value = b.status_booking;
    openModal('modalStatusBooking');
}
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
