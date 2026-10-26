<?php
$page_title = "Dashboard Utama";
require_once __DIR__ . '/header.php';

// Hitung Statistik
// 1. Total User & Rincian
$q_u = mysqli_query($conn, "SELECT 
    COUNT(*) as total_users,
    SUM(CASE WHEN role = 'pengunjung' THEN 1 ELSE 0 END) as total_pengunjung,
    SUM(CASE WHEN role = 'petugas' THEN 1 ELSE 0 END) as total_petugas,
    SUM(CASE WHEN role = 'admin' THEN 1 ELSE 0 END) as total_admin
    FROM tbl_users");
$stat_u = mysqli_fetch_assoc($q_u);

// 2. Total Kamar & Okupansi
$q_k = mysqli_query($conn, "SELECT 
    COUNT(*) as total_kamar,
    SUM(CASE WHEN status = 'tersedia' THEN 1 ELSE 0 END) as kamar_tersedia,
    SUM(CASE WHEN status = 'terisi' THEN 1 ELSE 0 END) as kamar_terisi,
    SUM(CASE WHEN status = 'renovasi' THEN 1 ELSE 0 END) as kamar_renovasi
    FROM tbl_kamar");
$stat_k = mysqli_fetch_assoc($q_k);

// 3. Keuangan
$q_uang = mysqli_query($conn, "SELECT 
    SUM(CASE WHEN status_pembayaran = 'lunas' THEN nominal ELSE 0 END) as total_pendapatan,
    SUM(CASE WHEN status_pembayaran = 'menunggu_verifikasi' THEN nominal ELSE 0 END) as pending_nominal,
    SUM(CASE WHEN status_pembayaran = 'belum_bayar' THEN nominal ELSE 0 END) as belum_bayar_nominal
    FROM tbl_tagihan_pembayaran");
$stat_uang = mysqli_fetch_assoc($q_uang);

// Data 5 Pemesanan Terbaru
$q_recent_b = mysqli_query($conn, "SELECT b.*, u.nama_lengkap, u.no_hp, k.nomor_kamar, k.nama_kamar 
                                   FROM tbl_booking b 
                                   JOIN tbl_users u ON b.id_user = u.id_user 
                                   JOIN tbl_kamar k ON b.id_kamar = k.id_kamar 
                                   ORDER BY b.id_booking DESC LIMIT 5");

// Data 5 Pembayaran Terbaru
$q_recent_t = mysqli_query($conn, "SELECT t.*, u.nama_lengkap, k.nomor_kamar 
                                   FROM tbl_tagihan_pembayaran t 
                                   JOIN tbl_users u ON t.id_user = u.id_user 
                                   JOIN tbl_kamar k ON t.id_kamar = k.id_kamar 
                                   ORDER BY t.id_tagihan DESC LIMIT 5");
?>

<!-- STATISTIK UTAMA -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-data">
            <span class="stat-label">Total Pendapatan (Lunas)</span>
            <span class="stat-value" style="color: #059669; font-size: 1.45rem;"><?= format_rupiah($stat_uang['total_pendapatan'] ?? 0) ?></span>
        </div>
        <div class="stat-icon icon-emerald">
            <i class="fa-solid fa-wallet"></i>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-data">
            <span class="stat-label">Total Pengguna</span>
            <span class="stat-value"><?= (int)$stat_u['total_users'] ?></span>
            <span style="font-size: 0.75rem; color: var(--adm-muted); margin-top: 4px;">
                <?= (int)$stat_u['total_pengunjung'] ?> Penyewa | <?= (int)$stat_u['total_petugas'] ?> Petugas
            </span>
        </div>
        <div class="stat-icon icon-indigo">
            <i class="fa-solid fa-users"></i>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-data">
            <span class="stat-label">Kamar Tersedia</span>
            <span class="stat-value" style="color: #0284c7;"><?= (int)$stat_k['kamar_tersedia'] ?> / <?= (int)$stat_k['total_kamar'] ?></span>
            <span style="font-size: 0.75rem; color: var(--adm-muted); margin-top: 4px;">
                <?= (int)$stat_k['kamar_terisi'] ?> Terisi | <?= (int)$stat_k['kamar_renovasi'] ?> Renovasi
            </span>
        </div>
        <div class="stat-icon icon-blue">
            <i class="fa-solid fa-door-open"></i>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-data">
            <span class="stat-label">Verifikasi Tertunda</span>
            <span class="stat-value" style="color: #d97706;"><?= $pending_booking + $pending_bayar ?></span>
            <span style="font-size: 0.75rem; color: var(--adm-muted); margin-top: 4px;">
                <?= $pending_booking ?> Booking | <?= $pending_bayar ?> Bukti Bayar
            </span>
        </div>
        <div class="stat-icon icon-amber">
            <i class="fa-solid fa-bell"></i>
        </div>
    </div>
</div>

<div style="display: grid; grid-template-columns: 1.1fr 0.9fr; gap: 24px;">
    <!-- TABEL BOOKING TERBARU -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><i class="fa-solid fa-calendar-check"></i> Pemesanan Kamar Terbaru</h3>
            <a href="booking.php" class="btn btn-outline btn-sm">Lihat Semua</a>
        </div>
        <div class="card-body" style="padding: 0;">
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Kode</th>
                            <th>Pemesan</th>
                            <th>Kamar</th>
                            <th>Mulai</th>
                            <th>Total</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($q_recent_b && mysqli_num_rows($q_recent_b) > 0): ?>
                            <?php while ($b = mysqli_fetch_assoc($q_recent_b)): 
                                $statusBadge = [
                                    'pending' => 'badge-warning',
                                    'disetujui' => 'badge-info',
                                    'aktif' => 'badge-success',
                                    'selesai' => 'badge-secondary',
                                    'ditolak' => 'badge-danger',
                                    'dibatalkan' => 'badge-danger'
                                ][$b['status_booking']] ?? 'badge-secondary';
                            ?>
                                <tr>
                                    <td><b style="color: var(--adm-primary); font-size: 0.82rem;"><?= htmlspecialchars($b['kode_booking']) ?></b></td>
                                    <td>
                                        <b><?= htmlspecialchars($b['nama_lengkap']) ?></b><br>
                                        <small style="color: var(--adm-muted);"><?= htmlspecialchars($b['no_hp']) ?></small>
                                    </td>
                                    <td><?= htmlspecialchars($b['nomor_kamar']) ?></td>
                                    <td><?= tgl_indo($b['tgl_mulai_sewa']) ?></td>
                                    <td><b><?= format_rupiah($b['total_biaya']) ?></b></td>
                                    <td><span class="badge <?= $statusBadge ?>"><?= ucfirst($b['status_booking']) ?></span></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="6" style="text-align: center; color: var(--adm-muted);">Belum ada data pemesanan.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- TABEL TRANSAKSI & PEMBAYARAN TERBARU -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><i class="fa-solid fa-receipt"></i> Transaksi & Tagihan Terbaru</h3>
            <a href="tagihan.php" class="btn btn-outline btn-sm">Lihat Semua</a>
        </div>
        <div class="card-body" style="padding: 0;">
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Kode & Penyewa</th>
                            <th>Nominal</th>
                            <th>Status</th>
                            <th>Bukti</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($q_recent_t && mysqli_num_rows($q_recent_t) > 0): ?>
                            <?php while ($t = mysqli_fetch_assoc($q_recent_t)): 
                                $statusBadge = [
                                    'belum_bayar' => 'badge-danger',
                                    'menunggu_verifikasi' => 'badge-warning',
                                    'lunas' => 'badge-success',
                                    'ditolak' => 'badge-secondary'
                                ][$t['status_pembayaran']] ?? 'badge-secondary';
                            ?>
                                <tr>
                                    <td>
                                        <b style="color: var(--adm-primary); font-size: 0.82rem;"><?= htmlspecialchars($t['kode_tagihan']) ?></b><br>
                                        <span><?= htmlspecialchars($t['nama_lengkap']) ?> (<?= htmlspecialchars($t['nomor_kamar']) ?>)</span>
                                    </td>
                                    <td><b><?= format_rupiah($t['nominal']) ?></b></td>
                                    <td><span class="badge <?= $statusBadge ?>"><?= str_replace('_', ' ', ucfirst($t['status_pembayaran'])) ?></span></td>
                                    <td>
                                        <?php if (!empty($t['bukti_transfer'])): ?>
                                            <a href="tagihan.php" class="btn btn-outline btn-sm" title="Periksa Bukti">
                                                <i class="fa-solid fa-image"></i>
                                            </a>
                                        <?php else: ?>
                                            <span style="color: var(--adm-muted); font-size: 0.75rem;">-</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="4" style="text-align: center; color: var(--adm-muted);">Belum ada data tagihan.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
