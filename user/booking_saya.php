<?php
$page_title = "Riwayat Pemesanan Kamar";
require_once __DIR__ . '/header.php';

$id_user = (int)$_SESSION['user_id'];

// Batalkan Booking jika masih pending
if (isset($_GET['batal'])) {
    $id_batal = (int)$_GET['batal'];
    $stmt = mysqli_prepare($conn, "UPDATE tbl_booking SET status_booking = 'dibatalkan' WHERE id_booking = ? AND id_user = ? AND status_booking = 'pending'");
    mysqli_stmt_bind_param($stmt, "ii", $id_batal, $id_user);
    if (mysqli_stmt_execute($stmt)) {
        set_flash('info', 'Pemesanan kamar berhasil dibatalkan.');
    }
    header("Location: booking_saya.php");
    exit();
}

$my_bookings = mysqli_query($conn, "SELECT b.*, k.nomor_kamar, k.nama_kamar, k.foto_utama, k.harga_per_bulan 
                                    FROM tbl_booking b 
                                    JOIN tbl_kamar k ON b.id_kamar = k.id_kamar 
                                    WHERE b.id_user = $id_user 
                                    ORDER BY b.id_booking DESC");
?>

<div class="card">
    <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
        <h3 class="card-title"><i class="fa-solid fa-calendar-check"></i> Riwayat Pengajuan Sewa Kamar</h3>
        <a href="../index.php#kamar" class="btn btn-primary btn-sm"><i class="fa-solid fa-plus"></i> Pesan Kamar Baru</a>
    </div>

    <div class="card-body" style="padding: 0;">
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Kode Booking</th>
                        <th>Kamar</th>
                        <th>Mulai Sewa</th>
                        <th>Durasi</th>
                        <th>Total Biaya</th>
                        <th>Status Booking</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($my_bookings && mysqli_num_rows($my_bookings) > 0): ?>
                        <?php while ($b = mysqli_fetch_assoc($my_bookings)): 
                            $badge = [
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
                                    <b style="color: var(--adm-primary);"><?= htmlspecialchars($b['kode_booking']) ?></b><br>
                                    <small style="color: var(--adm-muted);"><?= tgl_jam_indo($b['tgl_booking']) ?></small>
                                </td>
                                <td>
                                    <b><?= htmlspecialchars($b['nama_kamar']) ?></b><br>
                                    <span style="font-size: 0.78rem; background: #e0e7ff; color: #4338ca; padding: 2px 6px; border-radius: 4px; font-weight: 700;">
                                        Kamar <?= htmlspecialchars($b['nomor_kamar']) ?>
                                    </span>
                                </td>
                                <td><?= tgl_indo($b['tgl_mulai_sewa']) ?></td>
                                <td><b><?= $b['durasi_bulan'] ?> Bulan</b></td>
                                <td><b style="color: #059669;"><?= format_rupiah($b['total_biaya']) ?></b></td>
                                <td><span class="badge <?= $badge ?>"><?= ucfirst($b['status_booking']) ?></span></td>
                                <td>
                                    <div style="display: flex; gap: 6px;">
                                        <a href="tagihan_saya.php" class="btn btn-primary btn-sm" title="Lihat Tagihan">
                                            <i class="fa-solid fa-receipt"></i> Tagihan
                                        </a>
                                        <?php if ($b['status_booking'] === 'pending'): ?>
                                            <a href="booking_saya.php?batal=<?= $b['id_booking'] ?>" onclick="return confirm('Batalkan pengajuan booking ini?')" class="btn btn-outline btn-sm" style="color: #ef4444;" title="Batalkan">
                                                <i class="fa-solid fa-xmark"></i> Batal
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="7" style="text-align: center; color: var(--adm-muted);">Anda belum pernah melakukan pemesanan kamar.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
