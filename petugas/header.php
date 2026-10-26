<?php
/**
 * Header & Navigasi Portal Petugas
 */
require_once __DIR__ . '/../koneksi.php';
require_once __DIR__ . '/../config/helper.php';

// Role petugas atau admin boleh mengakses
auth_check(['petugas', 'admin']);

$profil = get_profil_kost($conn);
$curr_user = get_current_user_data($conn);

// Hitung badge
$q_b_count = mysqli_query($conn, "SELECT COUNT(*) as tot FROM tbl_booking WHERE status_booking = 'pending'");
$pending_booking = mysqli_fetch_assoc($q_b_count)['tot'] ?? 0;

$q_t_count = mysqli_query($conn, "SELECT COUNT(*) as tot FROM tbl_tagihan_pembayaran WHERE status_pembayaran = 'menunggu_verifikasi'");
$pending_bayar = mysqli_fetch_assoc($q_t_count)['tot'] ?? 0;

$q_k_count = mysqli_query($conn, "SELECT COUNT(*) as tot FROM tbl_pengaduan_layanan WHERE status IN ('menunggu', 'diproses')");
$pending_komplain = mysqli_fetch_assoc($q_k_count)['tot'] ?? 0;

$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($page_title) ? $page_title . ' - ' : '' ?>Petugas Panel | <?= htmlspecialchars($profil['nama_kost']) ?></title>
    <link rel="stylesheet" href="../assets/css/admin.css?v=<?= time() ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Chart.js for interactive analytics -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>

<div class="admin-layout">
    <!-- SIDEBAR PETUGAS -->
    <aside class="sidebar">
        <div class="sidebar-header">
            <div class="sidebar-logo-icon" style="background: linear-gradient(135deg, #6366f1, #8b5cf6);">
                <i class="fa-solid fa-clipboard-user"></i>
            </div>
            <div>
                <h2 class="sidebar-title"><?= htmlspecialchars($profil['nama_kost']) ?></h2>
                <span class="sidebar-subtitle">Petugas Pelayanan</span>
            </div>
        </div>

        <ul class="sidebar-menu">
            <li class="sidebar-item <?= $current_page == 'index.php' ? 'active' : '' ?>">
                <a href="index.php"><i class="fa-solid fa-gauge-high"></i> Dashboard</a>
            </li>

            <li class="menu-category">Pelayanan Pengunjung</li>
            <li class="sidebar-item <?= $current_page == 'booking_verifikasi.php' ? 'active' : '' ?>">
                <a href="booking_verifikasi.php">
                    <i class="fa-solid fa-user-plus"></i> Verifikasi Booking
                    <?php if ($pending_booking > 0): ?>
                        <span class="sidebar-badge"><?= $pending_booking ?></span>
                    <?php endif; ?>
                </a>
            </li>
            <li class="sidebar-item <?= $current_page == 'penghuni.php' ? 'active' : '' ?>">
                <a href="penghuni.php"><i class="fa-solid fa-users"></i> Data Penghuni Kost</a>
            </li>

            <li class="menu-category">Keuangan & Tagihan</li>
            <li class="sidebar-item <?= $current_page == 'tagihan_input.php' ? 'active' : '' ?>">
                <a href="tagihan_input.php"><i class="fa-solid fa-file-invoice-dollar"></i> Input Tagihan & Biaya</a>
            </li>
            <li class="sidebar-item <?= $current_page == 'tagihan_list.php' ? 'active' : '' ?>">
                <a href="tagihan_list.php">
                    <i class="fa-solid fa-money-check-dollar"></i> Verifikasi Pembayaran
                    <?php if ($pending_bayar > 0): ?>
                        <span class="sidebar-badge"><?= $pending_bayar ?></span>
                    <?php endif; ?>
                </a>
            </li>

            <li class="menu-category">Kamar & Fasilitas</li>
            <li class="sidebar-item <?= $current_page == 'kamar_status.php' ? 'active' : '' ?>">
                <a href="kamar_status.php"><i class="fa-solid fa-door-open"></i> Status & Kondisi Kamar</a>
            </li>
            <li class="sidebar-item <?= $current_page == 'layanan_komplain.php' ? 'active' : '' ?>">
                <a href="layanan_komplain.php">
                    <i class="fa-solid fa-screwdriver-wrench"></i> Layanan & Komplain
                    <?php if ($pending_komplain > 0): ?>
                        <span class="sidebar-badge"><?= $pending_komplain ?></span>
                    <?php endif; ?>
                </a>
            </li>
        </ul>

        <div class="sidebar-footer" style="padding: 16px 14px; border-top: 1px solid #1e293b; display: flex; align-items: center; gap: 8px;">
            <a href="../index.php" target="_blank" class="btn btn-sidebar-web" style="flex: 1; display: inline-flex; align-items: center; justify-content: center; gap: 7px; padding: 9px 10px; font-size: 0.82rem; font-weight: 600; text-decoration: none; border-radius: 8px; background: rgba(255, 255, 255, 0.05); color: #e2e8f0; border: 1px solid #334155;" title="Buka Halaman Depan Website">
                <i class="fa-solid fa-arrow-up-right-from-square"></i> Lihat Website
            </a>
            <a href="../logout.php" class="btn btn-sidebar-logout" style="flex: 1; display: inline-flex; align-items: center; justify-content: center; gap: 7px; padding: 9px 10px; font-size: 0.82rem; font-weight: 600; text-decoration: none; border-radius: 8px; background: #ef4444; color: #ffffff; border: 1px solid #ef4444;" title="Keluar dari Panel Petugas">
                <i class="fa-solid fa-power-off"></i> Logout
            </a>
        </div>
    </aside>

    <!-- MAIN CONTENT -->
    <div class="main-wrapper">
        <!-- TOPBAR PETUGAS -->
        <header class="topbar">
            <div class="topbar-left">
                <button type="button" class="sidebar-toggle-btn" id="sidebarToggle" title="Toggle Sidebar">
                    <i class="fa-solid fa-bars"></i>
                </button>
                <div class="topbar-title">
                    <span><?= isset($page_title) ? $page_title : 'Dashboard Petugas' ?></span>
                </div>
            </div>

            <div class="topbar-right">
                <div class="user-profile-btn" style="cursor: default;">
                    <div class="user-avatar" style="background: linear-gradient(135deg, #6366f1, #8b5cf6); color: #ffffff;">
                        <i class="fa-solid fa-clipboard-user"></i>
                        <span class="status-pulse-dot"></span>
                    </div>
                    <div class="user-info">
                        <span class="user-name"><?= htmlspecialchars($curr_user['nama_lengkap'] ?? 'Petugas') ?></span>
                        <span class="user-role-badge">Petugas Pelayanan</span>
                    </div>
                </div>
            </div>
        </header>

        <main class="page-content">
            <?php show_flash(); ?>
