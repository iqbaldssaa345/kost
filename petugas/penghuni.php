<?php
$page_title = "Data Penghuni Kost Aktif";
require_once __DIR__ . '/header.php';

// Ambil Seluruh Penghuni Aktif
$q_penghuni = mysqli_query($conn, "SELECT b.*, u.nama_lengkap, u.no_hp, u.email, u.alamat_asal, u.pekerjaan, k.nomor_kamar, k.nama_kamar, k.harga_per_bulan, k.lantai 
                                   FROM tbl_booking b 
                                   JOIN tbl_users u ON b.id_user = u.id_user 
                                   JOIN tbl_kamar k ON b.id_kamar = k.id_kamar 
                                   WHERE b.status_booking = 'aktif' 
                                   ORDER BY k.nomor_kamar ASC");
?>

<div class="card">
    <div class="card-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
        <div>
            <h3 class="card-title"><i class="fa-solid fa-users"></i> Daftar Seluruh Penghuni Kost Aktif</h3>
            <span style="font-size: 0.8rem; color: var(--adm-muted);">Monitoring penyewa yang sedang aktif menempati kamar saat ini</span>
        </div>

        <div style="display: flex; gap: 10px;">
            <a href="tagihan_input.php" class="btn btn-primary btn-sm">
                <i class="fa-solid fa-plus"></i> Input Tagihan Baru
            </a>
            <input type="text" class="form-control table-search" data-target-table="penghuniTable" placeholder="Cari penghuni / kamar..." style="width: 200px; padding: 7px 12px;">
        </div>
    </div>

    <div class="card-body" style="padding: 0;">
        <div class="table-responsive">
            <table class="data-table" id="penghuniTable">
                <thead>
                    <tr>
                        <th>Kamar</th>
                        <th>Nama Penghuni</th>
                        <th>Kontak & Alamat</th>
                        <th>Mulai Masuk</th>
                        <th>Batas Akhir Sewa</th>
                        <th>Tarif / Bulan</th>
                        <th>Aksi Petugas</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($q_penghuni && mysqli_num_rows($q_penghuni) > 0): ?>
                        <?php while ($p = mysqli_fetch_assoc($q_penghuni)): 
                            // Cek apakah mendekati jatuh tempo (kurang dari 7 hari)
                            $sisa_hari = (strtotime($p['tgl_selesai_sewa']) - time()) / (60 * 60 * 24);
                        ?>
                            <tr>
                                <td>
                                    <span style="font-size: 1rem; font-weight: 800; color: #4338ca; background: #e0e7ff; padding: 4px 10px; border-radius: 6px;">
                                        <?= htmlspecialchars($p['nomor_kamar']) ?>
                                    </span><br>
                                    <small style="color: var(--adm-muted);">Lt. <?= $p['lantai'] ?></small>
                                </td>
                                <td>
                                    <b style="font-size: 0.95rem;"><?= htmlspecialchars($p['nama_lengkap']) ?></b><br>
                                    <small style="color: var(--adm-muted);"><?= htmlspecialchars($p['pekerjaan'] ?? 'Penghuni') ?></small>
                                </td>
                                <td>
                                    <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $p['no_hp']) ?>?text=Halo%20<?= urlencode($p['nama_lengkap']) ?>%20(Kamar%20<?= urlencode($p['nomor_kamar']) ?>)" target="_blank" style="color: #10b981; font-weight: 600; font-size: 0.85rem;">
                                        <i class="fa-brands fa-whatsapp"></i> <?= htmlspecialchars($p['no_hp']) ?>
                                    </a><br>
                                    <small style="color: var(--adm-muted);"><?= htmlspecialchars($p['alamat_asal'] ?? '-') ?></small>
                                </td>
                                <td><?= tgl_indo($p['tgl_mulai_sewa']) ?></td>
                                <td>
                                    <b><?= tgl_indo($p['tgl_selesai_sewa']) ?></b><br>
                                    <?php if ($sisa_hari <= 7 && $sisa_hari >= 0): ?>
                                        <span class="badge badge-warning" style="font-size: 0.7rem;">Sisa <?= ceil($sisa_hari) ?> Hari</span>
                                    <?php elseif ($sisa_hari < 0): ?>
                                        <span class="badge badge-danger" style="font-size: 0.7rem;">Sudah Lewat</span>
                                    <?php else: ?>
                                        <span class="badge badge-success" style="font-size: 0.7rem;">Aktif (<?= ceil($sisa_hari) ?> Hari)</span>
                                    <?php endif; ?>
                                </td>
                                <td><b style="color: #059669;"><?= format_rupiah($p['harga_per_bulan']) ?></b></td>
                                <td>
                                    <div style="display: flex; gap: 6px;">
                                        <a href="tagihan_input.php?user=<?= $p['id_user'] ?>&kamar=<?= $p['id_kamar'] ?>" class="btn btn-primary btn-sm" title="Buat Tagihan">
                                            <i class="fa-solid fa-file-invoice"></i> Tagih
                                        </a>
                                        <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $p['no_hp']) ?>?text=Halo%20<?= urlencode($p['nama_lengkap']) ?>,%20kami%20mengingatkan%20masa%20sewa%20kamar%20<?= urlencode($p['nomor_kamar']) ?>%20akan%20berakhir%20tanggal%20<?= urlencode(tgl_indo($p['tgl_selesai_sewa'])) ?>." target="_blank" class="btn btn-outline btn-sm" title="Ingatkan Sewa via WA" style="color: #25D366;">
                                            <i class="fa-brands fa-whatsapp"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="7" style="text-align: center; color: var(--adm-muted);">Saat ini belum ada data penghuni aktif.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
