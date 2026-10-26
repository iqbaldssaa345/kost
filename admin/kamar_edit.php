<?php
/**
 * Edit Kamar Kost (Admin)
 */
require_once __DIR__ . '/../koneksi.php';
require_once __DIR__ . '/../config/helper.php';

auth_check(['admin']);

$upload_dir = __DIR__ . '/../uploads/kost/';
$id_kamar = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$q_kamar = mysqli_query($conn, "SELECT * FROM tbl_kamar WHERE id_kamar = $id_kamar LIMIT 1");
if (!$q_kamar || mysqli_num_rows($q_kamar) == 0) {
    header("Location: kamar.php");
    exit();
}
$kamar = mysqli_fetch_assoc($q_kamar);

// 1. Hapus satu foto galeri jika ada request
if (isset($_GET['del_foto'])) {
    $id_foto = (int)$_GET['del_foto'];
    $q_f = mysqli_query($conn, "SELECT nama_file FROM tbl_foto_kamar WHERE id_foto = $id_foto AND id_kamar = $id_kamar");
    if ($f_row = mysqli_fetch_assoc($q_f)) {
        if (file_exists($upload_dir . $f_row['nama_file'])) {
            @unlink($upload_dir . $f_row['nama_file']);
        }
        mysqli_query($conn, "DELETE FROM tbl_foto_kamar WHERE id_foto = $id_foto");
        set_flash('success', 'Foto galeri berhasil dihapus.');
    }
    header("Location: kamar_edit.php?id=$id_kamar");
    exit();
}

$error = '';

// 2. PROSES UPDATE DATA KAMAR (Sebelum header.php)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['btn_update_kamar'])) {
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

    // Cek Duplikasi nomor kamar pada kamar lain
    $cek = mysqli_query($conn, "SELECT id_kamar FROM tbl_kamar WHERE nomor_kamar = '$nomor_kamar' AND id_kamar != $id_kamar");
    if (mysqli_num_rows($cek) > 0) {
        $error = "Nomor kamar '$nomor_kamar' sudah digunakan pada kamar lain!";
    } else {
        $foto_utama = $kamar['foto_utama'];

        // Jika upload foto utama baru
        if (isset($_FILES['foto_utama']) && $_FILES['foto_utama']['error'] === UPLOAD_ERR_OK) {
            $ext = pathinfo($_FILES['foto_utama']['name'], PATHINFO_EXTENSION);
            $allowed = ['jpg', 'jpeg', 'png', 'webp'];
            if (in_array(strtolower($ext), ['jpg', 'jpeg', 'png', 'webp'])) {
                $new_name = 'kamar_' . time() . '_' . rand(100, 999) . '.' . $ext;
                if (move_uploaded_file($_FILES['foto_utama']['tmp_name'], $upload_dir . $new_name)) {
                    // Hapus foto lama jika ada
                    if (!empty($kamar['foto_utama']) && file_exists($upload_dir . $kamar['foto_utama'])) {
                        @unlink($upload_dir . $kamar['foto_utama']);
                    }
                    $foto_utama = $new_name;
                }
            }
        }

        // Update Data Kamar
        $stmt = mysqli_prepare($conn, "UPDATE tbl_kamar SET id_kategori=?, nomor_kamar=?, nama_kamar=?, lantai=?, ukuran=?, harga_per_bulan=?, harga_per_tahun=?, harga_per_hari=?, status=?, foto_utama=?, deskripsi=? WHERE id_kamar=?");
        mysqli_stmt_bind_param($stmt, "issisdddsssi", $id_kategori, $nomor_kamar, $nama_kamar, $lantai, $ukuran, $harga_bulan, $harga_tahun, $harga_hari, $status, $foto_utama, $deskripsi, $id_kamar);

        if (mysqli_stmt_execute($stmt)) {
            // Update Fasilitas: Hapus yang lama & masukkan yang baru
            mysqli_query($conn, "DELETE FROM tbl_kamar_fasilitas WHERE id_kamar = $id_kamar");
            if (!empty($fasilitas_dipilih)) {
                $stmt_fas = mysqli_prepare($conn, "INSERT INTO tbl_kamar_fasilitas (id_kamar, id_fasilitas) VALUES (?, ?)");
                foreach ($fasilitas_dipilih as $id_f) {
                    $id_f = (int)$id_f;
                    mysqli_stmt_bind_param($stmt_fas, "ii", $id_kamar, $id_f);
                    mysqli_stmt_execute($stmt_fas);
                }
            }

            // Upload Foto Galeri Baru jika ada
            if (isset($_FILES['foto_galeri'])) {
                $total_files = count($_FILES['foto_galeri']['name']);
                for ($i = 0; $i < $total_files; $i++) {
                    if ($_FILES['foto_galeri']['error'][$i] === UPLOAD_ERR_OK) {
                        $ext = pathinfo($_FILES['foto_galeri']['name'][$i], PATHINFO_EXTENSION);
                        if (in_array(strtolower($ext), ['jpg', 'jpeg', 'png', 'webp'])) {
                            $gal_name = 'gal_' . $id_kamar . '_' . time() . '_' . $i . '.' . $ext;
                            if (move_uploaded_file($_FILES['foto_galeri']['tmp_name'][$i], $upload_dir . $gal_name)) {
                                mysqli_query($conn, "INSERT INTO tbl_foto_kamar (id_kamar, nama_file, keterangan) VALUES ($id_kamar, '$gal_name', 'Galeri Kamar')");
                            }
                        }
                    }
                }
            }

            set_flash('success', "Data kamar ($nama_kamar) berhasil diperbarui.");
            header("Location: kamar.php");
            exit();
        } else {
            $error = "Gagal memperbarui kamar: " . mysqli_error($conn);
        }
    }
}

// Ambil Kategori & Fasilitas
$kategori_list = mysqli_query($conn, "SELECT * FROM tbl_kategori_kamar ORDER BY id_kategori ASC");
$fasilitas_list = mysqli_query($conn, "SELECT * FROM tbl_fasilitas ORDER BY tipe DESC, id_fasilitas ASC");

// Ambil Fasilitas yang sudah terpilih
$q_selected_fas = mysqli_query($conn, "SELECT id_fasilitas FROM tbl_kamar_fasilitas WHERE id_kamar = $id_kamar");
$selected_fas_ids = [];
while ($sf = mysqli_fetch_assoc($q_selected_fas)) {
    $selected_fas_ids[] = $sf['id_fasilitas'];
}

// Ambil Galeri Foto Kamar
$q_galeri = mysqli_query($conn, "SELECT * FROM tbl_foto_kamar WHERE id_kamar = $id_kamar");

$page_title = "Edit Kamar Kost";
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
        <h3 class="card-title"><i class="fa-solid fa-pen-to-square"></i> Form Edit Kamar Kost (<?= htmlspecialchars($kamar['nomor_kamar']) ?>)</h3>
    </div>
    <div class="card-body">
        <form action="kamar_edit.php?id=<?= $id_kamar ?>" method="POST" enctype="multipart/form-data">
            
            <div class="form-row">
                <div class="form-group">
                    <label>Nomor Kamar * (Unik)</label>
                    <input type="text" name="nomor_kamar" class="form-control" required value="<?= htmlspecialchars($kamar['nomor_kamar']) ?>">
                </div>

                <div class="form-group">
                    <label>Nama / Label Kamar *</label>
                    <input type="text" name="nama_kamar" class="form-control" required value="<?= htmlspecialchars($kamar['nama_kamar']) ?>">
                </div>

                <div class="form-group">
                    <label>Kategori & Tipe Penghuni *</label>
                    <select name="id_kategori" class="form-control" required>
                        <?php while ($kat = mysqli_fetch_assoc($kategori_list)): ?>
                            <option value="<?= $kat['id_kategori'] ?>" <?= $kamar['id_kategori'] == $kat['id_kategori'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($kat['nama_kategori']) ?> (<?= htmlspecialchars($kat['jenis_penghuni']) ?>)
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Ukuran Kamar</label>
                    <input type="text" name="ukuran" class="form-control" value="<?= htmlspecialchars($kamar['ukuran']) ?>">
                </div>

                <div class="form-group">
                    <label>Posisi Lantai</label>
                    <input type="number" name="lantai" class="form-control" min="1" value="<?= $kamar['lantai'] ?>">
                </div>

                <div class="form-group">
                    <label>Status Ketersediaan *</label>
                    <select name="status" class="form-control" required>
                        <option value="tersedia" <?= $kamar['status'] == 'tersedia' ? 'selected' : '' ?>>Tersedia (Kosong)</option>
                        <option value="terisi" <?= $kamar['status'] == 'terisi' ? 'selected' : '' ?>>Terisi (Ada Penghuni)</option>
                        <option value="renovasi" <?= $kamar['status'] == 'renovasi' ? 'selected' : '' ?>>Renovasi / Pemeliharaan</option>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Biaya Sewa per Bulan (Rp) *</label>
                    <input type="number" name="harga_per_bulan" class="form-control" required min="0" step="10000" value="<?= $kamar['harga_per_bulan'] ?>">
                </div>

                <div class="form-group">
                    <label>Biaya Sewa per Tahun (Rp)</label>
                    <input type="number" name="harga_per_tahun" class="form-control" min="0" step="50000" value="<?= $kamar['harga_per_tahun'] ?? ($kamar['harga_per_bulan'] * 11) ?>">
                </div>

                <div class="form-group">
                    <label>Biaya Sewa per Hari (Rp)</label>
                    <input type="number" name="harga_per_hari" class="form-control" min="0" step="5000" value="<?= $kamar['harga_per_hari'] ?>">
                </div>
            </div>

            <div class="form-group">
                <label>Deskripsi Kamar</label>
                <textarea name="deskripsi" class="form-control" rows="3"><?= htmlspecialchars($kamar['deskripsi'] ?? '') ?></textarea>
            </div>

            <!-- FASILITAS CHECKBOXES -->
            <div class="form-group" style="margin-top: 10px;">
                <label style="font-size: 1rem; font-weight: 700; margin-bottom: 8px;">Fasilitas Kamar Terpilih</label>
                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 10px; background: var(--adm-bg); padding: 16px; border-radius: var(--radius-sm);">
                    <?php if ($fasilitas_list): ?>
                        <?php while ($f = mysqli_fetch_assoc($fasilitas_list)): ?>
                            <label style="display: flex; align-items: center; gap: 8px; font-weight: 500; font-size: 0.88rem; cursor: pointer;">
                                <input type="checkbox" name="fasilitas[]" value="<?= $f['id_fasilitas'] ?>" <?= in_array($f['id_fasilitas'], $selected_fas_ids) ? 'checked' : '' ?>>
                                <i class="fa-solid <?= htmlspecialchars($f['ikon'] ?? 'fa-check') ?>" style="color: var(--adm-primary);"></i>
                                <?= htmlspecialchars($f['nama_fasilitas']) ?>
                            </label>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- FOTO UTAMA & PREVIEW -->
            <div class="form-row" style="margin-top: 16px;">
                <div class="form-group">
                    <label>Ganti Foto Utama Kamar</label>
                    <input type="file" name="foto_utama" class="form-control admin-img-input" data-preview-target="previewCover" accept="image/*">
                    
                    <div style="margin-top: 10px;">
                        <span style="font-size: 0.8rem; color: var(--adm-muted);">Foto Saat Ini:</span><br>
                        <?php 
                        $foto_path = '../uploads/kost/' . $kamar['foto_utama'];
                        $img_src = (file_exists($foto_path) && !empty($kamar['foto_utama'])) ? $foto_path : 'https://images.unsplash.com/photo-1598928506311-c55ded91a20c?auto=format&fit=crop&w=300&q=80';
                        ?>
                        <img id="previewCover" src="<?= $img_src ?>" alt="Cover" style="max-height: 120px; border-radius: 6px; border: 1px solid var(--adm-border);">
                    </div>
                </div>

                <div class="form-group">
                    <label>Tambah Foto Galeri Baru</label>
                    <input type="file" name="foto_galeri[]" class="form-control" multiple accept="image/*">
                    <small class="form-text">Pilih satu atau beberapa foto untuk ditambahkan ke galeri kamar.</small>
                    
                    <!-- Galeri Terpasang -->
                    <div style="margin-top: 10px;">
                        <span style="font-size: 0.8rem; color: var(--adm-muted);">Foto Galeri Terpasang:</span>
                        <div style="display: flex; gap: 8px; flex-wrap: wrap; margin-top: 6px;">
                            <?php if ($q_galeri && mysqli_num_rows($q_galeri) > 0): ?>
                                <?php while ($g = mysqli_fetch_assoc($q_galeri)): 
                                    $g_path = '../uploads/kost/' . $g['nama_file'];
                                    $g_src = file_exists($g_path) ? $g_path : 'https://images.unsplash.com/photo-1598928506311-c55ded91a20c?auto=format&fit=crop&w=150&q=80';
                                ?>
                                    <div style="position: relative; width: 75px; height: 60px; border-radius: 6px; overflow: hidden; border: 1px solid var(--adm-border);">
                                        <img src="<?= $g_src ?>" alt="Galeri" style="width: 100%; height: 100%; object-fit: cover;">
                                        <a href="kamar_edit.php?id=<?= $id_kamar ?>&del_foto=<?= $g['id_foto'] ?>" onclick="return confirm('Hapus foto galeri ini?')" style="position: absolute; top: 2px; right: 2px; background: rgba(239,68,68,0.9); color: #fff; width: 20px; height: 20px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.7rem; text-decoration: none;">
                                            &times;
                                        </a>
                                    </div>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <small style="color: var(--adm-muted);">Belum ada galeri tambahan.</small>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <div style="margin-top: 24px; display: flex; justify-content: flex-end; gap: 12px;">
                <a href="kamar.php" class="btn btn-outline">Batal</a>
                <button type="submit" name="btn_update_kamar" class="btn btn-primary" style="padding: 11px 24px;">
                    <i class="fa-solid fa-floppy-disk"></i> Simpan Perubahan Kamar
                </button>
            </div>

        </form>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
