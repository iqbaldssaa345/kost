<?php
/**
 * Laporan Keuangan & Okupansi (Admin)
 * Dilengkapi Grafik Interaktif & Export ke Microsoft Excel
 */
require_once __DIR__ . '/../koneksi.php';
require_once __DIR__ . '/../config/helper.php';

auth_check(['admin']);

// 1. FILTER TANGGAL PERIODE
$tgl_mulai = isset($_GET['tgl_mulai']) && !empty($_GET['tgl_mulai']) ? clean($conn, $_GET['tgl_mulai']) : date('Y-m-01');
$tgl_selesai = isset($_GET['tgl_selesai']) && !empty($_GET['tgl_selesai']) ? clean($conn, $_GET['tgl_selesai']) : date('Y-m-t');

// Query Utama: Pemasukan Lunas
$sql_lunas = "SELECT t.*, u.nama_lengkap, u.no_hp, k.nomor_kamar, k.nama_kamar, kat.nama_kategori
              FROM tbl_tagihan_pembayaran t 
              JOIN tbl_users u ON t.id_user = u.id_user 
              JOIN tbl_kamar k ON t.id_kamar = k.id_kamar 
              JOIN tbl_kategori_kamar kat ON k.id_kategori = kat.id_kategori
              WHERE t.status_pembayaran = 'lunas' 
              AND DATE(t.created_at) BETWEEN '$tgl_mulai' AND '$tgl_selesai'
              ORDER BY t.created_at DESC";
$q_lunas = mysqli_query($conn, $sql_lunas);

// Query Tagihan Belum Lunas (Piutang / Menunggu Verifikasi)
$sql_piutang = "SELECT t.*, u.nama_lengkap, u.no_hp, k.nomor_kamar, k.nama_kamar 
                FROM tbl_tagihan_pembayaran t 
                JOIN tbl_users u ON t.id_user = u.id_user 
                JOIN tbl_kamar k ON t.id_kamar = k.id_kamar 
                WHERE t.status_pembayaran IN ('belum_bayar', 'menunggu_verifikasi')
                AND DATE(t.created_at) BETWEEN '$tgl_mulai' AND '$tgl_selesai'
                ORDER BY t.tgl_jatuh_tempo ASC";
$q_piutang = mysqli_query($conn, $sql_piutang);

// Query Summary Statistik Keuangan
$q_stat = mysqli_query($conn, "SELECT 
    SUM(CASE WHEN status_pembayaran = 'lunas' THEN nominal ELSE 0 END) as total_lunas,
    COUNT(CASE WHEN status_pembayaran = 'lunas' THEN 1 ELSE NULL END) as total_transaksi_lunas,
    SUM(CASE WHEN status_pembayaran IN ('belum_bayar', 'menunggu_verifikasi') THEN nominal ELSE 0 END) as total_piutang,
    COUNT(CASE WHEN status_pembayaran IN ('belum_bayar', 'menunggu_verifikasi') THEN 1 ELSE NULL END) as total_transaksi_piutang
    FROM tbl_tagihan_pembayaran 
    WHERE DATE(created_at) BETWEEN '$tgl_mulai' AND '$tgl_selesai'");
$stat = mysqli_fetch_assoc($q_stat);

// Query Breakdown per Jenis Biaya untuk Grafik Donat
$q_breakdown = mysqli_query($conn, "SELECT jenis_biaya, SUM(nominal) as subtotal, COUNT(*) as jumlah 
                                    FROM tbl_tagihan_pembayaran 
                                    WHERE status_pembayaran = 'lunas' 
                                    AND DATE(created_at) BETWEEN '$tgl_mulai' AND '$tgl_selesai'
                                    GROUP BY jenis_biaya");
$biaya_labels = [];
$biaya_values = [];
$breakdown_data = [];
while ($b = mysqli_fetch_assoc($q_breakdown)) {
    $nama_b = ucfirst(str_replace('_', ' ', $b['jenis_biaya']));
    $biaya_labels[] = $nama_b;
    $biaya_values[] = (float)$b['subtotal'];
    $breakdown_data[] = $b;
}

// Query Tren Harian untuk Grafik Garis/Batang
$q_trend = mysqli_query($conn, "SELECT DATE(created_at) as tgl, SUM(nominal) as total 
                                FROM tbl_tagihan_pembayaran 
                                WHERE status_pembayaran = 'lunas' 
                                AND DATE(created_at) BETWEEN '$tgl_mulai' AND '$tgl_selesai'
                                GROUP BY DATE(created_at) 
                                ORDER BY tgl ASC");
$trend_labels = [];
$trend_values = [];
while ($tr = mysqli_fetch_assoc($q_trend)) {
    $trend_labels[] = date('d M', strtotime($tr['tgl']));
    $trend_values[] = (float)$tr['total'];
}

// Query Okupansi Kamar Saat Ini
$q_kamar = mysqli_query($conn, "SELECT 
    COUNT(*) as total_kamar,
    SUM(CASE WHEN status = 'terisi' THEN 1 ELSE 0 END) as terisi,
    SUM(CASE WHEN status = 'tersedia' THEN 1 ELSE 0 END) as tersedia,
    SUM(CASE WHEN status = 'renovasi' THEN 1 ELSE 0 END) as renovasi
    FROM tbl_kamar");
$kamar_stat = mysqli_fetch_assoc($q_kamar);

// ==========================================
// 2. FITUR EXPORT EXCEL (.XLS)
// ==========================================
if (isset($_GET['export']) && $_GET['export'] === 'excel') {
    if (ob_get_level()) {
        ob_end_clean();
    }

    $filename = "Laporan_Keuangan_Kost_" . date('Ymd', strtotime($tgl_mulai)) . "_sd_" . date('Ymd', strtotime($tgl_selesai)) . ".xls";

    header("Content-Type: application/vnd.ms-excel; charset=utf-8");
    header("Content-Disposition: attachment; filename=\"$filename\"");
    header("Pragma: no-cache");
    header("Expires: 0");

    $profil_kost = get_profil_kost($conn);
    ?>
    <html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
        <!--[if gte mso 9]>
        <xml>
        <x:ExcelWorkbook>
        <x:ExcelWorksheets>
        <x:ExcelWorksheet>
        <x:Name>Laporan Keuangan</x:Name>
        <x:WorksheetOptions><x:DisplayGridlines/></x:WorksheetOptions>
        </x:ExcelWorksheet>
        </x:ExcelWorksheets>
        </x:ExcelWorkbook>
        </xml>
        <![endif]-->
        <style>
            body { font-family: Calibri, Arial, sans-serif; font-size: 11pt; }
            .header-title { font-size: 16pt; font-weight: bold; text-align: center; color: #1e3a8a; }
            .header-sub { font-size: 11pt; text-align: center; color: #475569; }
            .table-data { border-collapse: collapse; width: 100%; margin-top: 15px; }
            .table-data th { background-color: #2563eb; color: #ffffff; font-weight: bold; border: 1px solid #1d4ed8; padding: 8px; text-align: center; }
            .table-data td { border: 1px solid #cbd5e1; padding: 6px 8px; vertical-align: middle; }
            .table-summary { border-collapse: collapse; margin-bottom: 20px; width: 50%; }
            .table-summary td { padding: 5px 8px; border: 1px solid #e2e8f0; }
            .text-center { text-align: center; }
            .text-right { text-align: right; }
            .fw-bold { font-weight: bold; }
            .bg-total { background-color: #dbeafe; font-weight: bold; }
            .num { mso-number-format:"\#\,\#\#0"; }
        </style>
    </head>
    <body>
        <table style="width: 100%;">
            <tr>
                <td colspan="8" class="header-title"><?= strtoupper(htmlspecialchars($profil_kost['nama_kost'])) ?></td>
            </tr>
            <tr>
                <td colspan="8" class="header-sub">LAPORAN KEUANGAN & PEMASUKAN SEWA KOST</td>
            </tr>
            <tr>
                <td colspan="8" class="header-sub">Periode: <?= tgl_indo($tgl_mulai) ?> s/d <?= tgl_indo($tgl_selesai) ?> | Dicetak pada: <?= date('d/m/Y H:i') ?></td>
            </tr>
            <tr><td colspan="8"></td></tr>
        </table>

        <!-- RINGKASAN EKSEKUTIF -->
        <table class="table-summary">
            <tr style="background-color: #f1f5f9; font-weight: bold;">
                <td colspan="2">RINGKASAN EKSEKUTIF KEUANGAN</td>
            </tr>
            <tr>
                <td>Total Pemasukan Lunas</td>
                <td class="text-right fw-bold" style="color: #15803d;"><?= format_rupiah($stat['total_lunas'] ?? 0) ?></td>
            </tr>
            <tr>
                <td>Jumlah Transaksi Lunas</td>
                <td class="text-right"><?= (int)($stat['total_transaksi_lunas'] ?? 0) ?> Transaksi</td>
            </tr>
            <tr>
                <td>Total Piutang / Belum Lunas</td>
                <td class="text-right fw-bold" style="color: #b91c1c;"><?= format_rupiah($stat['total_piutang'] ?? 0) ?></td>
            </tr>
            <tr>
                <td>Tingkat Okupansi Kamar</td>
                <td class="text-right fw-bold"><?= $kamar_stat['total_kamar'] > 0 ? round(($kamar_stat['terisi'] / $kamar_stat['total_kamar']) * 100, 1) : 0 ?>% (<?= $kamar_stat['terisi'] ?> / <?= $kamar_stat['total_kamar'] ?> Kamar)</td>
            </tr>
        </table>

        <!-- TABEL RINCIAN TRANSAKSI -->
        <table class="table-data">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Tanggal Bayar</th>
                    <th>Kode Invoice</th>
                    <th>Nama Penyewa</th>
                    <th>Kamar Kost</th>
                    <th>Kategori / Tipe</th>
                    <th>Keterangan Tagihan</th>
                    <th>Metode Bayar</th>
                    <th>Nominal (Rp)</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $no = 1; 
                $grand_total = 0;
                mysqli_data_seek($q_lunas, 0);
                if (mysqli_num_rows($q_lunas) > 0): 
                    while ($row = mysqli_fetch_assoc($q_lunas)): 
                        $grand_total += $row['nominal'];
                ?>
                    <tr>
                        <td class="text-center"><?= $no++ ?></td>
                        <td class="text-center"><?= date('d/m/Y', strtotime($row['created_at'])) ?></td>
                        <td class="text-center" style="font-weight: bold;"><?= htmlspecialchars($row['kode_tagihan']) ?></td>
                        <td><?= htmlspecialchars($row['nama_lengkap']) ?></td>
                        <td><?= htmlspecialchars($row['nama_kamar']) ?> (No. <?= htmlspecialchars($row['nomor_kamar']) ?>)</td>
                        <td><?= htmlspecialchars($row['nama_kategori']) ?></td>
                        <td><?= htmlspecialchars($row['judul_tagihan']) ?></td>
                        <td class="text-center"><?= strtoupper(htmlspecialchars($row['metode_pembayaran'])) ?></td>
                        <td class="text-right num"><?= (float)$row['nominal'] ?></td>
                    </tr>
                <?php 
                    endwhile; 
                ?>
                    <tr class="bg-total">
                        <td colspan="8" class="text-right fw-bold">TOTAL PEMASUKAN BERSIH:</td>
                        <td class="text-right fw-bold num" style="color: #15803d;"><?= (float)$grand_total ?></td>
                    </tr>
                <?php else: ?>
                    <tr>
                        <td colspan="9" class="text-center">Tidak ada catatan transaksi pada periode ini.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </body>
    </html>
    <?php
    exit();
}

$page_title = "Laporan Keuangan & Okupansi";
require_once __DIR__ . '/header.php';
?>

<!-- FILTER PERIODE & ACTION BAR -->
<div class="card" style="margin-bottom: 24px;">
    <div class="card-body" style="padding: 18px 24px;">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
            
            <!-- FORM FILTER TANGGAL -->
            <form action="laporan.php" method="GET" style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
                <div style="display: flex; align-items: center; gap: 8px;">
                    <label style="font-size: 0.88rem; font-weight: 700; color: var(--adm-text);"><i class="fa-solid fa-calendar-days" style="color: var(--adm-primary);"></i> Periode:</label>
                    <input type="date" name="tgl_mulai" class="form-control" style="width: 155px; padding: 8px 12px;" value="<?= $tgl_mulai ?>">
                    <span style="color: var(--adm-muted); font-weight: 600;">s/d</span>
                    <input type="date" name="tgl_selesai" class="form-control" style="width: 155px; padding: 8px 12px;" value="<?= $tgl_selesai ?>">
                </div>

                <button type="submit" class="btn btn-primary btn-sm" style="padding: 8px 16px;">
                    <i class="fa-solid fa-filter"></i> Terapkan Filter
                </button>

                <!-- Shortcut Periode Cepat -->
                <div style="display: flex; gap: 6px;">
                    <a href="laporan.php?tgl_mulai=<?= date('Y-m-01') ?>&tgl_selesai=<?= date('Y-m-t') ?>" class="btn btn-outline btn-sm" style="font-size: 0.8rem; padding: 6px 10px;">Bulan Ini</a>
                    <a href="laporan.php?tgl_mulai=<?= date('Y-m-d', strtotime('first day of last month')) ?>&tgl_selesai=<?= date('Y-m-d', strtotime('last day of last month')) ?>" class="btn btn-outline btn-sm" style="font-size: 0.8rem; padding: 6px 10px;">Bulan Lalu</a>
                    <a href="laporan.php?tgl_mulai=<?= date('Y-01-01') ?>&tgl_selesai=<?= date('Y-12-31') ?>" class="btn btn-outline btn-sm" style="font-size: 0.8rem; padding: 6px 10px;">Tahun Ini</a>
                </div>
            </form>

            <!-- TOMBOL EXPORT EXCEL & CETAK -->
            <div style="display: flex; gap: 10px;">
                <a href="laporan.php?tgl_mulai=<?= $tgl_mulai ?>&tgl_selesai=<?= $tgl_selesai ?>&export=excel" class="btn btn-sm" style="background: #107c41; color: #ffffff; font-weight: 700; box-shadow: 0 2px 6px rgba(16,124,65,0.3);">
                    <i class="fa-solid fa-file-excel"></i> Export ke Excel (.xls)
                </a>
                <button type="button" class="btn btn-outline btn-sm" onclick="window.print()" style="background: #ffffff;">
                    <i class="fa-solid fa-print"></i> Cetak / PDF
                </button>
            </div>

        </div>
    </div>
</div>

<!-- STATS SUMMARY CARDS -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-data">
            <span class="stat-label">Total Pemasukan Lunas</span>
            <span class="stat-value" style="color: #059669; font-size: 1.6rem;"><?= format_rupiah($stat['total_lunas'] ?? 0) ?></span>
            <small style="color: var(--adm-muted);"><?= (int)($stat['total_transaksi_lunas'] ?? 0) ?> Transaksi Pembayaran Berhasil</small>
        </div>
        <div class="stat-icon icon-emerald"><i class="fa-solid fa-money-bill-trend-up"></i></div>
    </div>

    <div class="stat-card">
        <div class="stat-data">
            <span class="stat-label">Piutang / Belum Lunas</span>
            <span class="stat-value" style="color: #e11d48; font-size: 1.6rem;"><?= format_rupiah($stat['total_piutang'] ?? 0) ?></span>
            <small style="color: var(--adm-muted);"><?= (int)($stat['total_transaksi_piutang'] ?? 0) ?> Tagihan Menunggu Bayar</small>
        </div>
        <div class="stat-icon icon-rose"><i class="fa-solid fa-hand-holding-dollar"></i></div>
    </div>

    <div class="stat-card">
        <div class="stat-data">
            <span class="stat-label">Tingkat Okupansi Kamar</span>
            <span class="stat-value" style="color: #0284c7; font-size: 1.6rem;">
                <?= $kamar_stat['total_kamar'] > 0 ? round(($kamar_stat['terisi'] / $kamar_stat['total_kamar']) * 100, 1) : 0 ?>%
            </span>
            <small style="color: var(--adm-muted);"><?= $kamar_stat['terisi'] ?> Terisi • <?= $kamar_stat['tersedia'] ?> Tersedia • <?= $kamar_stat['renovasi'] ?> Renovasi</small>
        </div>
        <div class="stat-icon icon-blue"><i class="fa-solid fa-door-open"></i></div>
    </div>
</div>

<!-- BAGIAN GRAFIK LAPORAN -->
<div style="display: grid; grid-template-columns: 1.3fr 0.7fr; gap: 24px; margin-bottom: 24px;">
    
    <!-- GRAFIK 1: TREN PENDAPATAN -->
    <div class="card">
        <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
            <h3 class="card-title"><i class="fa-solid fa-chart-area"></i> Tren Pemasukan Periode Ini</h3>
            <span class="badge badge-info" style="font-weight: 600;"><?= tgl_indo($tgl_mulai) ?> - <?= tgl_indo($tgl_selesai) ?></span>
        </div>
        <div class="card-body">
            <div style="position: relative; height: 280px; width: 100%;">
                <canvas id="chartTrendPendapatan"></canvas>
            </div>
        </div>
    </div>

    <!-- GRAFIK 2: KOMPOSISI JENIS BIAYA -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><i class="fa-solid fa-chart-pie"></i> Komposisi Jenis Pendapatan</h3>
        </div>
        <div class="card-body">
            <div style="position: relative; height: 280px; width: 100%;">
                <canvas id="chartKomposisiBiaya"></canvas>
            </div>
        </div>
    </div>

</div>

<!-- TABEL 1: RINCIAN PEMASUKAN LUNAS -->
<div class="card" style="margin-bottom: 24px;">
    <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
        <h3 class="card-title">
            <i class="fa-solid fa-table-list"></i> Rincian Pemasukan Lunas (<?= tgl_indo($tgl_mulai) ?> s/d <?= tgl_indo($tgl_selesai) ?>)
        </h3>
        <span class="badge badge-success"><?= mysqli_num_rows($q_lunas) ?> Transaksi</span>
    </div>
    <div class="card-body" style="padding: 0;">
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width: 50px;">No</th>
                        <th>Tanggal Bayar</th>
                        <th>Kode Invoice</th>
                        <th>Nama Penyewa</th>
                        <th>Kamar Kost</th>
                        <th>Keterangan Tagihan</th>
                        <th>Metode Bayar</th>
                        <th style="text-align: right;">Nominal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($q_lunas && mysqli_num_rows($q_lunas) > 0): $no = 1; $total = 0; ?>
                        <?php while ($r = mysqli_fetch_assoc($q_lunas)): $total += $r['nominal']; ?>
                            <tr>
                                <td><?= $no++ ?></td>
                                <td>
                                    <b><?= date('d/m/Y', strtotime($r['created_at'])) ?></b><br>
                                    <small style="color: var(--adm-muted);"><?= date('H:i', strtotime($r['created_at'])) ?> WIB</small>
                                </td>
                                <td>
                                    <span style="font-weight: 700; color: var(--adm-primary); font-family: monospace; font-size: 0.95rem;">
                                        <?= htmlspecialchars($r['kode_tagihan']) ?>
                                    </span>
                                </td>
                                <td>
                                    <b><?= htmlspecialchars($r['nama_lengkap']) ?></b><br>
                                    <small style="color: var(--adm-muted);"><?= htmlspecialchars($r['no_hp']) ?></small>
                                </td>
                                <td>
                                    <span style="font-weight: 600; color: var(--adm-text);"><?= htmlspecialchars($r['nama_kamar']) ?></span><br>
                                    <span class="badge badge-secondary">Kamar <?= htmlspecialchars($r['nomor_kamar']) ?></span>
                                </td>
                                <td>
                                    <span><?= htmlspecialchars($r['judul_tagihan']) ?></span><br>
                                    <small style="color: var(--adm-muted); text-transform: capitalize;"><?= str_replace('_', ' ', $r['jenis_biaya']) ?></small>
                                </td>
                                <td>
                                    <span class="badge badge-info" style="text-transform: uppercase;">
                                        <i class="fa-solid fa-credit-card"></i> <?= htmlspecialchars($r['metode_pembayaran'] ?: 'Transfer') ?>
                                    </span>
                                </td>
                                <td style="text-align: right;">
                                    <b style="color: #059669; font-size: 0.95rem;"><?= format_rupiah($r['nominal']) ?></b>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                        <tr style="background: #f8fafc; font-weight: 800; border-top: 2px solid var(--adm-border);">
                            <td colspan="7" style="text-align: right; font-size: 1rem; color: var(--adm-text);">TOTAL PEMASUKAN BERSIH:</td>
                            <td style="text-align: right; color: #059669; font-size: 1.2rem; font-weight: 800;"><?= format_rupiah($total) ?></td>
                        </tr>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" style="text-align: center; padding: 30px; color: var(--adm-muted);">
                                <i class="fa-solid fa-inbox" style="font-size: 2rem; margin-bottom: 8px; display: block;"></i>
                                Tidak ada transaksi pemasukan lunas pada rentang tanggal ini.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- TABEL 2: REKAPITULASI PENDAPATAN BERDASARKAN JENIS BIAYA -->
<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-bottom: 24px;">
    
    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><i class="fa-solid fa-coins"></i> Rekapitulasi per Jenis Biaya</h3>
        </div>
        <div class="card-body" style="padding: 0;">
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Jenis Biaya</th>
                            <th style="text-align: center;">Jumlah Transaksi</th>
                            <th style="text-align: right;">Subtotal (Rp)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($breakdown_data)): $tot_trans = 0; $tot_sub = 0; ?>
                            <?php foreach ($breakdown_data as $bd): 
                                $tot_trans += $bd['jumlah'];
                                $tot_sub += $bd['subtotal'];
                            ?>
                                <tr>
                                    <td><b style="text-transform: capitalize;"><?= str_replace('_', ' ', $bd['jenis_biaya']) ?></b></td>
                                    <td style="text-align: center;"><span class="badge badge-secondary"><?= $bd['jumlah'] ?> kali</span></td>
                                    <td style="text-align: right; font-weight: 700; color: #059669;"><?= format_rupiah($bd['subtotal']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <tr style="background: #f8fafc; font-weight: 800;">
                                <td>TOTAL</td>
                                <td style="text-align: center;"><?= $tot_trans ?> Transaksi</td>
                                <td style="text-align: right; color: #059669;"><?= format_rupiah($tot_sub) ?></td>
                            </tr>
                        <?php else: ?>
                            <tr><td colspan="3" style="text-align: center; color: var(--adm-muted);">Belum ada data.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- TABEL 3: TAGIHAN TERTUNGGAK / PIUTANG -->
    <div class="card">
        <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
            <h3 class="card-title"><i class="fa-solid fa-circle-exclamation" style="color: #e11d48;"></i> Tagihan Belum Lunas (Piutang)</h3>
            <span class="badge badge-danger"><?= mysqli_num_rows($q_piutang) ?> Tertunggak</span>
        </div>
        <div class="card-body" style="padding: 0;">
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Penyewa</th>
                            <th>Kamar</th>
                            <th>Jatuh Tempo</th>
                            <th style="text-align: right;">Nominal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($q_piutang && mysqli_num_rows($q_piutang) > 0): ?>
                            <?php while ($p = mysqli_fetch_assoc($q_piutang)): ?>
                                <tr>
                                    <td>
                                        <b><?= htmlspecialchars($p['nama_lengkap']) ?></b><br>
                                        <small style="color: var(--adm-muted);"><?= htmlspecialchars($p['judul_tagihan']) ?></small>
                                    </td>
                                    <td><span class="badge badge-secondary"><?= htmlspecialchars($p['nomor_kamar']) ?></span></td>
                                    <td>
                                        <span style="color: #e11d48; font-weight: 600; font-size: 0.85rem;">
                                            <?= tgl_indo($p['tgl_jatuh_tempo']) ?>
                                        </span>
                                    </td>
                                    <td style="text-align: right; font-weight: 700; color: #e11d48;">
                                        <?= format_rupiah($p['nominal']) ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="4" style="text-align: center; color: var(--adm-muted);">Tidak ada tagihan tertunggak pada periode ini.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<!-- SCRIPT CHART.JS INITIALIZATION -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // 1. Grafik Tren Pemasukan
    const trendCtx = document.getElementById('chartTrendPendapatan');
    if (trendCtx) {
        new Chart(trendCtx, {
            type: 'line',
            data: {
                labels: <?= json_encode($trend_labels ?: ['Tidak Ada Data']) ?>,
                datasets: [{
                    label: 'Pemasukan (Rp)',
                    data: <?= json_encode($trend_values ?: [0]) ?>,
                    borderColor: '#4f46e5',
                    backgroundColor: 'rgba(79, 70, 229, 0.12)',
                    borderWidth: 3,
                    pointBackgroundColor: '#4f46e5',
                    pointRadius: 4,
                    pointHoverRadius: 6,
                    fill: true,
                    tension: 0.35
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return ' Pemasukan: Rp ' + new Intl.NumberFormat('id-ID').format(context.raw);
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                if (value >= 1000000) return 'Rp ' + (value/1000000).toFixed(1) + ' Jt';
                                if (value >= 1000) return 'Rp ' + (value/1000).toFixed(0) + ' Rb';
                                return 'Rp ' + value;
                            }
                        },
                        grid: { color: '#f1f5f9' }
                    },
                    x: {
                        grid: { display: false }
                    }
                }
            }
        });
    }

    // 2. Grafik Komposisi Jenis Pendapatan
    const pieCtx = document.getElementById('chartKomposisiBiaya');
    if (pieCtx) {
        new Chart(pieCtx, {
            type: 'doughnut',
            data: {
                labels: <?= json_encode($biaya_labels ?: ['Belum Ada Transaksi']) ?>,
                datasets: [{
                    data: <?= json_encode($biaya_values ?: [1]) ?>,
                    backgroundColor: [
                        '#4f46e5', '#10b981', '#f59e0b', '#06b6d4', '#ec4899', '#8b5cf6'
                    ],
                    borderWidth: 2,
                    borderColor: '#ffffff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            boxWidth: 12,
                            padding: 14,
                            font: { size: 11, family: "'Inter', sans-serif" }
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return ' ' + context.label + ': Rp ' + new Intl.NumberFormat('id-ID').format(context.raw);
                            }
                        }
                    }
                },
                cutout: '65%'
            }
        });
    }
});
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
