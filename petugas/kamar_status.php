<?php
/**
 * Kondisi & Status Kamar Kost (Petugas)
 */
require_once __DIR__ . '/../koneksi.php';
require_once __DIR__ . '/../config/helper.php';

auth_check(['petugas', 'admin']);

// 1. PROSES POST SEBELUM HEADER.PHP
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['btn_update_status_kamar'])) {
    $id_kamar = (int)$_POST['id_kamar'];
    $status_baru = clean($conn, $_POST['status']);

    mysqli_query($conn, "UPDATE tbl_kamar SET status = '$status_baru' WHERE id_kamar = $id_kamar");
    set_flash('success', "Status kamar berhasil diubah menjadi '" . ucfirst($status_baru) . "'.");
    header("Location: kamar_status.php");
    exit();
}

$kamar_list = mysqli_query($conn, "SELECT k.*, kat.nama_kategori, kat.jenis_penghuni 
                                   FROM tbl_kamar k 
                                   JOIN tbl_kategori_kamar kat ON k.id_kategori = kat.id_kategori 
                                   ORDER BY k.lantai ASC, k.nomor_kamar ASC");

$page_title = "Kondisi & Status Kamar Kost";
require_once __DIR__ . '/header.php';
?>

<div class="card">
    <div class="card-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
        <h3 class="card-title"><i class="fa-solid fa-door-open"></i> Pantau Ketersediaan & Kondisi Kamar</h3>
        <input type="text" class="form-control table-search" data-target-table="statusKamarTable" placeholder="Cari kamar..." style="width: 200px; padding: 7px 12px;">
    </div>

    <div class="card-body" style="padding: 0;">
        <div class="table-responsive">
            <table class="data-table" id="statusKamarTable">
                <thead>
                    <tr>
                        <th>Nomor Kamar</th>
                        <th>Nama Kamar & Tipe</th>
                        <th>Lantai & Ukuran</th>
                        <th>Tarif Sewa</th>
                        <th>Status Saat Ini</th>
                        <th>Ubah Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($kamar_list && mysqli_num_rows($kamar_list) > 0): ?>
                        <?php while ($km = mysqli_fetch_assoc($kamar_list)): 
                            $badge = [
                                'tersedia' => 'badge-success',
                                'terisi' => 'badge-danger',
                                'renovasi' => 'badge-warning'
                            ][$km['status']] ?? 'badge-secondary';
                        ?>
                            <tr>
                                <td>
                                    <b style="font-size: 1.05rem; color: var(--adm-primary);">Kamar <?= htmlspecialchars($km['nomor_kamar']) ?></b>
                                </td>
                                <td>
                                    <b><?= htmlspecialchars($km['nama_kamar']) ?></b><br>
                                    <small style="color: var(--adm-muted);"><?= htmlspecialchars($km['nama_kategori']) ?> (<?= $km['jenis_penghuni'] ?>)</small>
                                </td>
                                <td>
                                    Lantai <?= $km['lantai'] ?><br>
                                    <small style="color: var(--adm-muted);"><?= htmlspecialchars($km['ukuran']) ?></small>
                                </td>
                                <td><b style="color: #059669;"><?= format_rupiah($km['harga_per_bulan']) ?></b>/bln</td>
                                <td><span class="badge <?= $badge ?>"><?= ucfirst($km['status']) ?></span></td>
                                <td>
                                    <form action="kamar_status.php" method="POST" style="display: flex; gap: 6px; align-items: center;">
                                        <input type="hidden" name="id_kamar" value="<?= $km['id_kamar'] ?>">
                                        <select name="status" class="form-control" style="width: 120px; padding: 4px 8px; font-size: 0.82rem;">
                                            <option value="tersedia" <?= $km['status'] == 'tersedia' ? 'selected' : '' ?>>Tersedia</option>
                                            <option value="terisi" <?= $km['status'] == 'terisi' ? 'selected' : '' ?>>Terisi</option>
                                            <option value="renovasi" <?= $km['status'] == 'renovasi' ? 'selected' : '' ?>>Renovasi</option>
                                        </select>
                                        <button type="submit" name="btn_update_status_kamar" class="btn btn-primary btn-sm" title="Simpan Status">
                                            <i class="fa-solid fa-save"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="6" style="text-align: center; color: var(--adm-muted); padding: 24px;">Belum ada data kamar.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
