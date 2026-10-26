<?php
/**
 * Input Tagihan & Pendaftaran Sewa Baru (Petugas)
 */
require_once __DIR__ . '/../koneksi.php';
require_once __DIR__ . '/../config/helper.php';

auth_check(['petugas', 'admin']);

$selected_user_id = isset($_GET['user']) ? (int)$_GET['user'] : 0;
$selected_kamar_id = isset($_GET['kamar']) ? (int)$_GET['kamar'] : 0;

// 1. PROSES INPUT TAGIHAN BIAYA (Harus sebelum header.php)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['btn_simpan_tagihan'])) {
    $id_user = (int)$_POST['id_user'];
    $id_kamar = (int)$_POST['id_kamar'];
    $judul = clean($conn, $_POST['judul_tagihan']);
    $jenis = clean($conn, $_POST['jenis_biaya']);
    $periode = clean($conn, $_POST['periode_bulan']);
    $nominal = (float)$_POST['nominal'];
    $jatuh_tempo = clean($conn, $_POST['tgl_jatuh_tempo']);
    $kode = generate_kode('INV');

    $stmt = mysqli_prepare($conn, "INSERT INTO tbl_tagihan_pembayaran (kode_tagihan, id_user, id_kamar, judul_tagihan, jenis_biaya, periode_bulan, nominal, tgl_jatuh_tempo, status_pembayaran) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'belum_bayar')");
    mysqli_stmt_bind_param($stmt, "siisssds", $kode, $id_user, $id_kamar, $judul, $jenis, $periode, $nominal, $jatuh_tempo);

    if (mysqli_stmt_execute($stmt)) {
        set_flash('success', "Tagihan baru senilai " . format_rupiah($nominal) . " berhasil dibuat.");
        header("Location: tagihan_list.php");
        exit();
    } else {
        set_flash('danger', "Gagal membuat tagihan: " . mysqli_error($conn));
    }
}

// 2. PROSES PENDAFTARAN PENYEWA BARU LANGSUNG (WALK-IN)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['btn_daftar_penyewa_langsung'])) {
    $nama = clean($conn, $_POST['nama_lengkap']);
    $username = clean($conn, $_POST['username']);
    $email = clean($conn, $_POST['email']);
    $no_hp = clean($conn, $_POST['no_hp']);
    $pekerjaan = clean($conn, $_POST['pekerjaan']);
    $alamat = clean($conn, $_POST['alamat_asal']);
    $id_kamar = (int)$_POST['id_kamar'];
    $tgl_mulai = clean($conn, $_POST['tgl_mulai_sewa']);
    $durasi = (int)$_POST['durasi_bulan'];
    $password = '123456'; // Default password untuk penyewa baru dibuatkan petugas

    // Cek username
    $cek = mysqli_query($conn, "SELECT id_user FROM tbl_users WHERE username = '$username'");
    if (mysqli_num_rows($cek) > 0) {
        $username = $username . rand(10, 99);
    }

    $pass_hash = md5($password);
    $stmt_u = mysqli_prepare($conn, "INSERT INTO tbl_users (username, password, nama_lengkap, email, no_hp, role, pekerjaan, alamat_asal, status_aktif) VALUES (?, ?, ?, ?, ?, 'pengunjung', ?, ?, 'aktif')");
    mysqli_stmt_bind_param($stmt_u, "sssssss", $username, $pass_hash, $nama, $email, $no_hp, $pekerjaan, $alamat);

    if (mysqli_stmt_execute($stmt_u)) {
        $id_user_baru = mysqli_insert_id($conn);

        // Ambil harga kamar
        $q_k = mysqli_query($conn, "SELECT harga_per_bulan, nama_kamar, nomor_kamar FROM tbl_kamar WHERE id_kamar = $id_kamar");
        $k_data = mysqli_fetch_assoc($q_k);
        $total_biaya = $k_data['harga_per_bulan'] * $durasi;
        $tgl_selesai = date('Y-m-d', strtotime("+$durasi months", strtotime($tgl_mulai)));
        $kode_booking = generate_kode('BK');
        $id_petugas = (int)$_SESSION['user_id'];
        $now = date('Y-m-d H:i:s');

        // Buat booking langsung aktif
        $stmt_b = mysqli_prepare($conn, "INSERT INTO tbl_booking (kode_booking, id_user, id_kamar, tgl_mulai_sewa, durasi_bulan, tgl_selesai_sewa, total_biaya, status_booking, diverifikasi_oleh, tgl_verifikasi) VALUES (?, ?, ?, ?, ?, ?, ?, 'aktif', ?, ?)");
        mysqli_stmt_bind_param($stmt_b, "siisidsis", $kode_booking, $id_user_baru, $id_kamar, $tgl_mulai, $durasi, $tgl_selesai, $total_biaya, $id_petugas, $now);
        mysqli_stmt_execute($stmt_b);
        $id_b_baru = mysqli_insert_id($conn);

        // Update kamar jadi terisi
        mysqli_query($conn, "UPDATE tbl_kamar SET status = 'terisi' WHERE id_kamar = $id_kamar");

        // Buat tagihan awal
        $kode_inv = generate_kode('INV');
        $judul_inv = "Sewa Kamar " . $k_data['nomor_kamar'] . " ($durasi Bulan)";
        $periode = date('F Y', strtotime($tgl_mulai)) . " - " . date('F Y', strtotime($tgl_selesai));
        $jatuh_tempo = date('Y-m-d', strtotime('+3 days'));

        $stmt_inv = mysqli_prepare($conn, "INSERT INTO tbl_tagihan_pembayaran (kode_tagihan, id_booking, id_user, id_kamar, judul_tagihan, jenis_biaya, periode_bulan, nominal, tgl_jatuh_tempo, status_pembayaran) VALUES (?, ?, ?, ?, ?, 'sewa_kost', ?, ?, ?, 'belum_bayar')");
        mysqli_stmt_bind_param($stmt_inv, "siiisdds", $kode_inv, $id_b_baru, $id_user_baru, $id_kamar, $judul_inv, $periode, $total_biaya, $jatuh_tempo);
        mysqli_stmt_execute($stmt_inv);

        set_flash('success', "Penyewa baru ($nama) berhasil didaftarkan ke Kamar " . $k_data['nomor_kamar'] . ". Password default: 123456.");
        header("Location: penghuni.php");
        exit();
    } else {
        set_flash('danger', "Gagal mendaftarkan penyewa: " . mysqli_error($conn));
    }
}

// Data Kamar Tersedia untuk Form Walk-in
$kamar_tersedia = mysqli_query($conn, "SELECT * FROM tbl_kamar WHERE status = 'tersedia' ORDER BY nomor_kamar ASC");

// Data Penghuni Aktif untuk Form Tagihan
$penghuni_aktif = mysqli_query($conn, "SELECT u.id_user, u.nama_lengkap, k.id_kamar, k.nomor_kamar, k.nama_kamar 
                                      FROM tbl_users u 
                                      JOIN tbl_booking b ON u.id_user = b.id_user 
                                      JOIN tbl_kamar k ON b.id_kamar = k.id_kamar 
                                      WHERE b.status_booking = 'aktif' GROUP BY u.id_user");

$page_title = "Input Tagihan & Pendaftaran Sewa Baru";
require_once __DIR__ . '/header.php';
?>

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px; align-items: start;">
    
    <!-- 1. FORM INPUT TAGIHAN BIAYA -->
    <div class="card">
        <div class="card-header" style="background: #eef2ff;">
            <h3 class="card-title" style="color: #4338ca;"><i class="fa-solid fa-file-invoice-dollar"></i> 1. Input Tagihan / Iuran Penghuni</h3>
        </div>
        <div class="card-body">
            <form action="tagihan_input.php" method="POST">
                <div class="form-group">
                    <label>Pilih Penghuni Aktif *</label>
                    <select name="user_kamar_select" class="form-control" required onchange="setTenantSelection(this)">
                        <option value="">-- Pilih Penghuni --</option>
                        <?php if ($penghuni_aktif && mysqli_num_rows($penghuni_aktif) > 0): ?>
                            <?php while ($p = mysqli_fetch_assoc($penghuni_aktif)): ?>
                                <option value="<?= $p['id_user'] ?>" data-kamar="<?= $p['id_kamar'] ?>" <?= ($p['id_user'] == $selected_user_id) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($p['nama_lengkap']) ?> (Kamar <?= htmlspecialchars($p['nomor_kamar']) ?>)
                                </option>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </select>
                    <input type="hidden" name="id_user" id="in_id_user" value="<?= $selected_user_id ?>">
                    <input type="hidden" name="id_kamar" id="in_id_kamar" value="<?= $selected_kamar_id ?>">
                </div>

                <div class="form-group">
                    <label>Judul / Rincian Tagihan *</label>
                    <input type="text" name="judul_tagihan" class="form-control" required placeholder="Contoh: Iuran Listrik & Air Bulan September">
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Jenis Biaya *</label>
                        <select name="jenis_biaya" class="form-control" required>
                            <option value="sewa_kost">Sewa Kost Bulanan</option>
                            <option value="listrik">Listrik / Tambahan Watt</option>
                            <option value="kebersihan">Kebersihan & Sampah</option>
                            <option value="denda">Denda / Kerusakan</option>
                            <option value="lainnya">Biaya Lainnya</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Periode Tagihan *</label>
                        <input type="text" name="periode_bulan" class="form-control" required value="<?= date('F Y') ?>">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Nominal (Rp) *</label>
                        <input type="number" name="nominal" class="form-control" required min="0" step="5000" placeholder="Contoh: 100000">
                    </div>

                    <div class="form-group">
                        <label>Jatuh Tempo *</label>
                        <input type="date" name="tgl_jatuh_tempo" class="form-control" required value="<?= date('Y-m-d', strtotime('+5 days')) ?>">
                    </div>
                </div>

                <button type="submit" name="btn_simpan_tagihan" class="btn btn-primary btn-block" style="margin-top: 10px;">
                    <i class="fa-solid fa-paper-plane"></i> Terbitkan Tagihan
                </button>
            </form>
        </div>
    </div>

    <!-- 2. FORM PENDAFTARAN PENYEWA BARU LANGSUNG -->
    <div class="card">
        <div class="card-header" style="background: #ecfdf5;">
            <h3 class="card-title" style="color: #065f46;"><i class="fa-solid fa-user-plus"></i> 2. Input Penghuni Baru Langsung (Walk-in)</h3>
        </div>
        <div class="card-body">
            <form action="tagihan_input.php" method="POST">
                <div class="form-row">
                    <div class="form-group">
                        <label>Nama Lengkap Penghuni *</label>
                        <input type="text" name="nama_lengkap" class="form-control" required placeholder="Ahmad Fauzi">
                    </div>
                    <div class="form-group">
                        <label>Username Login *</label>
                        <input type="text" name="username" class="form-control" required placeholder="ahmad12">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>No. WhatsApp Aktif *</label>
                        <input type="tel" name="no_hp" class="form-control" required placeholder="08123456789">
                    </div>
                    <div class="form-group">
                        <label>Email *</label>
                        <input type="email" name="email" class="form-control" required placeholder="ahmad@gmail.com">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Pekerjaan / Status</label>
                        <input type="text" name="pekerjaan" class="form-control" placeholder="Mahasiswa / Karyawan">
                    </div>
                    <div class="form-group">
                        <label>Kota Asal</label>
                        <input type="text" name="alamat_asal" class="form-control" placeholder="Semarang">
                    </div>
                </div>

                <div class="form-group">
                    <label>Pilih Kamar Kosong *</label>
                    <select name="id_kamar" class="form-control" required>
                        <option value="">-- Pilih Kamar Tersedia --</option>
                        <?php if ($kamar_tersedia && mysqli_num_rows($kamar_tersedia) > 0): ?>
                            <?php while ($k = mysqli_fetch_assoc($kamar_tersedia)): ?>
                                <option value="<?= $k['id_kamar'] ?>">
                                    Kamar <?= htmlspecialchars($k['nomor_kamar']) ?> - <?= htmlspecialchars($k['nama_kamar']) ?> (<?= format_rupiah($k['harga_per_bulan']) ?>/bln)
                                </option>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <option value="" disabled>Semua kamar penuh!</option>
                        <?php endif; ?>
                    </select>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Tanggal Mulai Masuk *</label>
                        <input type="date" name="tgl_mulai_sewa" class="form-control" required value="<?= date('Y-m-d') ?>">
                    </div>

                    <div class="form-group">
                        <label>Durasi Sewa *</label>
                        <select name="durasi_bulan" class="form-control" required>
                            <option value="1">1 Bulan</option>
                            <option value="3">3 Bulan</option>
                            <option value="6">6 Bulan</option>
                            <option value="12">12 Bulan (1 Tahun)</option>
                        </select>
                    </div>
                </div>

                <button type="submit" name="btn_daftar_penyewa_langsung" class="btn btn-success btn-block" style="margin-top: 10px;">
                    <i class="fa-solid fa-check-circle"></i> Daftarkan & Masukkan ke Kamar
                </button>
            </form>
        </div>
    </div>

</div>

<script>
function setTenantSelection(selectElem) {
    const selected = selectElem.options[selectElem.selectedIndex];
    document.getElementById('in_id_user').value = selectElem.value;
    document.getElementById('in_id_kamar').value = selected.dataset.kamar || '';
}
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
