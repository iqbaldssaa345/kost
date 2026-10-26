<?php
$page_title = "Tagihan & Pembayaran Saya";
require_once __DIR__ . '/header.php';

$id_user = (int)$_SESSION['user_id'];

$my_bills = mysqli_query($conn, "SELECT t.*, k.nomor_kamar, k.nama_kamar 
                                 FROM tbl_tagihan_pembayaran t 
                                 JOIN tbl_kamar k ON t.id_kamar = k.id_kamar 
                                 WHERE t.id_user = $id_user 
                                 ORDER BY (t.status_pembayaran = 'belum_bayar') DESC, t.id_tagihan DESC");
?>

<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fa-solid fa-file-invoice-dollar"></i> Seluruh Tagihan & Pembayaran Anda</h3>
    </div>
    <div class="card-body" style="padding: 0;">
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Kode Invoice</th>
                        <th>Kamar</th>
                        <th>Keterangan Tagihan</th>
                        <th>Nominal</th>
                        <th>Jatuh Tempo</th>
                        <th>Status Pembayaran</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($my_bills && mysqli_num_rows($my_bills) > 0): ?>
                        <?php while ($t = mysqli_fetch_assoc($my_bills)): 
                            $statusBadge = [
                                'belum_bayar' => 'badge-danger',
                                'menunggu_verifikasi' => 'badge-warning',
                                'lunas' => 'badge-success',
                                'ditolak' => 'badge-secondary'
                            ][$t['status_pembayaran']] ?? 'badge-secondary';
                        ?>
                            <tr>
                                <td>
                                    <b style="color: var(--adm-primary);"><?= htmlspecialchars($t['kode_tagihan']) ?></b><br>
                                    <small style="color: var(--adm-muted);"><?= tgl_indo($t['created_at']) ?></small>
                                </td>
                                <td>
                                    <span style="font-size: 0.8rem; background: #e0e7ff; color: #4338ca; padding: 2px 6px; border-radius: 4px; font-weight: 700;">
                                        Kamar <?= htmlspecialchars($t['nomor_kamar']) ?>
                                    </span>
                                </td>
                                <td>
                                    <b><?= htmlspecialchars($t['judul_tagihan']) ?></b><br>
                                    <small style="color: var(--adm-muted);">Periode: <?= htmlspecialchars($t['periode_bulan']) ?></small>
                                </td>
                                <td><b style="color: #059669; font-size: 1rem;"><?= format_rupiah($t['nominal']) ?></b></td>
                                <td>
                                    <span><?= tgl_indo($t['tgl_jatuh_tempo']) ?></span>
                                </td>
                                <td>
                                    <span class="badge <?= $statusBadge ?>"><?= str_replace('_', ' ', ucfirst($t['status_pembayaran'])) ?></span>
                                    <?php if (!empty($t['catatan_petugas'])): ?>
                                        <br><small style="color: var(--adm-muted); font-size: 0.72rem;">Catatan: <?= htmlspecialchars($t['catatan_petugas']) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div style="display: flex; gap: 6px;">
                                        <?php if (in_array($t['status_pembayaran'], ['belum_bayar', 'ditolak'])): ?>
                                            <a href="bayar_upload.php?id=<?= $t['id_tagihan'] ?>" class="btn btn-primary btn-sm">
                                                <i class="fa-solid fa-upload"></i> Bayar
                                            </a>
                                        <?php elseif ($t['status_pembayaran'] === 'menunggu_verifikasi'): ?>
                                            <a href="bayar_upload.php?id=<?= $t['id_tagihan'] ?>" class="btn btn-outline btn-sm" title="Lihat Bukti">
                                                <i class="fa-solid fa-clock"></i> Cek Bukti
                                            </a>
                                        <?php elseif ($t['status_pembayaran'] === 'lunas'): ?>
                                            <a href="invoice.php?id=<?= $t['id_tagihan'] ?>" target="_blank" class="btn btn-outline btn-sm" title="Kuitansi Digital">
                                                <i class="fa-solid fa-print"></i> Kuitansi
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="7" style="text-align: center; color: var(--adm-muted);">Tidak ada riwayat tagihan.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
