<?php
/**
 * Header & Navigasi Portal Admin
 */
require_once __DIR__ . '/../koneksi.php';
require_once __DIR__ . '/../config/helper.php';

// Pastikan hanya role admin yang boleh masuk
auth_check(['admin']);

$profil = get_profil_kost($conn);
$curr_user = get_current_user_data($conn);

// Hitung badge pending
$q_b_count = mysqli_query($conn, "SELECT COUNT(*) as tot FROM tbl_booking WHERE status_booking = 'pending'");
$pending_booking = mysqli_fetch_assoc($q_b_count)['tot'] ?? 0;

$q_t_count = mysqli_query($conn, "SELECT COUNT(*) as tot FROM tbl_tagihan_pembayaran WHERE status_pembayaran = 'menunggu_verifikasi'");
$pending_bayar = mysqli_fetch_assoc($q_t_count)['tot'] ?? 0;

$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($page_title) ? $page_title . ' - ' : '' ?>Admin Panel | <?= htmlspecialchars($profil['nama_kost']) ?></title>
    <link rel="stylesheet" href="../assets/css/admin.css?v=<?= time() ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Chart.js CDN -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>

<div class="admin-layout">
    <!-- SIDEBAR -->
    <aside class="sidebar">
        <div class="sidebar-header">
            <div class="sidebar-logo-icon"><i class="fa-solid fa-shield-halved"></i></div>
            <div>
                <h2 class="sidebar-title"><?= htmlspecialchars($profil['nama_kost']) ?></h2>
                <span class="sidebar-subtitle">Administrator</span>
            </div>
        </div>

        <ul class="sidebar-menu">
            <li class="sidebar-item <?= $current_page == 'index.php' ? 'active' : '' ?>">
                <a href="index.php"><i class="fa-solid fa-gauge-high"></i> Dashboard</a>
            </li>

            <li class="menu-category">Manajemen Kamar</li>
            <li class="sidebar-item <?= in_array($current_page, ['kamar.php', 'kamar_tambah.php', 'kamar_edit.php']) ? 'active' : '' ?>">
                <a href="kamar.php"><i class="fa-solid fa-door-open"></i> Data Kamar</a>
            </li>
            <li class="sidebar-item <?= $current_page == 'fasilitas.php' ? 'active' : '' ?>">
                <a href="fasilitas.php"><i class="fa-solid fa-list-check"></i> Fasilitas Kost</a>
            </li>

            <li class="menu-category">Transaksi & Penghuni</li>
            <li class="sidebar-item <?= $current_page == 'booking.php' ? 'active' : '' ?>">
                <a href="booking.php">
                    <i class="fa-solid fa-calendar-check"></i> Pemesanan / Sewa
                    <?php if ($pending_booking > 0): ?>
                        <span class="sidebar-badge"><?= $pending_booking ?></span>
                    <?php endif; ?>
                </a>
            </li>
            <li class="sidebar-item <?= $current_page == 'tagihan.php' ? 'active' : '' ?>">
                <a href="tagihan.php">
                    <i class="fa-solid fa-receipt"></i> Data Tagihan
                    <?php if ($pending_bayar > 0): ?>
                        <span class="sidebar-badge"><?= $pending_bayar ?></span>
                    <?php endif; ?>
                </a>
            </li>
            <li class="sidebar-item <?= $current_page == 'laporan.php' ? 'active' : '' ?>">
                <a href="laporan.php"><i class="fa-solid fa-chart-pie"></i> Laporan Keuangan</a>
            </li>

            <li class="menu-category">Pengaturan & User</li>
            <li class="sidebar-item <?= $current_page == 'users.php' ? 'active' : '' ?>">
                <a href="users.php"><i class="fa-solid fa-users-gear"></i> Kelola Pengguna</a>
            </li>
            <li class="sidebar-item <?= $current_page == 'profil_kost.php' ? 'active' : '' ?>">
                <a href="profil_kost.php"><i class="fa-solid fa-sliders"></i> Profil & Kontak Kos</a>
            </li>
        </ul>

        <div class="sidebar-footer" style="padding: 16px 14px; border-top: 1px solid #1e293b; display: flex; align-items: center; gap: 8px;">
            <a href="../index.php" target="_blank" class="btn btn-sidebar-web" style="flex: 1; display: inline-flex; align-items: center; justify-content: center; gap: 7px; padding: 9px 10px; font-size: 0.82rem; font-weight: 600; text-decoration: none; border-radius: 8px; background: rgba(255, 255, 255, 0.05); color: #e2e8f0; border: 1px solid #334155;" title="Buka Halaman Depan Website">
                <i class="fa-solid fa-arrow-up-right-from-square"></i> Lihat Website
            </a>
            <a href="../logout.php" class="btn btn-sidebar-logout" style="flex: 1; display: inline-flex; align-items: center; justify-content: center; gap: 7px; padding: 9px 10px; font-size: 0.82rem; font-weight: 600; text-decoration: none; border-radius: 8px; background: #ef4444; color: #ffffff; border: 1px solid #ef4444;" title="Keluar dari Panel Admin">
                <i class="fa-solid fa-power-off"></i> Logout
            </a>
        </div>
    </aside>

    <!-- MAIN CONTENT WRAPPER -->
    <div class="main-wrapper">
        <!-- TOPBAR ADMIN -->
        <header class="topbar">
            <div class="topbar-left">
                <button type="button" class="sidebar-toggle-btn" id="sidebarToggle" title="Toggle Sidebar">
                    <i class="fa-solid fa-bars"></i>
                </button>
                <div class="topbar-title">
                    <span><?= isset($page_title) ? $page_title : 'Dashboard Admin' ?></span>
                </div>
            </div>

            <div class="topbar-right">
                <div class="user-profile-btn" style="cursor: default;">
                    <div class="user-avatar" style="background: linear-gradient(135deg, #4f46e5, #0ea5e9); color: #ffffff;">
                        <i class="fa-solid fa-user-tie"></i>
                        <span class="status-pulse-dot"></span>
                    </div>
                    <div class="user-info">
                        <span class="user-name"><?= htmlspecialchars($curr_user['nama_lengkap'] ?? 'Admin') ?></span>
                        <span class="user-role-badge">Super Admin</span>
                    </div>
                </div>
            </div>
        </header>

        <!-- PAGE CONTENT -->
        <main class="page-content">
            <?php show_flash(); ?>
