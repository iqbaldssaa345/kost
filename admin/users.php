<?php
/**
 * Kelola Pengguna (Admin)
 */
require_once __DIR__ . '/../koneksi.php';
require_once __DIR__ . '/../config/helper.php';

auth_check(['admin']);

// 1. PROSES TAMBAH USER (Sebelum header.php)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['btn_tambah_user'])) {
    $username = clean($conn, $_POST['username']);
    $nama_lengkap = clean($conn, $_POST['nama_lengkap']);
    $email = clean($conn, $_POST['email']);
    $no_hp = clean($conn, $_POST['no_hp']);
    $role = clean($conn, $_POST['role']);
    $pekerjaan = clean($conn, $_POST['pekerjaan']);
    $alamat = clean($conn, $_POST['alamat_asal']);
    $password = $_POST['password'];

    // Cek duplikasi
    $cek = mysqli_query($conn, "SELECT id_user FROM tbl_users WHERE username = '$username' OR email = '$email'");
    if (mysqli_num_rows($cek) > 0) {
        set_flash('danger', 'Username atau email sudah digunakan pengguna lain!');
    } else {
        $pass_hash = md5($password);
        $stmt = mysqli_prepare($conn, "INSERT INTO tbl_users (username, password, nama_lengkap, email, no_hp, role, pekerjaan, alamat_asal, status_aktif) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'aktif')");
        mysqli_stmt_bind_param($stmt, "ssssssss", $username, $pass_hash, $nama_lengkap, $email, $no_hp, $role, $pekerjaan, $alamat);
        if (mysqli_stmt_execute($stmt)) {
            set_flash('success', "Pengguna baru ($nama_lengkap) berhasil ditambahkan.");
        } else {
            set_flash('danger', "Gagal menambah user: " . mysqli_error($conn));
        }
    }
    header("Location: users.php");
    exit();
}

// 2. PROSES EDIT USER
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['btn_edit_user'])) {
    $id_user = (int)$_POST['id_user'];
    $nama_lengkap = clean($conn, $_POST['nama_lengkap']);
    $email = clean($conn, $_POST['email']);
    $no_hp = clean($conn, $_POST['no_hp']);
    $role = clean($conn, $_POST['role']);
    $pekerjaan = clean($conn, $_POST['pekerjaan']);
    $alamat = clean($conn, $_POST['alamat_asal']);
    $status_aktif = clean($conn, $_POST['status_aktif']);
    $password = $_POST['password'];

    if (!empty($password)) {
        $pass_hash = md5($password);
        $stmt = mysqli_prepare($conn, "UPDATE tbl_users SET nama_lengkap=?, email=?, no_hp=?, role=?, pekerjaan=?, alamat_asal=?, status_aktif=?, password=? WHERE id_user=?");
        mysqli_stmt_bind_param($stmt, "ssssssssi", $nama_lengkap, $email, $no_hp, $role, $pekerjaan, $alamat, $status_aktif, $pass_hash, $id_user);
    } else {
        $stmt = mysqli_prepare($conn, "UPDATE tbl_users SET nama_lengkap=?, email=?, no_hp=?, role=?, pekerjaan=?, alamat_asal=?, status_aktif=? WHERE id_user=?");
        mysqli_stmt_bind_param($stmt, "sssssssi", $nama_lengkap, $email, $no_hp, $role, $pekerjaan, $alamat, $status_aktif, $id_user);
    }

    if (mysqli_stmt_execute($stmt)) {
        set_flash('success', "Data pengguna ($nama_lengkap) berhasil diperbarui.");
    } else {
        set_flash('danger', "Gagal memperbarui user: " . mysqli_error($conn));
    }
    header("Location: users.php");
    exit();
}

// 3. PROSES HAPUS USER
if (isset($_GET['hapus'])) {
    $id_hapus = (int)$_GET['hapus'];
    if ($id_hapus === (int)$_SESSION['user_id']) {
        set_flash('danger', 'Anda tidak dapat menghapus akun Anda sendiri yang sedang aktif!');
    } else {
        mysqli_query($conn, "DELETE FROM tbl_users WHERE id_user = $id_hapus");
        set_flash('success', 'Pengguna berhasil dihapus.');
    }
    header("Location: users.php");
    exit();
}

// Filter Role
$role_filter = isset($_GET['role']) ? clean($conn, $_GET['role']) : '';
$where = "WHERE 1=1";
if (!empty($role_filter)) {
    $where .= " AND role = '$role_filter'";
}

$users_list = mysqli_query($conn, "SELECT * FROM tbl_users $where ORDER BY id_user DESC");

$page_title = "Kelola Pengguna";
require_once __DIR__ . '/header.php';
?>

<div class="card">
    <div class="card-header" style="flex-wrap: wrap; gap: 12px;">
        <div style="display: flex; align-items: center; gap: 12px; flex: 1;">
            <h3 class="card-title">Data Seluruh Pengguna</h3>
            <div style="display: flex; gap: 6px;">
                <a href="users.php" class="btn btn-sm <?= empty($role_filter) ? 'btn-primary' : 'btn-outline' ?>">Semua</a>
                <a href="users.php?role=admin" class="btn btn-sm <?= $role_filter == 'admin' ? 'btn-primary' : 'btn-outline' ?>">Admin</a>
                <a href="users.php?role=petugas" class="btn btn-sm <?= $role_filter == 'petugas' ? 'btn-primary' : 'btn-outline' ?>">Petugas</a>
                <a href="users.php?role=pengunjung" class="btn btn-sm <?= $role_filter == 'pengunjung' ? 'btn-primary' : 'btn-outline' ?>">Pengunjung</a>
            </div>
        </div>

        <div style="display: flex; gap: 10px; align-items: center;">
            <input type="text" class="form-control table-search" data-target-table="usersTable" placeholder="Cari nama/username..." style="width: 220px; padding: 7px 12px;">
            <button type="button" class="btn btn-primary btn-sm" onclick="openModal('modalTambahUser')">
                <i class="fa-solid fa-user-plus"></i> Tambah Pengguna
            </button>
        </div>
    </div>

    <div class="card-body" style="padding: 0;">
        <div class="table-responsive">
            <table class="data-table" id="usersTable">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Pengguna</th>
                        <th>Role</th>
                        <th>Kontak</th>
                        <th>Pekerjaan / Asal</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($users_list && mysqli_num_rows($users_list) > 0): $no = 1; ?>
                        <?php while ($u = mysqli_fetch_assoc($users_list)): 
                            $roleBadges = [
                                'admin' => 'badge-danger',
                                'petugas' => 'badge-warning',
                                'pengunjung' => 'badge-info'
                            ][$u['role']] ?? 'badge-secondary';
                        ?>
                            <tr>
                                <td><?= $no++ ?></td>
                                <td>
                                    <b><?= htmlspecialchars($u['nama_lengkap']) ?></b><br>
                                    <small style="color: var(--adm-muted);">@<?= htmlspecialchars($u['username']) ?></small>
                                </td>
                                <td><span class="badge <?= $roleBadges ?>"><?= ucfirst($u['role']) ?></span></td>
                                <td>
                                    <div><i class="fa-brands fa-whatsapp" style="color:#25D366;"></i> <?= htmlspecialchars($u['no_hp']) ?></div>
                                    <small style="color: var(--adm-muted);"><?= htmlspecialchars($u['email']) ?></small>
                                </td>
                                <td>
                                    <span><?= htmlspecialchars($u['pekerjaan'] ?? '-') ?></span><br>
                                    <small style="color: var(--adm-muted);"><?= htmlspecialchars($u['alamat_asal'] ?? '-') ?></small>
                                </td>
                                <td>
                                    <span class="badge <?= $u['status_aktif'] == 'aktif' ? 'badge-success' : 'badge-danger' ?>">
                                        <?= ucfirst($u['status_aktif']) ?>
                                    </span>
                                </td>
                                <td>
                                    <div style="display: flex; gap: 6px;">
                                        <button type="button" class="btn btn-outline btn-sm" onclick='editUser(<?= json_encode($u) ?>)' title="Edit">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </button>
                                        <?php if ($u['id_user'] != $_SESSION['user_id']): ?>
                                            <button type="button" class="btn btn-danger btn-sm" onclick="confirmAction('Hapus akun pengguna <?= addslashes($u['nama_lengkap']) ?>?', 'users.php?hapus=<?= $u['id_user'] ?>')" title="Hapus">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="7" style="text-align: center; color: var(--adm-muted);">Tidak ada pengguna ditemukan.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- MODAL TAMBAH USER -->
<div class="modal-overlay" id="modalTambahUser">
    <div class="modal-box">
        <form action="users.php" method="POST">
            <div class="modal-header">
                <h3 style="font-size: 1.1rem; font-weight: 700;">Tambah Pengguna Baru</h3>
                <button type="button" class="btn btn-outline btn-sm" onclick="closeModal('modalTambahUser')">&times;</button>
            </div>
            <div class="modal-body">
                <div class="form-row">
                    <div class="form-group">
                        <label>Nama Lengkap *</label>
                        <input type="text" name="nama_lengkap" class="form-control" required placeholder="Budi Pratama">
                    </div>
                    <div class="form-group">
                        <label>Username *</label>
                        <input type="text" name="username" class="form-control" required placeholder="budi123">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Email *</label>
                        <input type="email" name="email" class="form-control" required placeholder="budi@gmail.com">
                    </div>
                    <div class="form-group">
                        <label>No. HP / WhatsApp *</label>
                        <input type="tel" name="no_hp" class="form-control" required placeholder="08123456789">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Role Akses *</label>
                        <select name="role" class="form-control" required>
                            <option value="pengunjung">Pengunjung / Penyewa</option>
                            <option value="petugas">Petugas Kost</option>
                            <option value="admin">Administrator</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Password *</label>
                        <input type="password" name="password" class="form-control" required placeholder="Minimal 6 karakter">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Pekerjaan</label>
                        <input type="text" name="pekerjaan" class="form-control" placeholder="Mahasiswa / Swasta">
                    </div>
                    <div class="form-group">
                        <label>Alamat Asal</label>
                        <input type="text" name="alamat_asal" class="form-control" placeholder="Kota Asal">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal('modalTambahUser')">Batal</button>
                <button type="submit" name="btn_tambah_user" class="btn btn-primary"><i class="fa-solid fa-check"></i> Simpan Pengguna</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL EDIT USER -->
<div class="modal-overlay" id="modalEditUser">
    <div class="modal-box">
        <form action="users.php" method="POST">
            <input type="hidden" name="id_user" id="edit_id_user">
            <div class="modal-header">
                <h3 style="font-size: 1.1rem; font-weight: 700;">Edit Data Pengguna</h3>
                <button type="button" class="btn btn-outline btn-sm" onclick="closeModal('modalEditUser')">&times;</button>
            </div>
            <div class="modal-body">
                <div class="form-row">
                    <div class="form-group">
                        <label>Nama Lengkap *</label>
                        <input type="text" name="nama_lengkap" id="edit_nama_lengkap" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Role Akses *</label>
                        <select name="role" id="edit_role" class="form-control" required>
                            <option value="pengunjung">Pengunjung / Penyewa</option>
                            <option value="petugas">Petugas Kost</option>
                            <option value="admin">Administrator</option>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Email *</label>
                        <input type="email" name="email" id="edit_email" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>No. HP / WhatsApp *</label>
                        <input type="tel" name="no_hp" id="edit_no_hp" class="form-control" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Status Akun</label>
                        <select name="status_aktif" id="edit_status_aktif" class="form-control">
                            <option value="aktif">Aktif</option>
                            <option value="nonaktif">Nonaktif</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Password Baru (Kosongkan jika tidak diganti)</label>
                        <input type="password" name="password" class="form-control" placeholder="Ganti password baru...">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Pekerjaan</label>
                        <input type="text" name="pekerjaan" id="edit_pekerjaan" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>Alamat Asal</label>
                        <input type="text" name="alamat_asal" id="edit_alamat_asal" class="form-control">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal('modalEditUser')">Batal</button>
                <button type="submit" name="btn_edit_user" class="btn btn-primary"><i class="fa-solid fa-floppy-disk"></i> Update Data</button>
            </div>
        </form>
    </div>
</div>

<script>
function editUser(user) {
    document.getElementById('edit_id_user').value = user.id_user;
    document.getElementById('edit_nama_lengkap').value = user.nama_lengkap;
    document.getElementById('edit_email').value = user.email;
    document.getElementById('edit_no_hp').value = user.no_hp;
    document.getElementById('edit_role').value = user.role;
    document.getElementById('edit_status_aktif').value = user.status_aktif;
    document.getElementById('edit_pekerjaan').value = user.pekerjaan || '';
    document.getElementById('edit_alamat_asal').value = user.alamat_asal || '';
    openModal('modalEditUser');
}
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
