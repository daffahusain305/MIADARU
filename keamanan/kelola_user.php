<?php
// Jalur koneksi yang aman untuk include
if (file_exists('koneksi.php')) {
    include 'koneksi.php';
} else {
    include '../koneksi.php';
}

// PROTEKSI: Jika bukan Owner, hentikan script
if ($_SESSION['role'] !== 'Owner') {
    echo "<div class='alert alert-danger'>Akses Ditolak!</div>";
    return; 
}

$pesan_error = "";

// PROSES TAMBAH USER
if (isset($_POST['tambah_user'])) {
    $username = trim($_POST['username']);
    $nama = trim($_POST['nama_lengkap']);
    $password_raw = $_POST['password'];
    $role = $_POST['id_role'];

    // --- VALIDASI SERVER-SIDE (KEAMANAN DINGDING BELAKANG) ---
    
    // 1. Validasi Username (Maksimal 25 karakter)
    if (strlen($username) > 25) {
        $pesan_error = "Maksimal 25 karakter!";
    }
    // 2. Validasi Nama Lengkap (Hanya Huruf dan Spasi, Maksimal 40 karakter)
    elseif (!preg_match("/^[a-zA-A\s]+$/", $nama)) {
        $pesan_error = "Nama Lengkap hanya boleh berisi huruf dan spasi!";
    } elseif (strlen($nama) > 40) {
        $pesan_error = "Maksimal 40 karakter!";
    }
    // 3. Validasi Password (Maksimal 15 karakter)
    elseif (strlen($password_raw) > 15) {
        $pesan_error = "Maksimal 15 karakter!";
    }
    // 4. Validasi Role
    elseif ($role == '0' || empty($role)) {
        $pesan_error = "Silakan pilih Jabatan (Role)";
    }
    else {
        // Jika semua validasi lolos, amankan string dan hash password
        $username_clean = mysqli_real_escape_string($conn, substr($username, 0, 25));
        $nama_clean = mysqli_real_escape_string($conn, substr($nama, 0, 40));
        $password = password_hash(substr($password_raw, 0, 15), PASSWORD_DEFAULT);

        $query = "INSERT INTO users (username, password, nama_lengkap, id_role) VALUES ('$username_clean', '$password', '$nama_clean', '$role')";
        if (mysqli_query($conn, $query)) {
            echo "<script>window.location.href='index.php?page=kelola_user&pesan=berhasil';</script>";
            exit();
        }
    }
}

// PROSES HAPUS USER
if (isset($_GET['hapus'])) {
    $id = mysqli_real_escape_string($conn, $_GET['hapus']);
    mysqli_query($conn, "DELETE FROM users WHERE id_user = '$id'");
    echo "<script>window.location.href='index.php?page=kelola_user&pesan=hapus';</script>";
    exit();
}

$users = mysqli_query($conn, "SELECT u.*, r.nama_role FROM users u JOIN roles r ON u.id_role = r.id_role");
?>

<div class="card border-0 shadow-sm rounded-4">
    <div class="card-body p-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="fw-bold mb-0 text-dark">Manajemen Karyawan</h4>
        </div>

        <?php if(isset($_GET['pesan'])): ?>
            <div class="alert alert-success border-0 shadow-sm mb-4">
                <i class="bi bi-check-circle-fill me-2"></i> Proses Berhasil Diperbarui!
            </div>
        <?php endif; ?>

        <?php if(!empty($pesan_error)): ?>
            <div class="alert alert-danger border-0 shadow-sm mb-4">
                <i class="bi bi-exclamation-triangle-fill me-2"></i> <?= $pesan_error ?>
            </div>
        <?php endif; ?>

        <div class="row g-4">
            <div class="col-md-4">
                <div class="card border p-3 rounded-4 bg-light">
                    <h5 class="fw-bold mb-3">Tambah User</h5>
                    <form method="POST">
                        <div class="mb-2">
                            <label class="small fw-bold">Username <span class="text-muted">(Maks. 25 Karakter)</span></label>
                            <input type="text" name="username" class="form-control rounded-3" maxlength="25" required>
                        </div>
                        <div class="mb-2">
                            <label class="small fw-bold">Nama Lengkap <span class="text-muted">(Maks. 40 Karakter)</span></label>
                            <!-- patternRegex: Hanya menerima huruf besar/kecil dan spasi -->
                            <input type="text" name="nama_lengkap" class="form-control rounded-3" maxlength="40" pattern="[A-Za-z\s]+" title="Nama lengkap hanya boleh diisi huruf dan spasi" required>
                        </div>
                        <div class="mb-2">
                            <label class="small fw-bold">Password <span class="text-muted">(Maks. 15 Karakter)</span></label>
                            <input type="password" name="password" class="form-control rounded-3" maxlength="15" required>
                        </div>
                        <div class="mb-3">
                            <label class="small fw-bold">Jabatan (Role)</label>
                            <select name="id_role" class="form-select rounded-3" required>
                                <option value="0">--Pilih--</option>
                                <option value="1">Owner</option>
                                <option value="2">Admin Toko</option>
                                <option value="3">Bagian Packing</option>
                            </select>
                        </div>
                        <button type="submit" name="tambah_user" class="btn btn-primary w-100 rounded-3 fw-bold">Simpan Akun</button>
                    </form>
                </div>
            </div>

            <div class="col-md-8">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Nama Lengkap</th>
                                <th>Username</th>
                                <th>Role</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($row = mysqli_fetch_assoc($users)): ?>
                            <tr>
                                <td class="fw-semibold"><?= htmlspecialchars($row['nama_lengkap']) ?></td>
                                <td class="text-muted"><?= htmlspecialchars($row['username']) ?></td>
                                <td>
                                    <?php 
                                        $badge_class = 'bg-secondary';
                                        if($row['nama_role'] == 'Owner') $badge_class = 'bg-primary';
                                        if($row['nama_role'] == 'Admin' || $row['nama_role'] == 'Admin Toko') $badge_class = 'bg-success';
                                        if($row['nama_role'] == 'Bagian Packing') $badge_class = 'bg-warning text-dark';
                                    ?>
                                    <span class="badge <?= $badge_class ?> rounded-pill">
                                        <?= htmlspecialchars($row['nama_role']) ?>
                                    </span>
                                </td>
                                <td class="text-center">
                                    <?php if($row['username'] !== $_SESSION['username']): ?>
                                        <a href="index.php?page=kelola_user&hapus=<?= $row['id_user'] ?>" 
                                           class="btn btn-outline-danger btn-sm rounded-3" 
                                           onclick="return confirm('Hapus user ini?')">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                    <?php else: ?>
                                        <span class="badge bg-secondary rounded-pill small">Aktif</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>