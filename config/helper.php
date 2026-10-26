<?php
/**
 * Helper Functions Sistem Kost
 */

// Pastikan koneksi disertakan jika belum
if (!isset($conn)) {
    require_once __DIR__ . '/../koneksi.php';
}

// Cek dan instalasi otomatis tabel jika belum ada
function auto_install_database($conn) {
    $check = mysqli_query($conn, "SHOW TABLES LIKE 'tbl_pengaturan'");
    if ($check && mysqli_num_rows($check) == 0) {
        $sql_file = __DIR__ . '/../kost.sql';
        if (file_exists($sql_file)) {
            $queries = file_get_contents($sql_file);
            // Eksekusi multi query
            if (mysqli_multi_query($conn, $queries)) {
                do {
                    // Flush multi_query buffer
                    if ($result = mysqli_store_result($conn)) {
                        mysqli_free_result($result);
                    }
                } while (mysqli_more_results($conn) && mysqli_next_result($conn));
            }
        }
    }

    // Pastikan tabel tbl_password_resets selalu ada
    $check_reset = mysqli_query($conn, "SHOW TABLES LIKE 'tbl_password_resets'");
    if ($check_reset && mysqli_num_rows($check_reset) == 0) {
        mysqli_query($conn, "CREATE TABLE IF NOT EXISTS `tbl_password_resets` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `email` varchar(100) NOT NULL,
          `token` varchar(100) NOT NULL,
          `otp` varchar(10) DEFAULT NULL,
          `expires_at` datetime NOT NULL,
          `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
          PRIMARY KEY (`id`),
          INDEX (`token`),
          INDEX (`email`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
    }
}
auto_install_database($conn);

/**
 * Format Angka ke Rupiah
 */
function format_rupiah($angka) {
    return 'Rp ' . number_format((float)$angka, 0, ',', '.');
}

/**
 * Format Tanggal Indonesia
 */
function tgl_indo($tanggal) {
    if (!$tanggal || $tanggal == '0000-00-00' || $tanggal == '0000-00-00 00:00:00') {
        return '-';
    }
    $bulan = [
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];
    $split = explode('-', date('Y-m-d', strtotime($tanggal)));
    return $split[2] . ' ' . $bulan[(int)$split[1]] . ' ' . $split[0];
}

/**
 * Format Tanggal & Jam
 */
function tgl_jam_indo($datetime) {
    if (!$datetime) return '-';
    return tgl_indo($datetime) . ' ' . date('H:i', strtotime($datetime)) . ' WIB';
}

/**
 * Ambil Profil & Pengaturan Kost
 */
function get_profil_kost($conn) {
    $q = mysqli_query($conn, "SELECT * FROM tbl_pengaturan WHERE id = 1 LIMIT 1");
    if ($q && mysqli_num_rows($q) > 0) {
        return mysqli_fetch_assoc($q);
    }
    return [
        'nama_kost' => 'Griya Kost Harmoni',
        'slogan' => 'Hunian Nyaman & Strategis',
        'logo' => 'logo_default.png',
        'banner' => 'banner_default.jpg',
        'deskripsi' => 'Kost eksklusif fasilitas lengkap',
        'alamat' => 'Jl. Flamboyan No. 45',
        'kota' => 'Jakarta Selatan',
        'no_telepon' => '021-88997766',
        'no_wa' => '081234567890',
        'email' => 'kontak@griyakost.com',
        'nama_bank' => 'Bank BCA',
        'no_rekening' => '8830129841',
        'atas_nama' => 'Griya Kost Harmoni',
        'qris_image' => null
    ];
}

/**
 * Cek Autentikasi Pengguna berdasarkan Role
 */
function auth_check($allowed_roles = []) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
        header("Location: ../login.php?msg=silakan_login");
        exit();
    }
    if (!empty($allowed_roles) && !in_array($_SESSION['role'], (array)$allowed_roles)) {
        header("Location: ../login.php?msg=akses_ditolak");
        exit();
    }
}

/**
 * Dapatkan Info User Saat Ini
 */
function get_current_user_data($conn) {
    if (!isset($_SESSION['user_id'])) return null;
    $id = (int)$_SESSION['user_id'];
    $q = mysqli_query($conn, "SELECT * FROM tbl_users WHERE id_user = $id LIMIT 1");
    return ($q && mysqli_num_rows($q) > 0) ? mysqli_fetch_assoc($q) : null;
}

/**
 * Generate Kode Unik Otomatis
 */
function generate_kode($prefix = 'BK') {
    return $prefix . '-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -4));
}

/**
 * Flash Message Handler
 */
function set_flash($type, $message) {
    $_SESSION['flash_type'] = $type; // success, danger, warning, info
    $_SESSION['flash_msg'] = $message;
}

function show_flash() {
    if (isset($_SESSION['flash_msg'])) {
        $type = $_SESSION['flash_type'] ?? 'info';
        $msg = $_SESSION['flash_msg'];
        unset($_SESSION['flash_msg'], $_SESSION['flash_type']);
        
        $meta = [
            'success' => [
                'icon' => 'fa-solid fa-circle-check',
                'title' => 'Berhasil!',
                'bg' => 'linear-gradient(135deg, #ffffff 0%, #f0fdf4 100%)',
                'border' => '#86efac',
                'text' => '#14532d',
                'icon_bg' => '#dcfce7',
                'icon_color' => '#16a34a',
                'progress_color' => '#22c55e'
            ],
            'danger' => [
                'icon' => 'fa-solid fa-circle-xmark',
                'title' => 'Peringatan / Gagal!',
                'bg' => 'linear-gradient(135deg, #ffffff 0%, #fff1f2 100%)',
                'border' => '#fca5a5',
                'text' => '#7f1d1d',
                'icon_bg' => '#fee2e2',
                'icon_color' => '#dc2626',
                'progress_color' => '#ef4444'
            ],
            'warning' => [
                'icon' => 'fa-solid fa-triangle-exclamation',
                'title' => 'Pemberitahuan!',
                'bg' => 'linear-gradient(135deg, #ffffff 0%, #fffbeb 100%)',
                'border' => '#fde68a',
                'text' => '#78350f',
                'icon_bg' => '#fef3c7',
                'icon_color' => '#d97706',
                'progress_color' => '#f59e0b'
            ],
            'info' => [
                'icon' => 'fa-solid fa-circle-info',
                'title' => 'Informasi Sistem',
                'bg' => 'linear-gradient(135deg, #ffffff 0%, #f0f9ff 100%)',
                'border' => '#7dd3fc',
                'text' => '#0c4a6e',
                'icon_bg' => '#e0f2fe',
                'icon_color' => '#0284c7',
                'progress_color' => '#0ea5e9'
            ]
        ];
        
        $c = $meta[$type] ?? $meta['info'];
        $toast_id = 'toast_' . rand(100, 999);
        
        echo "<div class='modern-toast-card' id='{$toast_id}' style='background: {$c['bg']}; border: 1.5px solid {$c['border']}; color: {$c['text']}; padding: 16px 20px; border-radius: 14px; margin-bottom: 24px; display: flex; align-items: center; justify-content: space-between; gap: 16px; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.08), 0 4px 6px -2px rgba(0,0,0,0.04); position: relative; overflow: hidden; animation: toastSlideDown 0.4s cubic-bezier(0.16, 1, 0.3, 1);'>
            
            <div style='display: flex; align-items: center; gap: 14px;'>
                <div style='width: 42px; height: 42px; border-radius: 10px; background: {$c['icon_bg']}; display: flex; align-items: center; justify-content: center; flex-shrink: 0; box-shadow: 0 2px 5px rgba(0,0,0,0.05);'>
                    <i class='{$c['icon']}' style='font-size: 1.35rem; color: {$c['icon_color']};'></i>
                </div>
                <div>
                    <div style='font-size: 0.95rem; font-weight: 800; color: {$c['text']}; margin-bottom: 2px; display: flex; align-items: center; gap: 6px;'>
                        {$c['title']}
                    </div>
                    <div style='font-size: 0.88rem; color: #334155; line-height: 1.45;'>
                        {$msg}
                    </div>
                </div>
            </div>

            <button type='button' onclick=\"dismissToast('{$toast_id}')\" style='background: rgba(0,0,0,0.04); border: none; width: 28px; height: 28px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.1rem; color: #64748b; cursor: pointer; transition: all 0.2s;' title='Tutup'>&times;</button>
            
            <!-- Progress countdown bar -->
            <div id='{$toast_id}_progress' style='position: absolute; bottom: 0; left: 0; height: 3.5px; background: {$c['progress_color']}; width: 100%; transition: width 4.5s linear;'></div>
        </div>

        <style>
        @keyframes toastSlideDown {
            from { opacity: 0; transform: translateY(-16px) scale(0.98); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }
        @keyframes toastFadeOut {
            from { opacity: 1; transform: translateY(0) scale(1); max-height: 120px; margin-bottom: 24px; }
            to { opacity: 0; transform: translateY(-10px) scale(0.95); max-height: 0; margin-bottom: 0; padding-top: 0; padding-bottom: 0; }
        }
        .toast-closing {
            animation: toastFadeOut 0.35s forwards ease-in-out !important;
        }
        </style>

        <script>
        (function() {
            const toastEl = document.getElementById('{$toast_id}');
            const progEl = document.getElementById('{$toast_id}_progress');
            if (toastEl) {
                // Jalankan countdown bar
                setTimeout(function() {
                    if (progEl) progEl.style.width = '0%';
                }, 50);

                // Auto dismiss setelah 4.5 detik
                setTimeout(function() {
                    dismissToast('{$toast_id}');
                }, 4500);
            }
        })();

        function dismissToast(id) {
            const el = document.getElementById(id);
            if (el && !el.classList.contains('toast-closing')) {
                el.classList.add('toast-closing');
                setTimeout(function() {
                    if (el.parentNode) el.parentNode.removeChild(el);
                }, 350);
            }
        }
        </script>";
    }
}

/**
 * Bersihkan input XSS & Karakter Berbahaya
 */
function clean($conn, $data) {
    return mysqli_real_escape_string($conn, trim(htmlspecialchars($data, ENT_QUOTES, 'UTF-8')));
}
?>
