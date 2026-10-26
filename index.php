<?php
/**
 * Halaman Utama (Homepage) / Katalog Kost
 * Sistem Manajemen Kost Lengkap
 */
require_once __DIR__ . '/koneksi.php';
require_once __DIR__ . '/config/helper.php';

$profil = get_profil_kost($conn);

// Filter Pencarian
$kategori_filter = isset($_GET['kategori']) ? (int)$_GET['kategori'] : 0;
$jenis_filter = isset($_GET['jenis']) ? clean($conn, $_GET['jenis']) : '';
$harga_max = isset($_GET['harga_max']) ? (float)$_GET['harga_max'] : 0;
$keyword = isset($_GET['keyword']) ? clean($conn, $_GET['keyword']) : '';

// Query Kamar
$sql = "SELECT k.*, kat.nama_kategori, kat.jenis_penghuni 
        FROM tbl_kamar k 
        JOIN tbl_kategori_kamar kat ON k.id_kategori = kat.id_kategori 
        WHERE 1=1";

if ($kategori_filter > 0) {
    $sql .= " AND k.id_kategori = $kategori_filter";
}
if (!empty($jenis_filter)) {
    $sql .= " AND kat.jenis_penghuni = '$jenis_filter'";
}
if ($harga_max > 0) {
    $sql .= " AND k.harga_per_bulan <= $harga_max";
}
if (!empty($keyword)) {
    $sql .= " AND (k.nama_kamar LIKE '%$keyword%' OR k.nomor_kamar LIKE '%$keyword%' OR k.deskripsi LIKE '%$keyword%')";
}

$sql .= " ORDER BY (k.status = 'tersedia') DESC, k.id_kamar ASC";
$result_kamar = mysqli_query($conn, $sql);

// Query Kategori untuk filter
$kategori_list = mysqli_query($conn, "SELECT * FROM tbl_kategori_kamar ORDER BY id_kategori ASC");

// Query Fasilitas Umum
$fasilitas_list = mysqli_query($conn, "SELECT * FROM tbl_fasilitas WHERE tipe = 'bersama' LIMIT 8");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($profil['nama_kost']) ?> - <?= htmlspecialchars($profil['slogan']) ?></title>
    <link rel="stylesheet" href="assets/css/style.css?v=<?= time() ?>">
    <!-- Font Awesome Icons -->
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
                    <li><a href="index.php" class="nav-link active">Beranda</a></li>
                    <li><a href="#kamar" class="nav-link">Pilihan Kamar</a></li>
                    <li><a href="#fasilitas" class="nav-link">Fasilitas</a></li>
                    <li><a href="#lokasi" class="nav-link">Lokasi & Kontak</a></li>
                </ul>

                <div class="nav-actions">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <?php if ($_SESSION['role'] === 'admin'): ?>
                            <a href="admin/index.php" class="btn btn-primary btn-sm"><i class="fa-solid fa-gauge"></i> Panel Admin</a>
                        <?php elseif ($_SESSION['role'] === 'petugas'): ?>
                            <a href="petugas/index.php" class="btn btn-primary btn-sm"><i class="fa-solid fa-clipboard-user"></i> Panel Petugas</a>
                        <?php else: ?>
                            <a href="user/index.php" class="btn btn-primary btn-sm"><i class="fa-solid fa-user"></i> Akun Saya</a>
                        <?php endif; ?>
                        <a href="logout.php" class="btn btn-outline btn-sm"><i class="fa-solid fa-right-from-bracket"></i> Keluar</a>
                    <?php else: ?>
                        <a href="login.php" class="btn btn-outline btn-sm"><i class="fa-solid fa-arrow-right-to-bracket"></i> Masuk</a>
                        <a href="register.php" class="btn btn-primary btn-sm"><i class="fa-solid fa-user-plus"></i> Daftar</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </header>

    <!-- HERO SECTION -->
    <section class="hero-section">
        <div class="container">
            <div class="hero-grid" style="margin-bottom: 36px;">
                <div class="hero-content">
                    <div class="hero-badge">
                        <i class="fa-solid fa-shield-halved"></i> Hunian Nyaman, Bersih & Strategis
                    </div>
                    <h1 class="hero-title">
                        Temukan Kenyamanan Tinggal di <span><?= htmlspecialchars($profil['nama_kost']) ?></span>
                    </h1>
                    <p class="hero-desc">
                        <?= htmlspecialchars($profil['deskripsi']) ?>
                    </p>
                    <div class="hero-actions">
                        <a href="#kamar" class="btn btn-primary"><i class="fa-solid fa-door-open"></i> Lihat Kamar Tersedia</a>
                        <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $profil['no_wa']) ?>?text=Halo%20Admin%20Griya%20Kost,%20saya%20tertarik%20tanya%20kamar%20kost" target="_blank" class="btn btn-outline" style="background:#25D366; color:#fff; border-color:#25D366;">
                            <i class="fa-brands fa-whatsapp"></i> Hubungi via WhatsApp
                        </a>
                    </div>
                </div>

                <div class="hero-visual">
                    <div class="hero-card-preview">
                        <img src="https://images.unsplash.com/photo-1522771739844-6a9f6d5f14af?auto=format&fit=crop&w=800&q=80" alt="Kost Room">
                    </div>
                </div>
            </div>

            <!-- SEARCH & FILTER BOX -->
            <div class="search-filter-box" style="margin-top: 36px; position: relative; z-index: 10;">
                <form action="index.php#kamar" method="GET" class="filter-form">
                    <div class="form-group">
                        <label><i class="fa-solid fa-magnifying-glass"></i> Cari Nama / Nomor</label>
                        <input type="text" name="keyword" class="form-control" placeholder="Contoh: Deluxe, A-01" value="<?= htmlspecialchars($keyword) ?>">
                    </div>

                    <div class="form-group">
                        <label><i class="fa-solid fa-venus-mars"></i> Tipe Penghuni</label>
                        <select name="jenis" class="form-control">
                            <option value="">Semua Penghuni</option>
                            <option value="Putra" <?= $jenis_filter == 'Putra' ? 'selected' : '' ?>>Khusus Putra</option>
                            <option value="Putri" <?= $jenis_filter == 'Putri' ? 'selected' : '' ?>>Khusus Putri</option>
                            <option value="Campur" <?= $jenis_filter == 'Campur' ? 'selected' : '' ?>>Campur</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label><i class="fa-solid fa-layer-group"></i> Kategori Kamar</label>
                        <select name="kategori" class="form-control">
                            <option value="0">Semua Tipe Kamar</option>
                            <?php if ($kategori_list): ?>
                                <?php while ($kat = mysqli_fetch_assoc($kategori_list)): ?>
                                    <option value="<?= $kat['id_kategori'] ?>" <?= $kategori_filter == $kat['id_kategori'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($kat['nama_kategori']) ?>
                                    </option>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </select>
                    </div>

                    <div class="filter-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fa-solid fa-filter"></i> Filter
                        </button>
                        <?php if (!empty($keyword) || !empty($jenis_filter) || $kategori_filter > 0): ?>
                            <a href="index.php#kamar" class="btn btn-outline" title="Reset Filter">
                                <i class="fa-solid fa-rotate-left"></i> Reset
                            </a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
    </section>

    <!-- SECTION DAFTAR KAMAR -->
    <section class="section" id="kamar">
        <div class="container">
            <div class="section-header">
                <span class="section-tag">Pilihan Kamar</span>
                <h2 class="section-title">Katalog Kamar Kost</h2>
                <p class="section-subtitle">Pilih kamar yang sesuai dengan kebutuhan dan kenyamanan Anda</p>
            </div>

            <div class="room-grid">
                <?php if ($result_kamar && mysqli_num_rows($result_kamar) > 0): ?>
                    <?php while ($kamar = mysqli_fetch_assoc($result_kamar)): 
                        // Query Fasilitas Kamar
                        $id_k = $kamar['id_kamar'];
                        $q_fas = mysqli_query($conn, "SELECT f.nama_fasilitas, f.ikon FROM tbl_kamar_fasilitas kf JOIN tbl_fasilitas f ON kf.id_fasilitas = f.id_fasilitas WHERE kf.id_kamar = $id_k LIMIT 4");
                        
                        // Gambar Kamar (Fallback jika upload belum ada)
                        $foto_path = 'uploads/kost/' . $kamar['foto_utama'];
                        $img_src = (file_exists($foto_path) && !empty($kamar['foto_utama'])) 
                            ? $foto_path 
                            : 'https://images.unsplash.com/photo-1598928506311-c55ded91a20c?auto=format&fit=crop&w=600&q=80';
                    ?>
                        <div class="room-card">
                            <div class="room-thumb">
                                <img src="<?= $img_src ?>" alt="<?= htmlspecialchars($kamar['nama_kamar']) ?>">
                                
                                <span class="room-badge badge-<?= strtolower($kamar['jenis_penghuni']) ?>">
                                    <?= htmlspecialchars($kamar['jenis_penghuni']) ?>
                                </span>

                                <span class="room-status-badge status-<?= $kamar['status'] ?>">
                                    <?= ucfirst($kamar['status']) ?>
                                </span>
                            </div>

                            <div class="room-body">
                                <h3 class="room-name"><?= htmlspecialchars($kamar['nama_kamar']) ?> (<?= htmlspecialchars($kamar['nomor_kamar']) ?>)</h3>
                                
                                <div class="room-meta">
                                    <span><i class="fa-solid fa-arrows-up-down-left-right"></i> <?= htmlspecialchars($kamar['ukuran']) ?></span>
                                    <span><i class="fa-solid fa-building"></i> Lantai <?= $kamar['lantai'] ?></span>
                                </div>

                                <div class="room-facilities">
                                    <?php while ($fas = mysqli_fetch_assoc($q_fas)): ?>
                                        <span class="facility-chip">
                                            <i class="fa-solid <?= htmlspecialchars($fas['ikon'] ?? 'fa-check') ?>"></i>
                                            <?= htmlspecialchars($fas['nama_fasilitas']) ?>
                                        </span>
                                    <?php endwhile; ?>
                                </div>

                                <div class="room-footer">
                                    <div class="room-price-box">
                                        <span class="price-label">Mulai dari</span>
                                        <span class="price-amount"><?= format_rupiah($kamar['harga_per_bulan']) ?> <span class="price-unit">/bln</span></span>
                                        <?php if (!empty($kamar['harga_per_tahun']) && $kamar['harga_per_tahun'] > 0): ?>
                                            <span style="display: block; font-size: 0.76rem; color: #059669; font-weight: 700; margin-top: 2px;">
                                                <i class="fa-solid fa-tag"></i> <?= format_rupiah($kamar['harga_per_tahun']) ?>/thn
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    <a href="detail_kamar.php?id=<?= $kamar['id_kamar'] ?>" class="btn btn-outline btn-sm">
                                        Detail <i class="fa-solid fa-arrow-right"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div style="grid-column: 1 / -1; text-align: center; padding: 40px; background: #fff; border-radius: 12px; border: 1px solid var(--border);">
                        <i class="fa-solid fa-bed" style="font-size: 3rem; color: #94a3b8; margin-bottom: 12px;"></i>
                        <h3>Tidak Ada Kamar yang Sesuai Filter</h3>
                        <p style="color: var(--slate); margin-top: 6px;">Silakan atur ulang filter pencarian Anda untuk melihat kamar lainnya.</p>
                        <a href="index.php#kamar" class="btn btn-primary btn-sm" style="margin-top: 14px;">Reset Filter</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- SECTION FASILITAS UMUM -->
    <section class="section" id="fasilitas" style="background: #ffffff;">
        <div class="container">
            <div class="section-header">
                <span class="section-tag">Keunggulan</span>
                <h2 class="section-title">Fasilitas Bersama Terbaik</h2>
                <p class="section-subtitle">Kenyamanan penghuni adalah prioritas utama dengan fasilitas modern</p>
            </div>

            <div class="facility-card-grid">
                <?php if ($fasilitas_list && mysqli_num_rows($fasilitas_list) > 0): ?>
                    <?php while ($f = mysqli_fetch_assoc($fasilitas_list)): ?>
                        <div class="facility-card">
                            <div class="facility-icon-circle">
                                <i class="fa-solid <?= htmlspecialchars($f['ikon'] ?? 'fa-circle-check') ?>"></i>
                            </div>
                            <h4 class="facility-title"><?= htmlspecialchars($f['nama_fasilitas']) ?></h4>
                            <p class="facility-desc"><?= htmlspecialchars($f['keterangan'] ?? 'Fasilitas terawat dan dapat dinikmati bersama.') ?></p>
                        </div>
                    <?php endwhile; ?>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- SECTION LOKASI & KONTAK -->
    <section class="section" id="lokasi">
        <div class="container">
            <div class="section-header">
                <span class="section-tag">Lokasi</span>
                <h2 class="section-title">Akses Mudah & Strategis</h2>
                <p class="section-subtitle">Dekat dengan kampus, perkantoran, minimarket, dan halte transportasi umum</p>
            </div>

            <div style="background: #fff; border-radius: var(--radius-lg); border: 1px solid var(--border); padding: 30px; display: grid; grid-template-columns: 1fr 1fr; gap: 30px; align-items: center;">
                <div>
                    <h3 style="font-size: 1.4rem; font-weight: 700; margin-bottom: 12px;"><?= htmlspecialchars($profil['nama_kost']) ?></h3>
                    <p style="color: var(--slate); margin-bottom: 18px;"><i class="fa-solid fa-location-dot" style="color: var(--primary);"></i> <?= htmlspecialchars($profil['alamat']) ?>, <?= htmlspecialchars($profil['kota']) ?></p>
                    
                    <div style="display: flex; flex-direction: column; gap: 10px; margin-bottom: 24px;">
                        <div><i class="fa-solid fa-phone" style="width: 20px; color: var(--primary);"></i> <b>Telepon:</b> <?= htmlspecialchars($profil['no_telepon']) ?></div>
                        <div><i class="fa-brands fa-whatsapp" style="width: 20px; color: #25D366;"></i> <b>WhatsApp:</b> <?= htmlspecialchars($profil['no_wa']) ?></div>
                        <div><i class="fa-solid fa-envelope" style="width: 20px; color: var(--primary);"></i> <b>Email:</b> <?= htmlspecialchars($profil['email']) ?></div>
                        <div><i class="fa-solid fa-credit-card" style="width: 20px; color: var(--primary);"></i> <b>Pembayaran:</b> <?= htmlspecialchars($profil['nama_bank']) ?> - <?= htmlspecialchars($profil['no_rekening']) ?> (a/n <?= htmlspecialchars($profil['atas_nama']) ?>)</div>
                    </div>

                    <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $profil['no_wa']) ?>?text=Halo,%20saya%20mau%20jadwalkan%20survey%20kost" target="_blank" class="btn btn-primary">
                        <i class="fa-solid fa-calendar-check"></i> Jadwalkan Survey Kamar
                    </a>
                </div>

                <div style="border-radius: var(--radius-md); overflow: hidden; height: 280px; background: #e2e8f0;">
                    <!-- Static Google Maps Frame Placeholder -->
                    <iframe 
                        width="100%" 
                        height="100%" 
                        frameborder="0" 
                        scrolling="no" 
                        marginheight="0" 
                        marginwidth="0" 
                        src="https://maps.google.com/maps?q=<?= urlencode($profil['alamat'] . ' ' . $profil['kota']) ?>&t=&z=15&ie=UTF8&iwloc=&output=embed">
                    </iframe>
                </div>
            </div>
        </div>
    </section>

    <!-- FOOTER -->
    <footer class="footer">
        <div class="container">
            <div class="footer-grid">
                <div class="footer-brand">
                    <h3><?= htmlspecialchars($profil['nama_kost']) ?></h3>
                    <p><?= htmlspecialchars($profil['slogan']) ?></p>
                    <p style="margin-top: 10px; font-size: 0.85rem;"><i class="fa-solid fa-location-dot"></i> <?= htmlspecialchars($profil['alamat']) ?></p>
                </div>

                <div class="footer-col">
                    <h4>Navigasi</h4>
                    <ul class="footer-links">
                        <li><a href="index.php">Beranda</a></li>
                        <li><a href="#kamar">Pilihan Kamar</a></li>
                        <li><a href="#fasilitas">Fasilitas</a></li>
                        <li><a href="#lokasi">Lokasi & Kontak</a></li>
                    </ul>
                </div>

                <div class="footer-col">
                    <h4>Portal Akses</h4>
                    <ul class="footer-links">
                        <li><a href="login.php"><i class="fa-solid fa-user-shield"></i> Login Pengguna</a></li>
                        <li><a href="register.php"><i class="fa-solid fa-user-plus"></i> Daftar Pencari Kost</a></li>
                        <li><a href="admin/index.php"><i class="fa-solid fa-lock"></i> Portal Admin</a></li>
                        <li><a href="petugas/index.php"><i class="fa-solid fa-user-gear"></i> Portal Petugas</a></li>
                    </ul>
                </div>

                <div class="footer-col">
                    <h4>Kontak Pengelola</h4>
                    <ul class="footer-links">
                        <li><i class="fa-brands fa-whatsapp"></i> WA: <?= htmlspecialchars($profil['no_wa']) ?></li>
                        <li><i class="fa-solid fa-phone"></i> Telp: <?= htmlspecialchars($profil['no_telepon']) ?></li>
                        <li><i class="fa-solid fa-envelope"></i> <?= htmlspecialchars($profil['email']) ?></li>
                    </ul>
                </div>
            </div>

            <div class="footer-bottom">
                <p>&copy; <?= date('Y') ?> <?= htmlspecialchars($profil['nama_kost']) ?>. Hak Cipta Dilindungi.</p>
            </div>
        </div>
    </footer>

    <script src="assets/js/main.js"></script>
</body>
</html>
