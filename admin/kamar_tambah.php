<?php
/**
 * Tambah Kamar Baru (Admin)
 */
require_once __DIR__ . '/../koneksi.php';
require_once __DIR__ . '/../config/helper.php';

auth_check(['admin']);

// Pastikan direktori upload tersedia
$upload_dir = __DIR__ . '/../uploads/kost/';
if (!file_exists($upload_dir)) {
    @mkdir($upload_dir, 0777, true);
}

// Ambil Kategori & Fasilitas
$kategori_list = mysqli_query($conn, "SELECT * FROM tbl_kategori_kamar ORDER BY id_kategori ASC");
$fasilitas_list = mysqli_query($conn, "SELECT * FROM tbl_fasilitas ORDER BY tipe DESC, id_fasilitas ASC");

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['btn_simpan_kamar'])) {
    $nomor_kamar = clean($conn, $_POST['nomor_kamar']);
    $nama_kamar = clean($conn, $_POST['nama_kamar']);
    $id_kategori = (int)$_POST['id_kategori'];
    $lantai = (int)$_POST['lantai'];
    $ukuran = clean($conn, $_POST['ukuran']);
    $harga_bulan = (float)$_POST['harga_per_bulan'];
    $harga_tahun = !empty($_POST['harga_per_tahun']) ? (float)$_POST['harga_per_tahun'] : ($harga_bulan * 11);
    $harga_hari = !empty($_POST['harga_per_hari']) ? (float)$_POST['harga_per_hari'] : null;
    $status = clean($conn, $_POST['status']);
    $deskripsi = clean($conn, $_POST['deskripsi']);
    $fasilitas_dipilih = isset($_POST['fasilitas']) ? (array)$_POST['fasilitas'] : [];

    // Cek Duplikasi Nomor Kamar
    $cek = mysqli_query($conn, "SELECT id_kamar FROM tbl_kamar WHERE nomor_kamar = '$nomor_kamar'");
    if (mysqli_num_rows($cek) > 0) {
        $error = "Nomor kamar '$nomor_kamar' sudah digunakan! Silakan gunakan nomor kamar lain.";
    } else {
        // Upload Foto Utama
        $foto_utama = 'kamar_default.jpg';
        if (isset($_FILES['foto_utama']) && $_FILES['foto_utama']['error'] === UPLOAD_ERR_OK) {
            $ext = pathinfo($_FILES['foto_utama']['name'], PATHINFO_EXTENSION);
            $allowed = ['jpg', 'jpeg', 'png', 'webp'];
            if (in_array(strtolower($ext), $allowed)) {
                $new_name = 'kamar_' . time() . '_' . rand(100, 999) . '.' . $ext;
                if (move_uploaded_file($_FILES['foto_utama']['tmp_name'], $upload_dir . $new_name)) {
                    $foto_utama = $new_name;
                }
            }
        }

        // Insert Kamar
        $stmt = mysqli_prepare($conn, "INSERT INTO tbl_kamar (id_kategori, nomor_kamar, nama_kamar, lantai, ukuran, harga_per_bulan, harga_per_tahun, harga_per_hari, status, foto_utama, deskripsi) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "issisdddsss", $id_kategori, $nomor_kamar, $nama_kamar, $lantai, $ukuran, $harga_bulan, $harga_tahun, $harga_hari, $status, $foto_utama, $deskripsi);

        if (mysqli_stmt_execute($stmt)) {
            $id_kamar_baru = mysqli_insert_id($conn);

            // Simpan Fasilitas Terpilih
            if (!empty($fasilitas_dipilih)) {
                $stmt_fas = mysqli_prepare($conn, "INSERT INTO tbl_kamar_fasilitas (id_kamar, id_fasilitas) VALUES (?, ?)");
                foreach ($fasilitas_dipilih as $id_f) {
                    $id_f = (int)$id_f;
                    mysqli_stmt_bind_param($stmt_fas, "ii", $id_kamar_baru, $id_f);
                    mysqli_stmt_execute($stmt_fas);
                }
            }

            // Upload Foto Galeri Tambahan (Multiple)
            if (isset($_FILES['foto_galeri'])) {
                $total_files = count($_FILES['foto_galeri']['name']);
                for ($i = 0; $i < $total_files; $i++) {
                    if ($_FILES['foto_galeri']['error'][$i] === UPLOAD_ERR_OK) {
                        $ext = pathinfo($_FILES['foto_galeri']['name'][$i], PATHINFO_EXTENSION);
                        if (in_array(strtolower($ext), ['jpg', 'jpeg', 'png', 'webp'])) {
                            $gal_name = 'gal_' . $id_kamar_baru . '_' . time() . '_' . $i . '.' . $ext;
                            if (move_uploaded_file($_FILES['foto_galeri']['tmp_name'][$i], $upload_dir . $gal_name)) {
                                mysqli_query($conn, "INSERT INTO tbl_foto_kamar (id_kamar, nama_file, keterangan) VALUES ($id_kamar_baru, '$gal_name', 'Galeri Kamar')");
                            }
                        }
                    }
                }
            }

            set_flash('success', "Kamar baru ($nama_kamar) berhasil ditambahkan.");
            header("Location: kamar.php");
            exit();
        } else {
            $error = "Gagal menyimpan kamar: " . mysqli_error($conn);
        }
    }
}

$page_title = "Tambah Kamar Baru";
require_once __DIR__ . '/header.php';
?>

<div style="margin-bottom: 18px;">
    <a href="kamar.php" class="btn btn-outline btn-sm"><i class="fa-solid fa-arrow-left"></i> Kembali ke Daftar Kamar</a>
</div>

<?php if (!empty($error)): ?>
    <div class="alert-box danger"><i class="fa-solid fa-circle-exclamation"></i> <?= $error ?></div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fa-solid fa-plus-circle"></i> Form Tambah Kamar Kost</h3>
    </div>
    <div class="card-body">
        <form action="kamar_tambah.php" method="POST" enctype="multipart/form-data">
            
            <div class="form-row">
                <div class="form-group">
                    <label>Nomor Kamar * (Unik)</label>
                    <input type="text" name="nomor_kamar" class="form-control" required placeholder="Contoh: A-03, 101, VIP-02" value="<?= isset($_POST['nomor_kamar']) ? htmlspecialchars($_POST['nomor_kamar']) : '' ?>">
                </div>

                <div class="form-group">
                    <label>Nama / Label Kamar *</label>
                    <input type="text" name="nama_kamar" class="form-control" required placeholder="Contoh: Kamar Deluxe AC 03" value="<?= isset($_POST['nama_kamar']) ? htmlspecialchars($_POST['nama_kamar']) : '' ?>">
                </div>

                <div class="form-group">
                    <label>Kategori & Tipe Penghuni *</label>
                    <select name="id_kategori" class="form-control" required>
                        <?php while ($kat = mysqli_fetch_assoc($kategori_list)): ?>
                            <option value="<?= $kat['id_kategori'] ?>">
                                <?= htmlspecialchars($kat['nama_kategori']) ?> (<?= htmlspecialchars($kat['jenis_penghuni']) ?>)
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Ukuran Kamar</label>
                    <input type="text" name="ukuran" class="form-control" placeholder="Contoh: 3x4 meter" value="3x4 m">
                </div>

                <div class="form-group">
                    <label>Posisi Lantai</label>
                    <input type="number" name="lantai" class="form-control" min="1" value="1">
                </div>

                <div class="form-group">
                    <label>Status Ketersediaan *</label>
                    <select name="status" class="form-control" required>
                        <option value="tersedia">Tersedia (Kosong)</option>
                        <option value="terisi">Terisi (Ada Penghuni)</option>
                        <option value="renovasi">Renovasi / Pemeliharaan</option>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Biaya Sewa per Bulan (Rp) *</label>
                    <input type="number" name="harga_per_bulan" class="form-control" required placeholder="Contoh: 1200000" min="0" step="10000">
                </div>

                <div class="form-group">
                    <label>Biaya Sewa per Tahun (Rp)</label>
                    <input type="number" name="harga_per_tahun" class="form-control" placeholder="Contoh: 13200000" min="0" step="50000">
                </div>

                <div class="form-group">
                    <label>Biaya Sewa per Hari (Rp)</label>
                    <input type="number" name="harga_per_hari" class="form-control" placeholder="Contoh: 100000" min="0" step="5000">
                </div>
            </div>

            <div class="form-group">
                <label>Deskripsi Lengkap Kamar</label>
                <textarea name="deskripsi" class="form-control" rows="3" placeholder="Jelaskan spesifikasi kamar, kelebihan, posisi jendela, sirkulasi udara, dll."></textarea>
            </div>

            <!-- FASILITAS CHECKBOXES -->
            <div class="form-group" style="margin-top: 10px;">
                <label style="font-size: 1rem; font-weight: 700; margin-bottom: 8px;">Pilih Fasilitas Kamar</label>
                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 10px; background: var(--adm-bg); padding: 16px; border-radius: var(--radius-sm);">
                    <?php if ($fasilitas_list): ?>
                        <?php while ($f = mysqli_fetch_assoc($fasilitas_list)): ?>
                            <label style="display: flex; align-items: center; gap: 8px; font-weight: 500; font-size: 0.88rem; cursor: pointer;">
                                <input type="checkbox" name="fasilitas[]" value="<?= $f['id_fasilitas'] ?>">
                                <i class="fa-solid <?= htmlspecialchars($f['ikon'] ?? 'fa-check') ?>" style="color: var(--adm-primary);"></i>
                                <?= htmlspecialchars($f['nama_fasilitas']) ?>
                            </label>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- UPLOAD FOTO UTAMA & GALERI -->
            <div class="form-row" style="margin-top: 16px;">
                <div class="form-group">
                    <label>Foto Utama Kamar (Cover)</label>
                    <input type="file" name="foto_utama" class="form-control admin-img-input" data-preview-target="previewCover" accept="image/*">
                    <img id="previewCover" src="#" alt="Preview" style="display: none; max-height: 140px; margin-top: 10px; border-radius: 6px; border: 1px solid var(--adm-border);">
                </div>

                <div class="form-group">
                    <label>Foto Galeri Tambahan (Bisa Pilih Banyak Foto)</label>
                    <input type="file" name="foto_galeri[]" class="form-control" multiple accept="image/*">
                    <small class="form-text">Pilih foto interior, kamar mandi, sudut meja kerja, dll.</small>
                </div>
            </div>

            <div style="margin-top: 24px; display: flex; justify-content: flex-end; gap: 12px;">
                <a href="kamar.php" class="btn btn-outline">Batal</a>
                <button type="submit" name="btn_simpan_kamar" class="btn btn-primary" style="padding: 11px 24px;">
                    <i class="fa-solid fa-cloud-arrow-up"></i> Simpan Data Kamar
                </button>
            </div>

        </form>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
