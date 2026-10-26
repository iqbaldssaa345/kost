<?php
/**
 * Detail Kamar & Formulir Pemesanan Kost Online
 */
require_once __DIR__ . '/koneksi.php';
require_once __DIR__ . '/config/helper.php';

$profil = get_profil_kost($conn);

$id_kamar = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id_kamar <= 0) {
    header("Location: index.php");
    exit();
}

// Ambil Data Kamar
$q_kamar = mysqli_query($conn, "SELECT k.*, kat.nama_kategori, kat.jenis_penghuni 
                               FROM tbl_kamar k 
                               JOIN tbl_kategori_kamar kat ON k.id_kategori = kat.id_kategori 
                               WHERE k.id_kamar = $id_kamar LIMIT 1");

if (!$q_kamar || mysqli_num_rows($q_kamar) == 0) {
    die("Kamar tidak ditemukan. <a href='index.php'>Kembali ke beranda</a>");
}

$kamar = mysqli_fetch_assoc($q_kamar);

// Ambil Fasilitas Kamar
$q_fas = mysqli_query($conn, "SELECT f.* FROM tbl_kamar_fasilitas kf 
                             JOIN tbl_fasilitas f ON kf.id_fasilitas = f.id_fasilitas 
                             WHERE kf.id_kamar = $id_kamar");

// Ambil Galeri Foto Kamar
$q_galeri = mysqli_query($conn, "SELECT * FROM tbl_foto_kamar WHERE id_kamar = $id_kamar");

// Proses Pengajuan Booking
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['btn_booking'])) {
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php?msg=harus_login_untuk_booking&redirect=detail_kamar.php?id=" . $id_kamar);
        exit();
    }

    $id_user = (int)$_SESSION['user_id'];
    $tgl_mulai = clean($conn, $_POST['tgl_mulai_sewa']);
    $durasi = (int)$_POST['durasi_bulan'];
    $catatan = clean($conn, $_POST['catatan']);

    if (empty($tgl_mulai) || $durasi <= 0) {
        $error = "Silakan lengkapi tanggal mulai sewa dan durasi sewa yang valid.";
    } elseif ($kamar['status'] !== 'tersedia') {
        $error = "Maaf, kamar ini saat ini sedang tidak tersedia untuk dipesan.";
    } else {
        // Hitung tanggal selesai sewa
        $tgl_selesai = date('Y-m-d', strtotime("+$durasi months", strtotime($tgl_mulai)));
        $total_biaya = $kamar['harga_per_bulan'] * $durasi;
        $kode_booking = generate_kode('BK');

        // Simpan Booking ke Database
        $stmt = mysqli_prepare($conn, "INSERT INTO tbl_booking (kode_booking, id_user, id_kamar, tgl_mulai_sewa, durasi_bulan, tgl_selesai_sewa, total_biaya, catatan, status_booking) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending')");
        mysqli_stmt_bind_param($stmt, "siisidss", $kode_booking, $id_user, $id_kamar, $tgl_mulai, $durasi, $tgl_selesai, $total_biaya, $catatan);

        if (mysqli_stmt_execute($stmt)) {
            $id_booking_baru = mysqli_insert_id($conn);
            
            // Buat invoice tagihan awal
            $kode_tagihan = generate_kode('INV');
            $judul_tagihan = "Pembayaran Awal Sewa " . $kamar['nama_kamar'] . " ($durasi Bulan)";
            $periode = date('F Y', strtotime($tgl_mulai)) . " - " . date('F Y', strtotime($tgl_selesai));
            $jatuh_tempo = date('Y-m-d', strtotime('+3 days'));

            $stmt_inv = mysqli_prepare($conn, "INSERT INTO tbl_tagihan_pembayaran (kode_tagihan, id_booking, id_user, id_kamar, judul_tagihan, jenis_biaya, periode_bulan, nominal, tgl_jatuh_tempo, status_pembayaran) VALUES (?, ?, ?, ?, ?, 'sewa_kost', ?, ?, ?, 'belum_bayar')");
            mysqli_stmt_bind_param($stmt_inv, "siiisdds", $kode_tagihan, $id_booking_baru, $id_user, $id_kamar, $judul_tagihan, $periode, $total_biaya, $jatuh_tempo);
            mysqli_stmt_execute($stmt_inv);

            set_flash('success', "Pemesanan kamar berhasil diajukan! Kode Booking: $kode_booking. Silakan lakukan pembayaran pada tagihan Anda.");
            header("Location: user/booking_saya.php");
            exit();
        } else {
            $error = "Terjadi kesalahan saat memproses booking: " . mysqli_error($conn);
        }
    }
}

// Foto utama
$foto_path = 'uploads/kost/' . $kamar['foto_utama'];
$img_src = (file_exists($foto_path) && !empty($kamar['foto_utama'])) 
    ? $foto_path 
    : 'https://images.unsplash.com/photo-1522771739844-6a9f6d5f14af?auto=format&fit=crop&w=800&q=80';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($kamar['nama_kamar']) ?> - <?= htmlspecialchars($profil['nama_kost']) ?></title>
    <link rel="stylesheet" href="assets/css/style.css?v=<?= time() ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

    <!-- NAVBAR -->
    <header class="navbar">
        <div class="container">
            <div class="nav-wrapper">
                <a href="index.php" class="brand-logo">
                    <div class="brand-icon"><i class="fa-solid fa-hotel"></i></div>
                    <span><?= htmlspecialchars($profil['nama_kost']) ?></span>
                </a>
                
                <ul class="nav-menu">
                    <li><a href="index.php" class="nav-link">Beranda</a></li>
                    <li><a href="index.php#kamar" class="nav-link">Pilihan Kamar</a></li>
                    <li><a href="index.php#fasilitas" class="nav-link">Fasilitas</a></li>
                </ul>

                <div class="nav-actions">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <a href="user/index.php" class="btn btn-primary btn-sm"><i class="fa-solid fa-user"></i> Panel Saya</a>
                        <a href="logout.php" class="btn btn-outline btn-sm">Keluar</a>
                    <?php else: ?>
                        <a href="login.php" class="btn btn-outline btn-sm">Masuk</a>
                        <a href="register.php" class="btn btn-primary btn-sm">Daftar</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </header>

    <div class="container" style="padding-top: 40px; padding-bottom: 60px;">
        
        <div style="margin-bottom: 24px;">
            <a href="index.php#kamar" class="btn btn-outline btn-sm">
                <i class="fa-solid fa-arrow-left"></i> Kembali ke Katalog Kamar
            </a>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert-box danger"><?= $error ?></div>
        <?php endif; ?>

        <div style="display: grid; grid-template-columns: 1.3fr 0.7fr; gap: 36px; align-items: start;">
            
            <!-- KOLOM KIRI: FOTO, DESKRIPSI, FASILITAS -->
            <div>
                <div style="border-radius: var(--radius-lg); overflow: hidden; box-shadow: var(--shadow-md); margin-bottom: 20px; max-height: 440px; background: #e2e8f0;">
                    <img src="<?= $img_src ?>" alt="<?= htmlspecialchars($kamar['nama_kamar']) ?>" style="width:100%; height:440px; object-fit:cover; cursor:pointer;" onclick="openImageModal('<?= $img_src ?>')">
                </div>

                <!-- Galeri Foto Tambahan -->
                <?php if ($q_galeri && mysqli_num_rows($q_galeri) > 0): ?>
                    <div style="display: flex; gap: 12px; margin-bottom: 28px; overflow-x: auto; padding-bottom: 8px;">
                        <?php while ($g = mysqli_fetch_assoc($q_galeri)): 
                            $g_path = 'uploads/kost/' . $g['nama_file'];
                            $g_src = file_exists($g_path) ? $g_path : 'https://images.unsplash.com/photo-1598928506311-c55ded91a20c?auto=format&fit=crop&w=400&q=80';
                        ?>
                            <div style="width: 100px; height: 75px; border-radius: 8px; overflow: hidden; flex-shrink: 0; cursor: pointer; border: 2px solid var(--border);" onclick="openImageModal('<?= $g_src ?>')">
                                <img src="<?= $g_src ?>" alt="Galeri" style="width:100%; height:100%; object-fit:cover;">
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php endif; ?>

                <div style="background: #fff; border-radius: var(--radius-lg); border: 1px solid var(--border); padding: 30px; margin-bottom: 30px;">
                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px;">
                        <h1 style="font-size: 1.8rem; font-weight: 800;"><?= htmlspecialchars($kamar['nama_kamar']) ?> (<?= htmlspecialchars($kamar['nomor_kamar']) ?>)</h1>
                        <span class="room-status-badge status-<?= $kamar['status'] ?>" style="font-size: 0.85rem; padding: 6px 14px;">
                            Status: <?= ucfirst($kamar['status']) ?>
                        </span>
                    </div>

                    <div style="display: flex; gap: 20px; color: var(--slate); font-size: 0.95rem; margin-bottom: 24px;">
                        <span><i class="fa-solid fa-venus-mars"></i> <?= htmlspecialchars($kamar['jenis_penghuni']) ?></span>
                        <span><i class="fa-solid fa-arrows-up-down-left-right"></i> Ukuran: <?= htmlspecialchars($kamar['ukuran']) ?></span>
                        <span><i class="fa-solid fa-building"></i> Lantai <?= $kamar['lantai'] ?></span>
                        <span><i class="fa-solid fa-layer-group"></i> <?= htmlspecialchars($kamar['nama_kategori']) ?></span>
                    </div>

                    <hr style="border: 0; border-top: 1px solid var(--border); margin: 20px 0;">

                    <h3 style="font-size: 1.2rem; font-weight: 700; margin-bottom: 12px;">Deskripsi Kamar</h3>
                    <p style="color: var(--slate); line-height: 1.8; margin-bottom: 26px;">
                        <?= nl2br(htmlspecialchars($kamar['deskripsi'] ?? 'Kamar kost nyaman dengan fasilitas terawat.')) ?>
                    </p>

                    <h3 style="font-size: 1.2rem; font-weight: 700; margin-bottom: 16px;">Fasilitas Termasuk</h3>
                    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 14px;">
                        <?php if ($q_fas && mysqli_num_rows($q_fas) > 0): ?>
                            <?php while ($f = mysqli_fetch_assoc($q_fas)): ?>
                                <div style="display: flex; align-items: center; gap: 10px; padding: 10px 14px; background: var(--bg-light); border-radius: var(--radius-sm); border: 1px solid var(--border);">
                                    <i class="fa-solid <?= htmlspecialchars($f['ikon'] ?? 'fa-check') ?>" style="color: var(--primary); font-size: 1.1rem;"></i>
                                    <span style="font-size: 0.9rem; font-weight: 600;"><?= htmlspecialchars($f['nama_fasilitas']) ?></span>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <p style="color: var(--light-slate);">Fasilitas standar kost.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- KOLOM KANAN: FORM SEWA & HARGA -->
            <div>
                <div style="background: #fff; border-radius: var(--radius-lg); border: 1px solid var(--border); padding: 30px; box-shadow: var(--shadow-md); position: sticky; top: 100px;">
                    <div style="margin-bottom: 20px; border-bottom: 1px solid var(--border); padding-bottom: 16px;">
                        <span style="font-size: 0.85rem; color: var(--slate); font-weight: 600; text-transform: uppercase;">Pilihan Tarif Sewa</span>
                        <div style="font-size: 2rem; font-weight: 800; color: var(--primary);" id="hargaPerBulan" data-harga="<?= $kamar['harga_per_bulan'] ?>" data-harga-tahun="<?= $kamar['harga_per_tahun'] ?? ($kamar['harga_per_bulan'] * 11) ?>">
                            <?= format_rupiah($kamar['harga_per_bulan']) ?> <span style="font-size: 1rem; color: var(--slate); font-weight: 500;">/ bulan</span>
                        </div>
                        
                        <?php if (!empty($kamar['harga_per_tahun']) && $kamar['harga_per_tahun'] > 0): ?>
                            <div style="font-size: 0.9rem; color: #059669; font-weight: 700; margin-top: 6px; background: #ecfdf5; padding: 6px 10px; border-radius: 6px; display: inline-block;">
                                <i class="fa-solid fa-tag"></i> Paket Tahunan Hemat: <b><?= format_rupiah($kamar['harga_per_tahun']) ?></b> / tahun
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($kamar['harga_per_hari']) && $kamar['harga_per_hari'] > 0): ?>
                            <div style="font-size: 0.85rem; color: var(--slate); margin-top: 6px;">
                                Atau sewa harian: <b><?= format_rupiah($kamar['harga_per_hari']) ?></b> / hari
                            </div>
                        <?php endif; ?>
                    </div>

                    <?php if ($kamar['status'] === 'tersedia'): ?>
                        <form action="detail_kamar.php?id=<?= $id_kamar ?>" method="POST">
                            <input type="hidden" name="total_biaya" id="totalBiayaInput" value="<?= $kamar['harga_per_bulan'] ?>">
                            
                            <div class="form-group">
                                <label for="tglMulai"><i class="fa-solid fa-calendar-days"></i> Tanggal Mulai Masuk</label>
                                <input type="date" name="tgl_mulai_sewa" id="tglMulai" class="form-control" required min="<?= date('Y-m-d') ?>" value="<?= date('Y-m-d') ?>">
                            </div>

                            <div class="form-group">
                                <label for="durasiSewa"><i class="fa-solid fa-clock"></i> Pilihan Periode Sewa</label>
                                <select name="durasi_bulan" id="durasiSewa" class="form-control" required onchange="hitungTotalSewa()">
                                    <option value="1">1 Bulan</option>
                                    <option value="3">3 Bulan (1 Triwulan)</option>
                                    <option value="6">6 Bulan (1 Semester)</option>
                                    <option value="12">12 Bulan (Paket 1 Tahun Hemat)</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label><i class="fa-solid fa-comment-dots"></i> Catatan Tambahan (Opsional)</label>
                                <textarea name="catatan" class="form-control" rows="2" placeholder="Contoh: Bawa kendaraan motor, perkiraan tiba sore hari"></textarea>
                            </div>

                            <div style="background: var(--primary-light); border-radius: var(--radius-sm); padding: 16px; margin-bottom: 20px;">
                                <div style="display: flex; justify-content: space-between; font-size: 0.85rem; color: var(--slate); margin-bottom: 6px;">
                                    <span>Estimasi Berakhir:</span>
                                    <b id="tglSelesaiPreview"><?= tgl_indo(date('Y-m-d', strtotime('+1 month'))) ?></b>
                                </div>
                                <div style="display: flex; justify-content: space-between; font-size: 1.05rem; font-weight: 700; color: var(--primary);">
                                    <span>Total Biaya:</span>
                                    <span id="totalBiayaPreview"><?= format_rupiah($kamar['harga_per_bulan']) ?></span>
                                </div>
                            </div>

                            <?php if (isset($_SESSION['user_id'])): ?>
                                <button type="submit" name="btn_booking" class="btn btn-primary btn-block" style="padding: 14px;">
                                    <i class="fa-solid fa-paper-plane"></i> Ajukan Pemesanan Sekarang
                                </button>
                            <?php else: ?>
                                <a href="login.php?redirect=detail_kamar.php?id=<?= $id_kamar ?>" class="btn btn-primary btn-block" style="padding: 14px;">
                                    <i class="fa-solid fa-right-to-bracket"></i> Login untuk Memesan Kamar
                                </a>
                                <p style="font-size: 0.78rem; text-align: center; color: var(--slate); margin-top: 8px;">
                                    Belum punya akun? <a href="register.php" style="color: var(--primary); font-weight: 600;">Daftar gratis disini</a>
                                </p>
                            <?php endif; ?>
                        </form>
                    <?php else: ?>
                            <i class="fa-solid fa-lock" style="font-size: 2.5rem; color: #ef4444; margin-bottom: 12px;"></i>
                            <h4 style="color: #991b1b; margin-bottom: 6px;">Kamar Tidak Tersedia</h4>
                            <p style="font-size: 0.85rem; color: var(--slate); margin-bottom: 18px;">
                                Kamar ini sedang <b><?= $kamar['status'] ?></b>. Silakan cari kamar lain yang berstatus tersedia.
                            </p>
                            <a href="index.php#kamar" class="btn btn-outline btn-block">Cari Kamar Lain</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- FOOTER -->
    <footer class="footer">
        <div class="container">
            <div class="footer-bottom">
                <p>&copy; <?= date('Y') ?> <?= htmlspecialchars($profil['nama_kost']) ?>. Hak Cipta Dilindungi.</p>
            </div>
        </div>
    </footer>

    <script src="assets/js/main.js"></script>
</body>
</html>
