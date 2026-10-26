<?php
$page_title = "Dashboard Penyewa";
require_once __DIR__ . '/header.php';

$id_user = (int)$_SESSION['user_id'];

// Ambil Kamar Aktif Penyewa
$q_aktif = mysqli_query($conn, "SELECT b.*, k.nomor_kamar, k.nama_kamar, k.ukuran, k.lantai, k.foto_utama, k.harga_per_bulan, kat.nama_kategori 
                                FROM tbl_booking b 
                                JOIN tbl_kamar k ON b.id_kamar = k.id_kamar 
                                JOIN tbl_kategori_kamar kat ON k.id_kategori = kat.id_kategori 
                                WHERE b.id_user = $id_user AND b.status_booking = 'aktif' 
                                ORDER BY b.id_booking DESC LIMIT 1");
$kamar_aktif = mysqli_fetch_assoc($q_aktif);

// Ambil Tagihan Belum Bayar
$q_unpaid = mysqli_query($conn, "SELECT * FROM tbl_tagihan_pembayaran WHERE id_user = $id_user AND status_pembayaran = 'belum_bayar' ORDER BY tgl_jatuh_tempo ASC");

// Ambil Statistik
$q_tot_b = mysqli_query($conn, "SELECT COUNT(*) as tot FROM tbl_booking WHERE id_user = $id_user");
$tot_b = mysqli_fetch_assoc($q_tot_b)['tot'] ?? 0;

$q_tot_lunas = mysqli_query($conn, "SELECT SUM(nominal) as tot FROM tbl_tagihan_pembayaran WHERE id_user = $id_user AND status_pembayaran = 'lunas'");
$tot_lunas = mysqli_fetch_assoc($q_tot_lunas)['tot'] ?? 0;
?>

<!-- BANNER PERINGATAN JIKA ADA TAGIHAN BELUM BAYAR -->
<?php if ($q_unpaid && mysqli_num_rows($q_unpaid) > 0): ?>
    <div style="background: #fee2e2; border-left: 5px solid #ef4444; padding: 18px 22px; border-radius: var(--radius-sm); margin-bottom: 24px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 14px;">
        <div style="display: flex; align-items: center; gap: 14px;">
            <i class="fa-solid fa-triangle-exclamation" style="font-size: 2rem; color: #ef4444;"></i>
            <div>
                <h4 style="color: #991b1b; margin-bottom: 2px;">Anda Memiliki <?= mysqli_num_rows($q_unpaid) ?> Tagihan yang Belum Dibayar</h4>
                <p style="color: #7f1d1d; font-size: 0.88rem;">Mohon lakukan pembayaran dan unggah bukti transfer sebelum jatuh tempo.</p>
            </div>
        </div>
        <a href="tagihan_saya.php" class="btn btn-danger btn-sm"><i class="fa-solid fa-credit-card"></i> Bayar Sekarang</a>
    </div>
<?php endif; ?>

<!-- INFORMASI KAMAR AKTIF -->
<?php if ($kamar_aktif): 
    $foto_path = '../uploads/kost/' . $kamar_aktif['foto_utama'];
    $img_src = (file_exists($foto_path) && !empty($kamar_aktif['foto_utama'])) ? $foto_path : 'https://images.unsplash.com/photo-1522771739844-6a9f6d5f14af?auto=format&fit=crop&w=600&q=80';
    
    $sisa_hari = (strtotime($kamar_aktif['tgl_selesai_sewa']) - time()) / (60 * 60 * 24);
?>
    <div class="card" style="margin-bottom: 26px;">
        <div class="card-header" style="background: #eef2ff;">
            <h3 class="card-title" style="color: #4338ca;"><i class="fa-solid fa-key"></i> Kamar Kost Aktif Anda</h3>
            <span class="badge badge-success">Status Sewa Aktif</span>
        </div>
        <div class="card-body">
            <div style="display: grid; grid-template-columns: 240px 1fr; gap: 24px; align-items: center;">
                <div style="height: 160px; border-radius: var(--radius-md); overflow: hidden;">
                    <img src="<?= $img_src ?>" alt="Kamar" style="width: 100%; height: 100%; object-fit: cover;">
                </div>
                <div>
                    <h2 style="font-size: 1.5rem; font-weight: 800; color: var(--adm-text); margin-bottom: 6px;">
                        Kamar <?= htmlspecialchars($kamar_aktif['nomor_kamar']) ?> - <?= htmlspecialchars($kamar_aktif['nama_kamar']) ?>
                    </h2>
                    <p style="color: var(--adm-muted); font-size: 0.9rem; margin-bottom: 14px;">
                        <?= htmlspecialchars($kamar_aktif['nama_kategori']) ?> • Lantai <?= $kamar_aktif['lantai'] ?> • Ukuran <?= htmlspecialchars($kamar_aktif['ukuran']) ?>
                    </p>

                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 12px; background: var(--adm-bg); padding: 12px 16px; border-radius: var(--radius-sm); margin-bottom: 16px;">
                        <div>
                            <small style="color: var(--adm-muted);">Mulai Sewa:</small><br>
                            <b><?= tgl_indo($kamar_aktif['tgl_mulai_sewa']) ?></b>
                        </div>
                        <div>
                            <small style="color: var(--adm-muted);">Berakhir Sewa:</small><br>
                            <b><?= tgl_indo($kamar_aktif['tgl_selesai_sewa']) ?></b>
                        </div>
                        <div>
                            <small style="color: var(--adm-muted);">Masa Berlaku:</small><br>
                            <b style="color: <?= ($sisa_hari <= 7) ? '#ef4444' : '#059669' ?>;">
                                <?= ceil($sisa_hari) ?> Hari Lagi
                            </b>
                        </div>
                    </div>

                    <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                        <a href="komplain.php" class="btn btn-outline btn-sm">
                            <i class="fa-solid fa-wrench"></i> Laporkan Masalah Fasilitas
                        </a>
                        <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $profil['no_wa']) ?>?text=Halo%20Pengelola%20Kost,%20saya%20penghuni%20Kamar%20<?= urlencode($kamar_aktif['nomor_kamar']) ?>" target="_blank" class="btn btn-sm" style="background:#25D366; color:#fff;">
                            <i class="fa-brands fa-whatsapp"></i> Chat WhatsApp Pengelola
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php else: ?>
    <div class="card" style="margin-bottom: 26px; border-left: 4px solid var(--adm-primary);">
        <div class="card-body" style="padding: 24px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 16px;">
            <div>
                <h3 style="font-size: 1.25rem; font-weight: 700; margin-bottom: 4px;">Anda Belum Memiliki Kamar Aktif</h3>
                <p style="color: var(--adm-muted); font-size: 0.9rem;">Pilih dan ajukan pemesanan kamar impian Anda sekarang.</p>
            </div>
            <a href="../index.php#kamar" class="btn btn-primary">
                <i class="fa-solid fa-magnifying-glass"></i> Cari & Pesan Kamar
            </a>
        </div>
    </div>
<?php endif; ?>

<!-- STATISTIK RINGKAS PENYEWA -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-data">
            <span class="stat-label">Total Booking Dibuat</span>
            <span class="stat-value"><?= $tot_b ?></span>
        </div>
        <div class="stat-icon icon-indigo"><i class="fa-solid fa-calendar-check"></i></div>
    </div>

    <div class="stat-card">
        <div class="stat-data">
            <span class="stat-label">Total Pembayaran Lunas</span>
            <span class="stat-value" style="color: #059669; font-size: 1.45rem;"><?= format_rupiah($tot_lunas) ?></span>
        </div>
        <div class="stat-icon icon-emerald"><i class="fa-solid fa-receipt"></i></div>
    </div>

    <div class="stat-card">
        <div class="stat-data">
            <span class="stat-label">Tagihan Belum Lunas</span>
            <span class="stat-value" style="color: <?= $tagihan_unpaid > 0 ? '#ef4444' : '#059669' ?>;">
                <?= $tagihan_unpaid ?> Tagihan
            </span>
        </div>
        <div class="stat-icon icon-rose"><i class="fa-solid fa-clock-rotate-left"></i></div>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
