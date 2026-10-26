<?php
/**
 * Fasilitas Kost (Admin)
 */
require_once __DIR__ . '/../koneksi.php';
require_once __DIR__ . '/../config/helper.php';

auth_check(['admin']);

// 1. TAMBAH FASILITAS (Sebelum header.php)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['btn_tambah_fasilitas'])) {
    $nama = clean($conn, $_POST['nama_fasilitas']);
    $ikon = clean($conn, $_POST['ikon']);
    $tipe = clean($conn, $_POST['tipe']);
    $keterangan = clean($conn, $_POST['keterangan']);

    $stmt = mysqli_prepare($conn, "INSERT INTO tbl_fasilitas (nama_fasilitas, ikon, tipe, keterangan) VALUES (?, ?, ?, ?)");
    mysqli_stmt_bind_param($stmt, "ssss", $nama, $ikon, $tipe, $keterangan);
    if (mysqli_stmt_execute($stmt)) {
        set_flash('success', "Fasilitas '$nama' berhasil ditambahkan.");
    } else {
        set_flash('danger', "Gagal menambah fasilitas: " . mysqli_error($conn));
    }
    header("Location: fasilitas.php");
    exit();
}

// 2. EDIT FASILITAS
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['btn_edit_fasilitas'])) {
    $id = (int)$_POST['id_fasilitas'];
    $nama = clean($conn, $_POST['nama_fasilitas']);
    $ikon = clean($conn, $_POST['ikon']);
    $tipe = clean($conn, $_POST['tipe']);
    $keterangan = clean($conn, $_POST['keterangan']);

    $stmt = mysqli_prepare($conn, "UPDATE tbl_fasilitas SET nama_fasilitas=?, ikon=?, tipe=?, keterangan=? WHERE id_fasilitas=?");
    mysqli_stmt_bind_param($stmt, "ssssi", $nama, $ikon, $tipe, $keterangan, $id);
    if (mysqli_stmt_execute($stmt)) {
        set_flash('success', "Fasilitas berhasil diperbarui.");
    } else {
        set_flash('danger', "Gagal update fasilitas: " . mysqli_error($conn));
    }
    header("Location: fasilitas.php");
    exit();
}

// 3. HAPUS FASILITAS
if (isset($_GET['hapus'])) {
    $id_hapus = (int)$_GET['hapus'];
    mysqli_query($conn, "DELETE FROM tbl_fasilitas WHERE id_fasilitas = $id_hapus");
    set_flash('success', 'Fasilitas berhasil dihapus.');
    header("Location: fasilitas.php");
    exit();
}

$fasilitas_list = mysqli_query($conn, "SELECT * FROM tbl_fasilitas ORDER BY tipe ASC, id_fasilitas ASC");

$page_title = "Fasilitas Kost";
require_once __DIR__ . '/header.php';
?>

<div class="card">
    <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
        <h3 class="card-title"><i class="fa-solid fa-list-check"></i> Master Data Fasilitas</h3>
        <button type="button" class="btn btn-primary btn-sm" onclick="openModal('modalTambahFasilitas')">
            <i class="fa-solid fa-plus"></i> Tambah Fasilitas Baru
        </button>
    </div>

    <div class="card-body" style="padding: 0;">
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Ikon</th>
                        <th>Nama Fasilitas</th>
                        <th>Tipe Fasilitas</th>
                        <th>Keterangan</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($fasilitas_list && mysqli_num_rows($fasilitas_list) > 0): ?>
                        <?php while ($f = mysqli_fetch_assoc($fasilitas_list)): ?>
                            <tr>
                                <td>
                                    <div style="width: 38px; height: 38px; background: var(--adm-bg); border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; color: var(--adm-primary);">
                                        <i class="fa-solid <?= htmlspecialchars($f['ikon'] ?? 'fa-check') ?>"></i>
                                    </div>
                                </td>
                                <td><b><?= htmlspecialchars($f['nama_fasilitas']) ?></b></td>
                                <td>
                                    <span class="badge <?= $f['tipe'] == 'kamar' ? 'badge-info' : 'badge-warning' ?>">
                                        <?= $f['tipe'] == 'kamar' ? 'Fasilitas Kamar' : 'Fasilitas Bersama' ?>
                                    </span>
                                </td>
                                <td><?= htmlspecialchars($f['keterangan'] ?? '-') ?></td>
                                <td>
                                    <div style="display: flex; gap: 6px;">
                                        <button type="button" class="btn btn-outline btn-sm" onclick='editFasilitas(<?= json_encode($f) ?>)'>
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </button>
                                        <button type="button" class="btn btn-danger btn-sm" onclick="confirmAction('Hapus fasilitas <?= addslashes($f['nama_fasilitas']) ?>?', 'fasilitas.php?hapus=<?= $f['id_fasilitas'] ?>')">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="5" style="text-align: center; color: var(--adm-muted);">Belum ada fasilitas terdaftar.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- MODAL TAMBAH FASILITAS -->
<div class="modal-overlay" id="modalTambahFasilitas">
    <div class="modal-box">
        <form action="fasilitas.php" method="POST">
            <div class="modal-header">
                <h3 style="font-size: 1.1rem; font-weight: 700;">Tambah Fasilitas Baru</h3>
                <button type="button" class="btn btn-outline btn-sm" onclick="closeModal('modalTambahFasilitas')">&times;</button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label>Nama Fasilitas *</label>
                    <input type="text" name="nama_fasilitas" class="form-control" required placeholder="Contoh: Smart TV 32 Inch">
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Kelas Ikon FontAwesome (fa-*)</label>
                        <input type="text" name="ikon" class="form-control" placeholder="fa-tv" value="fa-check">
                        <small class="form-text">Contoh: fa-wifi, fa-snowflake, fa-bath, fa-bed, fa-tv</small>
                    </div>

                    <div class="form-group">
                        <label>Tipe Fasilitas *</label>
                        <select name="tipe" class="form-control" required>
                            <option value="kamar">Fasilitas Dalam Kamar</option>
                            <option value="bersama">Fasilitas Umum / Bersama</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label>Keterangan Tambahan</label>
                    <input type="text" name="keterangan" class="form-control" placeholder="Deskripsi singkat fasilitas">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal('modalTambahFasilitas')">Batal</button>
                <button type="submit" name="btn_tambah_fasilitas" class="btn btn-primary">Simpan Fasilitas</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL EDIT FASILITAS -->
<div class="modal-overlay" id="modalEditFasilitas">
    <div class="modal-box">
        <form action="fasilitas.php" method="POST">
            <input type="hidden" name="id_fasilitas" id="edit_id_fasilitas">
            <div class="modal-header">
                <h3 style="font-size: 1.1rem; font-weight: 700;">Edit Fasilitas</h3>
                <button type="button" class="btn btn-outline btn-sm" onclick="closeModal('modalEditFasilitas')">&times;</button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label>Nama Fasilitas *</label>
                    <input type="text" name="nama_fasilitas" id="edit_nama_fasilitas" class="form-control" required>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Kelas Ikon FontAwesome (fa-*)</label>
                        <input type="text" name="ikon" id="edit_ikon" class="form-control">
                    </div>

                    <div class="form-group">
                        <label>Tipe Fasilitas *</label>
                        <select name="tipe" id="edit_tipe" class="form-control" required>
                            <option value="kamar">Fasilitas Dalam Kamar</option>
                            <option value="bersama">Fasilitas Umum / Bersama</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label>Keterangan Tambahan</label>
                    <input type="text" name="keterangan" id="edit_keterangan" class="form-control">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal('modalEditFasilitas')">Batal</button>
                <button type="submit" name="btn_edit_fasilitas" class="btn btn-primary">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>

<script>
function editFasilitas(f) {
    document.getElementById('edit_id_fasilitas').value = f.id_fasilitas;
    document.getElementById('edit_nama_fasilitas').value = f.nama_fasilitas;
    document.getElementById('edit_ikon').value = f.ikon;
    document.getElementById('edit_tipe').value = f.tipe;
    document.getElementById('edit_keterangan').value = f.keterangan || '';
    openModal('modalEditFasilitas');
}
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
