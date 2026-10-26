<?php
/**
 * Cetak Kuitansi / Invoice Digital Pembayaran Kost
 */
require_once __DIR__ . '/../koneksi.php';
require_once __DIR__ . '/../config/helper.php';

// Cek autentikasi
auth_check();

$id_tagihan = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$profil = get_profil_kost($conn);

$q = mysqli_query($conn, "SELECT t.*, u.nama_lengkap, u.no_hp, u.email, u.alamat_asal, k.nomor_kamar, k.nama_kamar, k.ukuran, kat.nama_kategori, v.nama_lengkap as verifikator_nama 
                         FROM tbl_tagihan_pembayaran t 
                         JOIN tbl_users u ON t.id_user = u.id_user 
                         JOIN tbl_kamar k ON t.id_kamar = k.id_kamar 
                         JOIN tbl_kategori_kamar kat ON k.id_kategori = kat.id_kategori 
                         LEFT JOIN tbl_users v ON t.diverifikasi_oleh = v.id_user 
                         WHERE t.id_tagihan = $id_tagihan LIMIT 1");

if (!$q || mysqli_num_rows($q) == 0) {
    die("Invoice tidak ditemukan.");
}

$inv = mysqli_fetch_assoc($q);

// Pengunjung hanya boleh melihat invoice miliknya, kecuali admin/petugas
if ($_SESSION['role'] === 'pengunjung' && $inv['id_user'] != $_SESSION['user_id']) {
    die("Akses ditolak ke invoice ini.");
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kuitansi Pembayaran #<?= htmlspecialchars($inv['kode_tagihan']) ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Helvetica Neue', Arial, sans-serif; }
        body { background: #f8fafc; color: #1e293b; padding: 40px 20px; }
        .invoice-card { max-width: 800px; margin: 0 auto; background: #fff; padding: 40px; border-radius: 12px; box-shadow: 0 4px 14px rgba(0,0,0,0.08); position: relative; border: 1px solid #e2e8f0; }
        .inv-header { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 2px solid #4f46e5; padding-bottom: 24px; margin-bottom: 24px; }
        .brand-title { font-size: 1.5rem; font-weight: 800; color: #4f46e5; }
        .inv-badge { padding: 6px 16px; border-radius: 999px; font-weight: 800; font-size: 0.85rem; text-transform: uppercase; display: inline-block; }
        .badge-lunas { background: #d1fae5; color: #065f46; border: 1px solid #10b981; }
        .badge-pending { background: #fef3c7; color: #92400e; }
        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 30px; margin-bottom: 30px; font-size: 0.92rem; }
        .table-inv { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
        .table-inv th { background: #f1f5f9; padding: 12px 16px; text-align: left; font-size: 0.85rem; text-transform: uppercase; color: #475569; border-bottom: 2px solid #e2e8f0; }
        .table-inv td { padding: 14px 16px; border-bottom: 1px solid #e2e8f0; font-size: 0.95rem; }
        .total-box { display: flex; justify-content: flex-end; margin-bottom: 40px; }
        .total-wrapper { width: 320px; background: #f8fafc; border-radius: 8px; padding: 16px; border: 1px solid #e2e8f0; }
        .inv-footer { display: flex; justify-content: space-between; align-items: flex-end; border-top: 1px solid #e2e8f0; padding-top: 24px; font-size: 0.85rem; color: #64748b; }
        .print-btn { display: inline-block; padding: 10px 20px; background: #4f46e5; color: #fff; text-decoration: none; border-radius: 8px; font-weight: 700; cursor: pointer; border: none; }
        @media print {
            body { background: #fff; padding: 0; }
            .invoice-card { box-shadow: none; border: none; padding: 0; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>

    <div class="no-print" style="max-width: 800px; margin: 0 auto 20px; display: flex; justify-content: space-between;">
        <button onclick="window.history.back()" class="print-btn" style="background: #64748b;">&larr; Kembali</button>
        <button onclick="window.print()" class="print-btn">&#128438; Cetak Kuitansi / PDF</button>
    </div>

    <div class="invoice-card">
        <!-- HEADER -->
        <div class="inv-header">
            <div>
                <h1 class="brand-title"><?= htmlspecialchars($profil['nama_kost']) ?></h1>
                <p style="color: #64748b; font-size: 0.88rem; margin-top: 4px;"><?= htmlspecialchars($profil['alamat']) ?>, <?= htmlspecialchars($profil['kota']) ?></p>
                <p style="color: #64748b; font-size: 0.85rem;">WA: <?= htmlspecialchars($profil['no_wa']) ?> | Email: <?= htmlspecialchars($profil['email']) ?></p>
            </div>
            <div style="text-align: right;">
                <h2 style="font-size: 1.3rem; font-weight: 800; color: #0f172a; margin-bottom: 6px;">KUITANSI PEMBAYARAN</h2>
                <div style="font-weight: 700; color: #4f46e5; margin-bottom: 8px;"><?= htmlspecialchars($inv['kode_tagihan']) ?></div>
                <span class="inv-badge <?= $inv['status_pembayaran'] === 'lunas' ? 'badge-lunas' : 'badge-pending' ?>">
                    STATUS: <?= str_replace('_', ' ', strtoupper($inv['status_pembayaran'])) ?>
                </span>
            </div>
        </div>

        <!-- INFO PENYEWA & DETAIL TRANSAKSI -->
        <div class="info-grid">
            <div>
                <h4 style="color: #64748b; font-size: 0.78rem; text-transform: uppercase; margin-bottom: 8px; font-weight: 700;">DITAGIHKAN KEPADA:</h4>
                <p style="font-size: 1.1rem; font-weight: 800;"><?= htmlspecialchars($inv['nama_lengkap']) ?></p>
                <p style="color: #475569;">No. HP: <?= htmlspecialchars($inv['no_hp']) ?></p>
                <p style="color: #475569;">Asal: <?= htmlspecialchars($inv['alamat_asal'] ?? '-') ?></p>
            </div>

            <div style="text-align: right;">
                <h4 style="color: #64748b; font-size: 0.78rem; text-transform: uppercase; margin-bottom: 8px; font-weight: 700;">INFORMASI SEWA:</h4>
                <p><b>Kamar <?= htmlspecialchars($inv['nomor_kamar']) ?></b> (<?= htmlspecialchars($inv['nama_kamar']) ?>)</p>
                <p style="color: #475569;">Tipe: <?= htmlspecialchars($inv['nama_kategori']) ?> (<?= htmlspecialchars($inv['ukuran']) ?>)</p>
                <p style="color: #475569;">Tgl Tagihan: <?= tgl_indo($inv['created_at']) ?></p>
                <?php if ($inv['status_pembayaran'] === 'lunas' && !empty($inv['tgl_bayar'])): ?>
                    <p style="color: #059669; font-weight: 700;">Tgl Bayar: <?= tgl_indo($inv['tgl_bayar']) ?></p>
                <?php endif; ?>
            </div>
        </div>

        <!-- TABEL RINCIAN -->
        <table class="table-inv">
            <thead>
                <tr>
                    <th>Deskripsi Tagihan</th>
                    <th>Periode</th>
                    <th>Metode Pembayaran</th>
                    <th style="text-align: right;">Jumlah</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        <b><?= htmlspecialchars($inv['judul_tagihan']) ?></b><br>
                        <small style="color: #64748b; text-transform: capitalize;">Jenis: <?= str_replace('_', ' ', $inv['jenis_biaya']) ?></small>
                    </td>
                    <td><?= htmlspecialchars($inv['periode_bulan']) ?></td>
                    <td><?= htmlspecialchars($inv['metode_pembayaran'] ?? 'Transfer Bank') ?></td>
                    <td style="text-align: right; font-weight: 700;"><?= format_rupiah($inv['nominal']) ?></td>
                </tr>
            </tbody>
        </table>

        <!-- TOTAL HARGA -->
        <div class="total-box">
            <div class="total-wrapper">
                <div style="display: flex; justify-content: space-between; margin-bottom: 8px; font-size: 0.95rem;">
                    <span>Subtotal:</span>
                    <b><?= format_rupiah($inv['nominal']) ?></b>
                </div>
                <div style="display: flex; justify-content: space-between; margin-bottom: 8px; font-size: 0.95rem;">
                    <span>Biaya Admin:</span>
                    <b>Rp 0</b>
                </div>
                <hr style="border: 0; border-top: 1px solid #cbd5e1; margin: 10px 0;">
                <div style="display: flex; justify-content: space-between; font-size: 1.15rem; font-weight: 800; color: #059669;">
                    <span>TOTAL:</span>
                    <span><?= format_rupiah($inv['nominal']) ?></span>
                </div>
            </div>
        </div>

        <!-- FOOTER & TANDA TANGAN -->
        <div class="inv-footer">
            <div>
                <p><b>Catatan:</b></p>
                <p>Kuitansi ini adalah bukti pembayaran yang sah dan dicetak secara digital oleh sistem.</p>
                <?php if (!empty($inv['verifikator_nama'])): ?>
                    <p style="margin-top: 4px; font-style: italic;">Diverifikasi oleh: <?= htmlspecialchars($inv['verifikator_nama']) ?></p>
                <?php endif; ?>
            </div>
            <div style="text-align: center; min-width: 180px;">
                <p><?= htmlspecialchars($profil['kota']) ?>, <?= tgl_indo(date('Y-m-d')) ?></p>
                <div style="height: 60px;"></div>
                <p style="font-weight: 700; text-decoration: underline;"><?= htmlspecialchars($profil['atas_nama']) ?></p>
                <p style="font-size: 0.75rem;">Pengelola Kost</p>
            </div>
        </div>
    </div>

</body>
</html>
