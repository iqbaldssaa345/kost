<?php
/**
 * File Koneksi Database Kost
 * Menggunakan MySQLi untuk lingkungan AppServ / Localhost
 */

$host = "localhost";
$user = "root";
$pass = "12345678";
$db   = "kost";

// Buat koneksi ke server MySQL
$conn = mysqli_connect($host, $user, $pass, $db);

// Cek apakah koneksi berhasil
if (!$conn) {
    // Jika database belum dipilih/ada, coba konek ke server dulu
    $conn_server = @mysqli_connect($host, $user, $pass);
    if ($conn_server) {
        // Coba buat database otomatis jika belum ada
        @mysqli_query($conn_server, "CREATE DATABASE IF NOT EXISTS `$db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        mysqli_close($conn_server);
        
        // Konek kembali dengan database
        $conn = mysqli_connect($host, $user, $pass, $db);
    }
}

if (!$conn) {
    die("<div style='font-family: sans-serif; padding: 20px; background: #fee2e2; color: #991b1b; border-radius: 8px; margin: 20px;'>
        <h3 style='margin-top: 0;'>Koneksi Database Gagal!</h3>
        <p>Error: " . mysqli_connect_error() . "</p>
        <p>Pastikan MySQL di AppServ sudah aktif dengan username <b>$user</b> dan password <b>$pass</b>, serta import file <b>kost.sql</b>.</p>
    </div>");
}

// Start output buffering untuk mencegah headers already sent / blank page
if (!ob_get_level()) {
    ob_start();
}

// Start session jika belum aktif
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
