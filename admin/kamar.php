<?php
/**
 * Data Kamar Kost (Admin)
 */
require_once __DIR__ . '/../koneksi.php';
require_once __DIR__ . '/../config/helper.php';

auth_check(['admin']);

// 1. PROSES HAPUS KAMAR (Sebelum header.php)
if (isset($_GET['hapus'])) {
    $id_hapus = (int)$_GET['hapus'];
    
    // Ambil info foto kamar untuk dihapus dari disk
    $q_foto = mysqli_query($conn, "SELECT foto_utama FROM tbl_kamar WHERE id_kamar = $id_hapus");
    if ($row = mysqli_fetch_assoc($q_foto)) {
        if (!empty($row['foto_utama']) && file_exists(__DIR__ . '/../uploads/kost/' . $row['foto_utama'])) {
            @unlink(__DIR__ . '/../uploads/kost/' . $row['foto_utama']);
        }
    }
    
    // Hapus relasi foto galeri
    $q_gal = mysqli_query($conn, "SELECT nama_file FROM tbl_foto_kamar WHERE id_kamar = $id_hapus");
    while ($g = mysqli_fetch_assoc($q_gal)) {
        if (file_exists(__DIR__ . '/../uploads/kost/' . $g['nama_file'])) {
            @unlink(__DIR__ . '/../uploads/kost/' . $g['nama_file']);
        }
    }

    mysqli_query($conn, "DELETE FROM tbl_kamar WHERE id_kamar = $id_hapus");
    set_flash('success', 'Data kamar kost dan seluruh fotonya berhasil dihapus.');
    header("Location: kamar.php");
    exit();
}

// Filter
$filter_kat = isset($_GET['kategori']) ? (int)$_GET['kategori'] : 0;
$filter_status = isset($_GET['status']) ? clean($conn, $_GET['status']) : '';

$where = "WHERE 1=1";
if ($filter_kat > 0) $where .= " AND k.id_kategori = $filter_kat";
if (!empty($filter_status)) $where .= " AND k.status = '$filter_status'";

$kategori_list = mysqli_query($conn, "SELECT * FROM tbl_kategori_kamar ORDER BY id_kategori ASC");

$kamar_list = mysqli_query($conn, "SELECT k.*, kat.nama_kategori, kat.jenis_penghuni 
                                   FROM tbl_kamar k 
                                   JOIN tbl_kategori_kamar kat ON k.id_kategori = kat.id_kategori 
                                   $where ORDER BY k.lantai ASC, k.nomor_kamar ASC");

$page_title = "Data Kamar Kost";
require_once __DIR__ . '/header.php';
?>

<div class="card">
    <div class="card-header" style="flex-wrap: wrap; gap: 14px;">
        <div style="display: flex; align-items: center; gap: 12px; flex: 1;">
            <h3 class="card-title">Daftar Kamar Kost</h3>
            <a href="kamar_tambah.php" class="btn btn-primary btn-sm">
                <i class="fa-solid fa-plus"></i> Tambah Kamar Baru
            </a>
        </div>

        <div style="display: flex; gap: 10px; align-items: center;">
            <form action="kamar.php" method="GET" style="display: flex; gap: 8px;">
                <select name="status" class="form-control" style="width: 140px; padding: 7px 10px;" onchange="this.form.submit()">
                    <option value="">Semua Status</option>
                    <option value="tersedia" <?= $filter_status == 'tersedia' ? 'selected' : '' ?>>Tersedia</option>
                    <option value="terisi" <?= $filter_status == 'terisi' ? 'selected' : '' ?>>Terisi</option>
                    <option value="renovasi" <?= $filter_status == 'renovasi' ? 'selected' : '' ?>>Renovasi</option>
                </select>

                <select name="kategori" class="form-control" style="width: 160px; padding: 7px 10px;" onchange="this.form.submit()">
                    <option value="0">Semua Tipe</option>
                    <?php if ($kategori_list): ?>
                        <?php while ($kat = mysqli_fetch_assoc($kategori_list)): ?>
                            <option value="<?= $kat['id_kategori'] ?>" <?= $filter_kat == $kat['id_kategori'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($kat['nama_kategori']) ?>
                            </option>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </select>
            </form>

            <input type="text" class="form-control table-search" data-target-table="kamarTable" placeholder="Cari kamar..." style="width: 180px; padding: 7px 12px;">
        </div>
    </div>

    <div class="card-body" style="padding: 0;">
        <div class="table-responsive">
            <table class="data-table" id="kamarTable">
                <thead>
                    <tr>
                        <th>Foto</th>
                        <th>Nomor & Nama Kamar</th>
                        <th>Tipe / Penghuni</th>
                        <th>Ukuran / Lantai</th>
                        <th>Harga Sewa</th>
                        <th>Fasilitas</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($kamar_list && mysqli_num_rows($kamar_list) > 0): ?>
                        <?php while ($k = mysqli_fetch_assoc($kamar_list)): 
                            $id_k = $k['id_kamar'];
                            $q_fas_c = mysqli_query($conn, "SELECT COUNT(*) as tot FROM tbl_kamar_fasilitas WHERE id_kamar = $id_k");
                            $tot_fas = mysqli_fetch_assoc($q_fas_c)['tot'] ?? 0;

                            $foto_path = '../uploads/kost/' . $k['foto_utama'];
                            $img_src = (file_exists($foto_path) && !empty($k['foto_utama'])) ? $foto_path : 'https://images.unsplash.com/photo-1598928506311-c55ded91a20c?auto=format&fit=crop&w=200&q=80';

                            $badgeStatus = [
                                'tersedia' => 'badge-success',
                                'terisi' => 'badge-danger',
                                'renovasi' => 'badge-warning'
                            ][$k['status']] ?? 'badge-secondary';
                        ?>
                            <tr>
                                <td>
                                    <img src="<?= $img_src ?>" alt="Foto" style="width: 60px; height: 48px; object-fit: cover; border-radius: 6px;">
                                </td>
                                <td>
                                    <b style="font-size: 0.95rem; color: var(--adm-text);"><?= htmlspecialchars($k['nama_kamar']) ?></b><br>
                                    <span style="font-size: 0.8rem; background: #e0e7ff; color: #4338ca; padding: 2px 6px; border-radius: 4px; font-weight: 700;">
                                        No. <?= htmlspecialchars($k['nomor_kamar']) ?>
                                    </span>
                                </td>
                                <td>
                                    <span><?= htmlspecialchars($k['nama_kategori']) ?></span><br>
                                    <small style="color: var(--adm-muted);">Khusus <?= htmlspecialchars($k['jenis_penghuni']) ?></small>
                                </td>
                                <td>
                                    <span><?= htmlspecialchars($k['ukuran']) ?></span><br>
                                    <small style="color: var(--adm-muted);">Lantai <?= $k['lantai'] ?></small>
                                </td>
                                <td>
                                    <b style="color: var(--adm-primary);"><?= format_rupiah($k['harga_per_bulan']) ?></b><small>/bln</small>
                                    <?php if (!empty($k['harga_per_tahun']) && $k['harga_per_tahun'] > 0): ?>
                                        <br><span style="color: #059669; font-size: 0.8rem; font-weight: 700;"><?= format_rupiah($k['harga_per_tahun']) ?></span><small style="color: #059669;">/thn</small>
                                    <?php endif; ?>
                                    <?php if (!empty($k['harga_per_hari']) && $k['harga_per_hari'] > 0): ?>
                                        <br><small style="color: var(--adm-muted);"><?= format_rupiah($k['harga_per_hari']) ?>/hr</small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge badge-secondary"><?= $tot_fas ?> Fasilitas</span>
                                </td>
                                <td>
                                    <span class="badge <?= $badgeStatus ?>"><?= ucfirst($k['status']) ?></span>
                                </td>
                                <td>
                                    <div style="display: flex; gap: 6px;">
                                        <a href="../detail_kamar.php?id=<?= $k['id_kamar'] ?>" target="_blank" class="btn btn-outline btn-sm" title="Pratinjau Web">
                                            <i class="fa-solid fa-eye"></i>
                                        </a>
                                        <a href="kamar_edit.php?id=<?= $k['id_kamar'] ?>" class="btn btn-outline btn-sm" title="Edit Kamar">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </a>
                                        <button type="button" class="btn btn-danger btn-sm" onclick="confirmAction('Hapus kamar <?= addslashes($k['nama_kamar']) ?>?', 'kamar.php?hapus=<?= $k['id_kamar'] ?>')" title="Hapus">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="8" style="text-align: center; color: var(--adm-muted);">Tidak ada data kamar kost.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
