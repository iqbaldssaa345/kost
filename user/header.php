<?php
/**
 * Header Portal Pengunjung / Penyewa
 */
require_once __DIR__ . '/../koneksi.php';
require_once __DIR__ . '/../config/helper.php';

// Pastikan user telah login
auth_check();

$profil = get_profil_kost($conn);
$curr_user = get_current_user_data($conn);
$id_u = (int)$_SESSION['user_id'];

// Cek tagihan belum bayar
$q_tag_count = mysqli_query($conn, "SELECT COUNT(*) as tot FROM tbl_tagihan_pembayaran WHERE id_user = $id_u AND status_pembayaran = 'belum_bayar'");
$tagihan_unpaid = mysqli_fetch_assoc($q_tag_count)['tot'] ?? 0;

// Cek kamar aktif penyewa
$q_kamar_user = mysqli_query($conn, "SELECT b.id_booking, k.nomor_kamar, k.nama_kamar 
                                    FROM tbl_booking b 
                                    JOIN tbl_kamar k ON b.id_kamar = k.id_kamar 
                                    WHERE b.id_user = $id_u AND b.status_booking = 'aktif' 
                                    LIMIT 1");
$kamar_saya = mysqli_fetch_assoc($q_kamar_user);

// Cek komplain aktif
$q_komp_user = mysqli_query($conn, "SELECT COUNT(*) as tot FROM tbl_pengaduan_layanan WHERE id_user = $id_u AND status IN ('menunggu', 'diproses')");
$komplain_aktif = mysqli_fetch_assoc($q_komp_user)['tot'] ?? 0;

// Nomor WA Pengelola
$clean_wa = preg_replace('/[^0-9]/', '', $profil['no_wa'] ?? '');
if (substr($clean_wa, 0, 1) === '0') {
    $clean_wa = '62' . substr($clean_wa, 1);
}

$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($page_title) ? $page_title . ' - ' : '' ?>Panel Penyewa | <?= htmlspecialchars($profil['nama_kost']) ?></title>
    <link rel="stylesheet" href="../assets/css/admin.css?v=<?= time() ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

<div class="admin-layout">
    <!-- SIDEBAR PENYEWA -->
    <aside class="sidebar">
        <div class="sidebar-header">
            <div class="sidebar-logo-icon" style="background: linear-gradient(135deg, #0ea5e9, #6366f1);">
                <i class="fa-solid fa-house-user"></i>
            </div>
            <div>
                <h2 class="sidebar-title"><?= htmlspecialchars($profil['nama_kost']) ?></h2>
                <span class="sidebar-subtitle">Portal Penyewa</span>
            </div>
        </div>

        <ul class="sidebar-menu">
            <li class="sidebar-item <?= $current_page == 'index.php' ? 'active' : '' ?>">
                <a href="index.php"><i class="fa-solid fa-gauge-high"></i> Dashboard Saya</a>
            </li>

            <li class="menu-category">Sewa & Tagihan</li>
            <li class="sidebar-item <?= in_array($current_page, ['tagihan_saya.php', 'bayar_upload.php']) ? 'active' : '' ?>">
                <a href="tagihan_saya.php">
                    <i class="fa-solid fa-file-invoice-dollar"></i> Tagihan & Bayar
                    <?php if ($tagihan_unpaid > 0): ?>
                        <span class="sidebar-badge"><?= $tagihan_unpaid ?></span>
                    <?php endif; ?>
                </a>
            </li>
            <li class="sidebar-item <?= $current_page == 'booking_saya.php' ? 'active' : '' ?>">
                <a href="booking_saya.php"><i class="fa-solid fa-calendar-check"></i> Riwayat Sewa</a>
            </li>

            <li class="menu-category">Layanan & Akun</li>
            <li class="sidebar-item <?= $current_page == 'komplain.php' ? 'active' : '' ?>">
                <a href="komplain.php">
                    <i class="fa-solid fa-screwdriver-wrench"></i> Layanan / Komplain
                    <?php if ($komplain_aktif > 0): ?>
                        <span class="sidebar-badge" style="background: #0ea5e9;"><?= $komplain_aktif ?></span>
                    <?php endif; ?>
                </a>
            </li>
            <li class="sidebar-item <?= $current_page == 'profil.php' ? 'active' : '' ?>">
                <a href="profil.php"><i class="fa-solid fa-user-gear"></i> Profil Saya</a>
            </li>
        </ul>

        <div class="sidebar-footer" style="padding: 16px 14px; border-top: 1px solid #1e293b; display: flex; align-items: center; gap: 8px;">
            <a href="../index.php" target="_blank" class="btn btn-sidebar-web" style="flex: 1; display: inline-flex; align-items: center; justify-content: center; gap: 7px; padding: 9px 10px; font-size: 0.82rem; font-weight: 600; text-decoration: none; border-radius: 8px; background: rgba(255, 255, 255, 0.05); color: #e2e8f0; border: 1px solid #334155;" title="Buka Halaman Depan Website">
                <i class="fa-solid fa-arrow-up-right-from-square"></i> Lihat Website
            </a>
            <a href="../logout.php" class="btn btn-sidebar-logout" style="flex: 1; display: inline-flex; align-items: center; justify-content: center; gap: 7px; padding: 9px 10px; font-size: 0.82rem; font-weight: 600; text-decoration: none; border-radius: 8px; background: #ef4444; color: #ffffff; border: 1px solid #ef4444;" title="Keluar dari Panel Penyewa">
                <i class="fa-solid fa-power-off"></i> Logout
            </a>
        </div>
    </aside>

    <!-- MAIN CONTENT -->
    <div class="main-wrapper">
        <!-- TOPBAR PENYEWA -->
        <header class="topbar">
            <div class="topbar-left">
                <button type="button" class="sidebar-toggle-btn" id="sidebarToggle" title="Toggle Sidebar">
                    <i class="fa-solid fa-bars"></i>
                </button>
                <div class="topbar-title">
                    <span><?= isset($page_title) ? $page_title : 'Dashboard Penyewa' ?></span>
                    <?php if ($kamar_saya): ?>
                        <span class="topbar-room-pill" title="Kamar Aktif Anda">
                            <i class="fa-solid fa-key"></i> Kamar <?= htmlspecialchars($kamar_saya['nomor_kamar']) ?>
                        </span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="topbar-right">
                <div class="user-profile-btn" style="cursor: default;">
                    <div class="user-avatar" style="background: linear-gradient(135deg, #0ea5e9, #6366f1); color: #ffffff;">
                        <i class="fa-solid fa-user"></i>
                        <span class="status-pulse-dot"></span>
                    </div>
                    <div class="user-info">
                        <span class="user-name"><?= htmlspecialchars($curr_user['nama_lengkap'] ?? 'Penyewa') ?></span>
                        <span class="user-role-badge">Penghuni / Penyewa</span>
                    </div>
                </div>
            </div>
        </header>

        <main class="page-content">
            <?php show_flash(); ?>
