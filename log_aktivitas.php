<?php
include 'koneksi.php';
?>

<div class="container-fluid">
    <!-- Header Halaman -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
       
    </div>

    <!-- Card Data Log -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 bg-primary text-white">
            <h6 class="m-0 font-weight-bold">
                <i class="fas fa-list mr-1"></i> Daftar Catatan Aktivitas Pengguna
            </h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover table-bordered" id="dataTable" width="100%" cellspacing="0">
                    <thead class="thead-light">
                        <tr>
                            <th width="5%" class="text-center">No</th>
                            <th width="15%">Waktu</th>
                            <th width="15%">Pengguna</th>
                            <th width="15%">Role</th>
                            <th width="20%">Aktivitas</th>
                            <th width="30%">Detail Keterangan</th>
                        </tr>
                    </thead>
                    <tbody>
    <?php
    $no = 1;
    
    // Query untuk mengambil data log, user, dan role
    $sql = "SELECT l.*, 
                   IFNULL(u.username, l.username) AS nama_user,
                   IFNULL(r.nama_role, l.role) AS nama_role
            FROM log_aktivitas l
            LEFT JOIN users u ON l.id_user = u.id_user
            LEFT JOIN roles r ON u.id_role = r.id_role
            ORDER BY l.waktu DESC";
            
    $query = mysqli_query($conn, $sql);
    
    while($data = mysqli_fetch_array($query)) {
        
        // Ambil nama role (jika kosong set default 'PENGGUNA')
        $role_text = !empty($data['nama_role']) ? $data['nama_role'] : 'PENGGUNA';
        $role_lower = strtolower($role_text);

        // STYLING WARNA PASTI (BACKGROUND + TEKS)
        if (strpos($role_lower, 'owner') !== false || strpos($role_lower, 'pemilik') !== false) {
            // Owner: Background Biru Tua, Teks Putih
            $badge_style = "background-color: #2e59d9 !important; color: #ffffff !important;";
        } elseif (strpos($role_lower, 'admin') !== false || strpos($role_lower, 'toko') !== false) {
            // Admin Toko: Background Hijau Tua, Teks Putih
            $badge_style = "background-color: #1cc88a !important; color: #ffffff !important;";
        } elseif (strpos($role_lower, 'packing') !== false || strpos($role_lower, 'gudang') !== false) {
            // Bagian Packing: Background Kuning Emas, Teks HITAM PEKAT
            $badge_style = "background-color: #f6c23e !important; color: #111111 !important;";
        } else {
            // Role Lainnya: Background Abu-abu Gelap, Teks Putih
            $badge_style = "background-color: #5a5c69 !important; color: #ffffff !important;";
        }
    ?>
    <tr>
        <td class="text-center align-middle"><?= $no++; ?></td>
        <td class="align-middle">
            <small class="text-muted"><i class="far fa-clock mr-1"></i><?= date('d/m/Y H:i', strtotime($data['waktu'])); ?></small>
        </td>
        <td class="align-middle">
            <strong><i class="fas fa-user-circle text-gray-500 mr-1"></i><?= htmlspecialchars($data['nama_user']); ?></strong>
        </td>
        <td class="align-middle text-center">
            <!-- BADGE DENGAN STYLE IMPERATIF (!important) -->
            <span class="badge px-3 py-2 font-weight-bold" style="<?= $badge_style; ?> font-size: 0.85rem; border-radius: 4px; display: inline-block;">
                <?= strtoupper(htmlspecialchars($role_text)); ?>
            </span>
        </td>
        <td class="align-middle font-weight-bold text-gray-800">
            <?= htmlspecialchars($data['aksi']); ?>
        </td>
        <td class="align-middle text-muted">
            <?= htmlspecialchars($data['keterangan']); ?>
        </td>
    </tr>
    <?php } ?>
</tbody>
                </table>
            </div>
        </div>
    </div>
</div>