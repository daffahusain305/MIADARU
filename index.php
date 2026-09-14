<?php 
include 'keamanan/auth_check.php'; // Panggil satpam
include 'koneksi.php'; 

// Ambil role user dari session (Default 'Guest' jika tidak ada)
$user_role = $_SESSION['role'] ?? '';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Miadaru Dashboard</title>
    
    <!-- CSS Bootstrap & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="./CSS/index.css">

    <!-- Bootstrap JS dipindahkan ke Head dengan 'defer' agar modal JS dapat diakses langsung oleh modul include -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" defer></script>
</head>
<body>

<?php
// Inisialisasi daftar menu untuk judul dinamis
$page = isset($_GET['page']) ? $_GET['page'] : 'bi';
$menus_data = [
    'bi' => ['Dashboard '],
    'katalog' => ['Katalog Produk'],
    'infostok' => ['Informasi Stok'],
    'pembelian' => ['Order Pembelian'],
    'StokMasuk' => ['Pencatatan Stok Masuk'],
    'Velocity' => ['Stock Velocity'],
    'retur' => ['Retur Barang'],
    'rekap' => ['Rekap penjualan'],
    'riwayat' => ['Riwayat & Adjustment'],
    'log_aktifitas' => ['Log Aktivitas'],
    'kelola_user' => ['Kelola User']
];

$current_title = isset($menus_data[$page]) ? $menus_data[$page][0] : 'Dashboard';
$current_sub = '';

// Helper function untuk pesan Akses Ditolak
function tampilAksesDitolak($role_diperlukan) {
    echo "
    <div class='alert alert-danger rounded-4 border-0 shadow-sm p-4'>
        <i class='bi bi-shield-lock-fill fs-3 mb-3 d-block'></i>
        <h4 class='fw-bold'>Akses Ditolak!</h4>
        <p class='mb-0'>Maaf, halaman ini hanya dapat diakses oleh: <strong>" . htmlspecialchars($role_diperlukan) . "</strong>.</p>
    </div>";
}
?>

<!-- Navbar Mobile -->
<nav class="navbar navbar-expand-lg navbar-light bg-white d-lg-none border-bottom sticky-top">
    <div class="container-fluid">
        <button class="navbar-toggler border-0" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasSidebar">
            <span class="navbar-toggler-icon"></span>
        </button>
        <span class="navbar-brand fw-bold ms-2">Miadaru</span>
    </div>
</nav>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar Desktop -->
        <nav id="sidebarMenu" class="col-lg-2 d-none d-lg-block position-fixed">
            <div class="pt-4 px-3">
                <div class="d-flex align-items-center mb-4">
                    <div class="bg-danger text-white rounded p-2 me-2 fw-bold" style="width: 35px; height: 35px; display: flex; justify-content: center; align-items: center;">M</div>
                    <div>
                        <h6 class="mb-0 fw-bold">Miadaru</h6>
                        <small class="text-muted" style="font-size: 11px;">Pojok Hijab</small>
                    </div>
                </div>
                <?php include 'layout/menu_list.php'; ?>
            </div>
        </nav>

        <!-- Sidebar Mobile / Offcanvas -->
        <div class="offcanvas offcanvas-start" tabindex="-1" id="offcanvasSidebar" style="width: 280px;">
            <div class="offcanvas-header border-bottom">
                <h5 class="offcanvas-title fw-bold">Menu Navigasi</h5>
                <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
            </div>
            <div class="offcanvas-body">
                <?php include 'layout/menu_list.php'; ?>
            </div>
        </div>

        <!-- Area Konten Utama -->
        <main class="col-lg-10 ms-sm-auto px-md-4 py-4 main-content">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="fw-bold mb-0 text-dark"><?= $current_title ?></h4>
                    <small class="text-muted"><?= $current_sub ?></small>
                </div>
            </div>
            <hr class="mt-3 mb-4" style="border-top: 2px solid #343a40; opacity: 1;">

            <div class="content-wrapper">
                <?php 
                    switch ($page) {
                        case 'bi':
                            if(file_exists('bi_dashboard.php')) {
                                include 'bi_dashboard.php';
                            } else {
                                $nama_user = $_SESSION['nama'] ?? 'User';
                                echo "<div class='card p-4 rounded-4 border-0 shadow-sm'><h3>Selamat Datang, " . htmlspecialchars($nama_user) . "</h3></div>";
                            }
                            break;

                        case 'katalog': 
                            include 'Produk.php'; 
                            break;

                        case 'infostok': 
                        
                            if (in_array($user_role, ['Owner', 'Admin Toko'])) {
                             include 'infostok.php';
                            } else {
                                tampilAksesDitolak('Owner atau Admin Toko');
                            }
                            break;
                            
                        case 'pembelian': 
                            if (in_array($user_role, ['Owner', 'Admin Toko'])) {
                                include 'pembelian.php';
                            } else {
                                tampilAksesDitolak('Owner atau Admin Toko');
                            }
                            break;    

                        case 'Velocity': 
                            if (in_array($user_role, ['Owner', 'Admin Toko'])) {
                                include 'StockVelocity.php'; 
                            } else {
                                tampilAksesDitolak('Owner atau Admin Toko');
                            }
                            break;

                        case 'StokMasuk': 
                            if (in_array($user_role, ['Owner', 'Bagian Packing'])) {
                                include 'StokMasuk.php'; 
                            } else {
                                tampilAksesDitolak('Owner atau Bagian Packing');
                            }
                            break;

                        case 'retur': 
                            if (in_array($user_role, ['Owner', 'Bagian Packing'])) {
                                include 'ReturBarang.php';
                            } else {
                                tampilAksesDitolak('Owner atau Bagian Packing');
                            }
                            break;

                         case 'rekap': 
                            if (in_array($user_role, ['Owner', 'Admin Toko'])) {
                                include 'RekapPenjualan.php';
                            } else {
                                tampilAksesDitolak('Owner atau Admin Toko');
                            }
                            break;

                        case 'sales': 
                            if (in_array($user_role, ['Owner', 'Admin Toko'])) {
                                include 'StokSalesCenter.php';
                            } else {
                                tampilAksesDitolak('Owner atau Admin Toko');
                            }
                            break;

                        case 'riwayat': 
                            if (in_array($user_role, ['Owner', 'Bagian Packing'])) {
                                include 'adjustment.php';
                            } else {
                                tampilAksesDitolak('Owner atau Bagian Packing');
                            }
                            break;

                        case 'log_aktifitas': 
                            if ($user_role === 'Owner') {
                                include 'log_aktivitas.php';
                            } else {
                                tampilAksesDitolak('Owner');
                            }
                            break;   

                        case 'kelola_user':
                            if ($user_role === 'Owner') {
                                include 'keamanan/kelola_user.php'; 
                            } else {
                                tampilAksesDitolak('Owner');
                            }
                            break;  

                        default: 
                            if(file_exists('bi_dashboard.php')) {
                                include 'bi_dashboard.php';
                            } else {
                                $nama_user = $_SESSION['nama'] ?? 'User';
                                echo "<div class='card p-4 rounded-4 border-0 shadow-sm'><h3>Selamat Datang, " . htmlspecialchars($nama_user) . "</h3></div>";
                            }
                            break;
                    }
                ?>
            </div>
        </main>
    </div>
</div>

</body>
</html>