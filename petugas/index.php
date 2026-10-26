<?php
$page_title = "Dashboard & Monitoring Petugas";
require_once __DIR__ . '/header.php';

// 1. Data Statistik Kamar
$q_kamar = mysqli_query($conn, "SELECT 
    COUNT(*) as total_kamar,
    SUM(CASE WHEN status = 'tersedia' THEN 1 ELSE 0 END) as kamar_tersedia,
    SUM(CASE WHEN status = 'terisi' THEN 1 ELSE 0 END) as kamar_terisi,
    SUM(CASE WHEN status = 'renovasi' THEN 1 ELSE 0 END) as kamar_renovasi
    FROM tbl_kamar");
$kamar = mysqli_fetch_assoc($q_kamar);

$total_kamar = (int)($kamar['total_kamar'] ?? 0);
$kamar_tersedia = (int)($kamar['kamar_tersedia'] ?? 0);
$kamar_terisi = (int)($kamar['kamar_terisi'] ?? 0);
$kamar_renovasi = (int)($kamar['kamar_renovasi'] ?? 0);

// 2. Data Statistik Keuangan / Tagihan
$q_tagihan_stat = mysqli_query($conn, "SELECT 
    SUM(CASE WHEN status_pembayaran = 'lunas' THEN nominal ELSE 0 END) as total_lunas,
    SUM(CASE WHEN status_pembayaran = 'menunggu_verifikasi' THEN nominal ELSE 0 END) as total_menunggu,
    SUM(CASE WHEN status_pembayaran = 'belum_bayar' THEN nominal ELSE 0 END) as total_belum,
    COUNT(CASE WHEN status_pembayaran = 'lunas' THEN 1 END) as count_lunas,
    COUNT(CASE WHEN status_pembayaran = 'menunggu_verifikasi' THEN 1 END) as count_menunggu,
    COUNT(CASE WHEN status_pembayaran = 'belum_bayar' THEN 1 END) as count_belum
    FROM tbl_tagihan_pembayaran");
$stat_tagihan = mysqli_fetch_assoc($q_tagihan_stat);

$nominal_lunas = (float)($stat_tagihan['total_lunas'] ?? 0);
$nominal_menunggu = (float)($stat_tagihan['total_menunggu'] ?? 0);
$nominal_belum = (float)($stat_tagihan['total_belum'] ?? 0);

// 3. Total Penghuni Aktif
$q_penghuni = mysqli_query($conn, "SELECT COUNT(*) as total FROM tbl_booking WHERE status_booking = 'aktif'");
$penghuni_aktif = (int)(mysqli_fetch_assoc($q_penghuni)['total'] ?? 0);

// 4. Data Tabel 1: Booking Baru Butuh Verifikasi
$q_pending_b = mysqli_query($conn, "SELECT b.*, u.nama_lengkap, u.no_hp, k.nomor_kamar, k.nama_kamar 
                                    FROM tbl_booking b 
                                    JOIN tbl_users u ON b.id_user = u.id_user 
                                    JOIN tbl_kamar k ON b.id_kamar = k.id_kamar 
                                    WHERE b.status_booking = 'pending' 
                                    ORDER BY b.id_booking DESC LIMIT 5");

// 5. Data Tabel 2: Pembayaran Menunggu Verifikasi
$q_pending_t = mysqli_query($conn, "SELECT t.*, u.nama_lengkap, u.no_hp, k.nomor_kamar 
                                    FROM tbl_tagihan_pembayaran t 
                                    JOIN tbl_users u ON t.id_user = u.id_user 
                                    JOIN tbl_kamar k ON t.id_kamar = k.id_kamar 
                                    WHERE t.status_pembayaran = 'menunggu_verifikasi' 
                                    ORDER BY t.id_tagihan DESC LIMIT 5");

// 6. Data Tabel 3: Pengingat Masa Sewa Penghuni Aktif
$q_sewa_reminder = mysqli_query($conn, "SELECT b.*, u.nama_lengkap, u.no_hp, k.nomor_kamar, k.nama_kamar, k.harga_per_bulan 
                                       FROM tbl_booking b 
                                       JOIN tbl_users u ON b.id_user = u.id_user 
                                       JOIN tbl_kamar k ON b.id_kamar = k.id_kamar 
                                       WHERE b.status_booking = 'aktif' 
                                       ORDER BY b.tgl_selesai_sewa ASC LIMIT 5");

// 7. Data Tabel 4: Keluhan / Komplain Fasilitas Terkini
$q_komplain_recent = mysqli_query($conn, "SELECT p.*, u.nama_lengkap, k.nomor_kamar 
                                         FROM tbl_pengaduan_layanan p 
                                         JOIN tbl_users u ON p.id_user = u.id_user 
                                         JOIN tbl_kamar k ON p.id_kamar = k.id_kamar 
                                         ORDER BY (p.status = 'menunggu') DESC, p.id_pengaduan DESC LIMIT 5");
?>

<!-- KARTU STATISTIK UTAMA PETUGAS -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-data">
            <span class="stat-label">Booking Butuh Verifikasi</span>
            <span class="stat-value" style="color: #d97706;"><?= $pending_booking ?></span>
            <small style="color: var(--adm-muted);">Pemesanan baru dari pengunjung</small>
        </div>
        <div class="stat-icon icon-amber"><i class="fa-solid fa-user-clock"></i></div>
    </div>

    <div class="stat-card">
        <div class="stat-data">
            <span class="stat-label">Bukti Bayar Menunggu</span>
            <span class="stat-value" style="color: #4f46e5;"><?= $pending_bayar ?></span>
            <small style="color: var(--adm-muted);">Perlu dicocokkan dengan mutasi</small>
        </div>
        <div class="stat-icon icon-indigo"><i class="fa-solid fa-receipt"></i></div>
    </div>

    <div class="stat-card">
        <div class="stat-data">
            <span class="stat-label">Penghuni Kost Aktif</span>
            <span class="stat-value" style="color: #059669;"><?= $penghuni_aktif ?> Orang</span>
            <small style="color: var(--adm-muted);">Menempati <?= $kamar_terisi ?> Kamar</small>
        </div>
        <div class="stat-icon icon-emerald"><i class="fa-solid fa-people-roof"></i></div>
    </div>

    <div class="stat-card">
        <div class="stat-data">
            <span class="stat-label">Kamar Kosong Tersedia</span>
            <span class="stat-value" style="color: #0284c7;"><?= $kamar_tersedia ?> / <?= $total_kamar ?></span>
            <small style="color: var(--adm-muted);"><?= $kamar_renovasi ?> Kamar Sedang Renovasi</small>
        </div>
        <div class="stat-icon icon-blue"><i class="fa-solid fa-door-open"></i></div>
    </div>
</div>

<!-- ========================================================= -->
<!-- SECTION GRAFIK & ANALITIK PETUGAS -->
<!-- ========================================================= -->
<div style="display: grid; grid-template-columns: 1fr 1.2fr; gap: 24px; margin-bottom: 26px;">
    
    <!-- GRAFIK 1: OKUPANSI & KONDISI KAMAR -->
    <div class="card" style="margin-bottom: 0;">
        <div class="card-header">
            <h3 class="card-title"><i class="fa-solid fa-chart-pie"></i> Grafik Status Okupansi Kamar</h3>
            <span class="badge badge-info"><?= $total_kamar ?> Total Kamar</span>
        </div>
        <div class="card-body" style="display: flex; flex-direction: column; align-items: center; justify-content: center; min-height: 280px;">
            <div style="width: 100%; max-width: 250px; position: relative;">
                <canvas id="chartOkupansiKamar"></canvas>
            </div>
            <div style="display: flex; gap: 16px; margin-top: 18px; font-size: 0.82rem; font-weight: 600;">
                <span style="color: #10b981;"><i class="fa-solid fa-circle"></i> Tersedia (<?= $kamar_tersedia ?>)</span>
                <span style="color: #ef4444;"><i class="fa-solid fa-circle"></i> Terisi (<?= $kamar_terisi ?>)</span>
                <span style="color: #f59e0b;"><i class="fa-solid fa-circle"></i> Renovasi (<?= $kamar_renovasi ?>)</span>
            </div>
        </div>
    </div>

    <!-- GRAFIK 2: STATUS PEMBAYARAN & TAGIHAN -->
    <div class="card" style="margin-bottom: 0;">
        <div class="card-header">
            <h3 class="card-title"><i class="fa-solid fa-chart-column"></i> Grafik Rekapitulasi Tagihan & Pembayaran</h3>
            <a href="tagihan_list.php" class="btn btn-outline btn-sm">Lihat Detail</a>
        </div>
        <div class="card-body" style="min-height: 280px; display: flex; flex-direction: column; justify-content: center;">
            <div style="height: 220px; width: 100%;">
                <canvas id="chartStatusTagihan"></canvas>
            </div>
            <div style="display: flex; justify-content: space-around; margin-top: 12px; font-size: 0.8rem; border-top: 1px solid var(--adm-border); padding-top: 10px;">
                <div><b>Lunas:</b> <span style="color: #059669;"><?= format_rupiah($nominal_lunas) ?></span></div>
                <div><b>Menunggu:</b> <span style="color: #4f46e5;"><?= format_rupiah($nominal_menunggu) ?></span></div>
                <div><b>Belum Bayar:</b> <span style="color: #ef4444;"><?= format_rupiah($nominal_belum) ?></span></div>
            </div>
        </div>
    </div>

</div>

<!-- SHORTCUT PELAYANAN CEPAT -->
<div class="card" style="background: linear-gradient(135deg, #3730a3, #4f46e5); color: #fff; margin-bottom: 26px;">
    <div class="card-body" style="padding: 20px 24px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 16px;">
        <div>
            <h3 style="font-size: 1.2rem; font-weight: 800; margin-bottom: 4px;">Pusat Pelayanan Petugas Kost</h3>
            <p style="color: #c7d2fe; font-size: 0.88rem;">Input pemesanan kamar baru langsung di tempat (*walk-in*), terbitkan tagihan bulanan, atau cek mutasi.</p>
        </div>
        <div style="display: flex; gap: 10px; flex-wrap: wrap;">
            <a href="tagihan_input.php" class="btn" style="background: #ffffff; color: #3730a3; font-weight: 700;">
                <i class="fa-solid fa-plus-circle"></i> Input Tagihan / Walk-In
            </a>
            <a href="tagihan_list.php" class="btn btn-outline" style="color: #ffffff; border-color: rgba(255,255,255,0.4);">
                <i class="fa-solid fa-list-check"></i> Verifikasi Pembayaran
            </a>
        </div>
    </div>
</div>

<!-- ========================================================= -->
<!-- TABEL 1 & TABEL 2 (BOOKING & PEMBAYARAN) -->
<!-- ========================================================= -->
<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-bottom: 26px;">
    
    <!-- TABEL 1: BOOKING BARU BUTUH VERIFIKASI -->
    <div class="card" style="margin-bottom: 0;">
        <div class="card-header">
            <h3 class="card-title"><i class="fa-solid fa-user-plus"></i> Tabel 1: Pemesanan Baru Perlu Diproses</h3>
            <a href="booking_verifikasi.php" class="btn btn-outline btn-sm">Buka Semua</a>
        </div>
        <div class="card-body" style="padding: 0;">
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Pemesan & Kontak</th>
                            <th>Kamar</th>
                            <th>Mulai</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($q_pending_b && mysqli_num_rows($q_pending_b) > 0): ?>
                            <?php while ($b = mysqli_fetch_assoc($q_pending_b)): ?>
                                <tr>
                                    <td>
                                        <b><?= htmlspecialchars($b['nama_lengkap']) ?></b><br>
                                        <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $b['no_hp']) ?>" target="_blank" style="color: #10b981; font-size: 0.8rem; font-weight: 600;">
                                            <i class="fa-brands fa-whatsapp"></i> <?= htmlspecialchars($b['no_hp']) ?>
                                        </a>
                                    </td>
                                    <td>
                                        <b>Kamar <?= htmlspecialchars($b['nomor_kamar']) ?></b><br>
                                        <small style="color: var(--adm-muted);"><?= htmlspecialchars($b['nama_kamar']) ?></small>
                                    </td>
                                    <td><?= tgl_indo($b['tgl_mulai_sewa']) ?></td>
                                    <td>
                                        <a href="booking_verifikasi.php" class="btn btn-primary btn-sm">
                                            <i class="fa-solid fa-check"></i> Proses
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="4" style="text-align: center; color: var(--adm-muted); padding: 20px;">Tidak ada booking pending saat ini.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- TABEL 2: BUKTI BAYAR BUTUH VERIFIKASI -->
    <div class="card" style="margin-bottom: 0;">
        <div class="card-header">
            <h3 class="card-title"><i class="fa-solid fa-receipt"></i> Tabel 2: Bukti Transfer Menunggu Dicek</h3>
            <a href="tagihan_list.php" class="btn btn-outline btn-sm">Buka Semua</a>
        </div>
        <div class="card-body" style="padding: 0;">
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Penyewa</th>
                            <th>Nominal</th>
                            <th>Bukti</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($q_pending_t && mysqli_num_rows($q_pending_t) > 0): ?>
                            <?php while ($t = mysqli_fetch_assoc($q_pending_t)): 
                                $bukti_path = '../uploads/bukti/' . $t['bukti_transfer'];
                            ?>
                                <tr>
                                    <td>
                                        <b><?= htmlspecialchars($t['nama_lengkap']) ?></b><br>
                                        <small style="color: var(--adm-muted);">Kamar <?= htmlspecialchars($t['nomor_kamar']) ?></small>
                                    </td>
                                    <td><b style="color: #059669;"><?= format_rupiah($t['nominal']) ?></b></td>
                                    <td>
                                        <?php if (!empty($t['bukti_transfer']) && file_exists($bukti_path)): ?>
                                            <button type="button" class="btn btn-outline btn-sm" onclick="openImageModal('<?= $bukti_path ?>')" style="color: #4f46e5;">
                                                <i class="fa-solid fa-image"></i> Foto
                                            </button>
                                        <?php else: ?>
                                            <small style="color: var(--adm-muted);">-</small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="tagihan_list.php" class="btn btn-success btn-sm">
                                            <i class="fa-solid fa-check-double"></i> Verif
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="4" style="text-align: center; color: var(--adm-muted); padding: 20px;">Semua bukti pembayaran telah diverifikasi.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<!-- ========================================================= -->
<!-- TABEL 3 & TABEL 4 (PENGINGAT MASA SEWA & KOMPLAIN FASILITAS) -->
<!-- ========================================================= -->
<div style="display: grid; grid-template-columns: 1.1fr 0.9fr; gap: 24px; margin-top: 26px;">
    
    <!-- TABEL 3: PENGINGAT JATUH TEMPO / MASA BERAKHIR SEWA -->
    <div class="card" style="margin-bottom: 0;">
        <div class="card-header">
            <h3 class="card-title"><i class="fa-solid fa-clock-rotate-left"></i> Tabel 3: Pengingat Berakhir Masa Sewa Penghuni</h3>
            <a href="penghuni.php" class="btn btn-outline btn-sm">Seluruh Penghuni</a>
        </div>
        <div class="card-body" style="padding: 0;">
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Kamar & Penghuni</th>
                            <th>Batas Sewa</th>
                            <th>Sisa Hari</th>
                            <th>Tindakan Petugas</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($q_sewa_reminder && mysqli_num_rows($q_sewa_reminder) > 0): ?>
                            <?php while ($r = mysqli_fetch_assoc($q_sewa_reminder)): 
                                $sisa_hari = (strtotime($r['tgl_selesai_sewa']) - time()) / (60 * 60 * 24);
                            ?>
                                <tr>
                                    <td>
                                        <b><?= htmlspecialchars($r['nama_lengkap']) ?></b><br>
                                        <span style="font-size: 0.75rem; background: #e0e7ff; color: #4338ca; padding: 1px 6px; border-radius: 4px; font-weight: 700;">
                                            Kamar <?= htmlspecialchars($r['nomor_kamar']) ?>
                                        </span>
                                    </td>
                                    <td><?= tgl_indo($r['tgl_selesai_sewa']) ?></td>
                                    <td>
                                        <?php if ($sisa_hari <= 7 && $sisa_hari >= 0): ?>
                                            <span class="badge badge-warning">Sisa <?= ceil($sisa_hari) ?> Hari</span>
                                        <?php elseif ($sisa_hari < 0): ?>
                                            <span class="badge badge-danger">Lewat Waktu</span>
                                        <?php else: ?>
                                            <span class="badge badge-success"><?= ceil($sisa_hari) ?> Hari</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div style="display: flex; gap: 6px;">
                                            <a href="tagihan_input.php?user=<?= $r['id_user'] ?>&kamar=<?= $r['id_kamar'] ?>" class="btn btn-primary btn-sm" title="Buat Tagihan">
                                                <i class="fa-solid fa-file-invoice"></i> Tagih
                                            </a>
                                            <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $r['no_hp']) ?>?text=Halo%20<?= urlencode($r['nama_lengkap']) ?>,%20kami%20mengingatkan%20masa%20sewa%20Kamar%20<?= urlencode($r['nomor_kamar']) ?>%20akan%20berakhir%20tanggal%20<?= urlencode(tgl_indo($r['tgl_selesai_sewa'])) ?>." target="_blank" class="btn btn-outline btn-sm" style="color: #25D366;" title="WA Reminder">
                                                <i class="fa-brands fa-whatsapp"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="4" style="text-align: center; color: var(--adm-muted); padding: 20px;">Belum ada data sewa aktif.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- TABEL 4: KELUHAN FASILITAS TERKINI -->
    <div class="card" style="margin-bottom: 0;">
        <div class="card-header">
            <h3 class="card-title"><i class="fa-solid fa-screwdriver-wrench"></i> Tabel 4: Keluhan Fasilitas Masuk</h3>
            <a href="layanan_komplain.php" class="btn btn-outline btn-sm">Buka Semua</a>
        </div>
        <div class="card-body" style="padding: 0;">
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Kamar & Pelapor</th>
                            <th>Masalah</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($q_komplain_recent && mysqli_num_rows($q_komplain_recent) > 0): ?>
                            <?php while ($kp = mysqli_fetch_assoc($q_komplain_recent)): 
                                $kp_badge = [
                                    'menunggu' => 'badge-danger',
                                    'diproses' => 'badge-warning',
                                    'selesai' => 'badge-success',
                                    'ditolak' => 'badge-secondary'
                                ][$kp['status']] ?? 'badge-secondary';
                            ?>
                                <tr>
                                    <td>
                                        <b>Kamar <?= htmlspecialchars($kp['nomor_kamar']) ?></b><br>
                                        <small style="color: var(--adm-muted);"><?= htmlspecialchars($kp['nama_lengkap']) ?></small>
                                    </td>
                                    <td>
                                        <b style="font-size: 0.85rem;"><?= htmlspecialchars($kp['judul_keluhan']) ?></b>
                                    </td>
                                    <td>
                                        <span class="badge <?= $kp_badge ?>"><?= ucfirst($kp['status']) ?></span>
                                    </td>
                                    <td>
                                        <a href="layanan_komplain.php" class="btn btn-primary btn-sm">
                                            <i class="fa-solid fa-reply"></i> Tanggapi
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="4" style="text-align: center; color: var(--adm-muted); padding: 20px;">Tidak ada keluhan fasilitas.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<!-- SCRIPT CHART.JS UNTUK INISIALISASI GRAFIK -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // 1. Grafik Donut Okupansi Kamar
    const ctxOkupansi = document.getElementById('chartOkupansiKamar');
    if (ctxOkupansi) {
        new Chart(ctxOkupansi, {
            type: 'doughnut',
            data: {
                labels: ['Tersedia', 'Terisi', 'Renovasi'],
                datasets: [{
                    data: [<?= $kamar_tersedia ?>, <?= $kamar_terisi ?>, <?= $kamar_renovasi ?>],
                    backgroundColor: ['#10b981', '#ef4444', '#f59e0b'],
                    borderWidth: 2,
                    borderColor: '#ffffff',
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                cutout: '68%'
            }
        });
    }

    // 2. Grafik Bar Status Tagihan Pembayaran
    const ctxTagihan = document.getElementById('chartStatusTagihan');
    if (ctxTagihan) {
        new Chart(ctxTagihan, {
            type: 'bar',
            data: {
                labels: ['Lunas', 'Menunggu Verifikasi', 'Belum Bayar'],
                datasets: [{
                    label: 'Nominal Tagihan (Rp)',
                    data: [<?= $nominal_lunas ?>, <?= $nominal_menunggu ?>, <?= $nominal_belum ?>],
                    backgroundColor: ['#10b981', '#6366f1', '#ef4444'],
                    borderRadius: 6,
                    barThickness: 32
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return 'Rp ' + Number(context.raw).toLocaleString('id-ID');
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                if (value >= 1000000) return (value / 1000000) + ' Jt';
                                if (value >= 1000) return (value / 1000) + ' Rb';
                                return value;
                            }
                        },
                        grid: {
                            color: '#f1f5f9'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
    }
});
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
