<ul class="nav flex-column gap-1">
    <?php
    $current_page = isset($_GET['page']) ? $_GET['page'] : 'bi';
    $role = $_SESSION['role'] ?? '';
    
    // Format: 'key' => ['Label', 'Icon', [Daftar Role yang Diizinkan Access]]
    // Format: 'key' => ['Label', 'Icon', [Daftar Role yang Diizinkan Access]]
    $menus = [
        'bi'            => ['Dashboard', 'grid-fill', ['Owner', 'Admin Toko', 'Bagian Packing']],
        'katalog'       => ['Katalog', 'box-seam', ['Owner', 'Admin Toko', 'Bagian Packing']],
        'infostok'      => ['Stok Produk', 'database-fill-check', ['Owner', 'Admin Toko']],
        'pembelian'     => ['Pembelian Produk', 'bag-check-fill', ['Owner', 'Admin Toko']],
        'StokMasuk'     => ['Penerimaan Barang', 'box-arrow-in-down', ['Owner', 'Bagian Packing']],
        'Velocity'      => ['Mutasi Stok Internal', 'graph-up-arrow', ['Owner', 'Admin Toko']],
        'retur'         => ['Manajemen Retur', 'arrow-return-left', ['Owner', 'Bagian Packing']],
        'rekap'         => ['Rekap Penjualan', 'receipt-cutoff', ['Owner', 'Admin Toko']],
        'sales'         => ['Stok Sales Center', 'shop-window', ['Owner', 'Admin Toko']],
        'riwayat'       => ['Penyesuaian Barang', 'sliders', ['Owner', 'Bagian Packing']],
        'log_aktifitas' => ['Riwayat Aktifitas', 'journal-text', ['Owner']],
        'kelola_user'   => ['Kelola Karyawan', 'people-fill', ['Owner']]
    
    ];

    foreach ($menus as $key => $val): 
        $role_diizinkan = $val[2];

        // FILTER LOGIC: Jika role user tidak ada di dalam daftar role yang diizinkan, lewati (sembunyikan menu)
        if (!in_array($role, $role_diizinkan)) {
            continue;
        }
    ?>
        <li class="nav-item">
            <a class="nav-link <?= $current_page == $key ? 'active-menu' : '' ?>" href="index.php?page=<?= $key ?>">
                <i class="bi bi-<?= $val[1] ?> me-2"></i> <?= $val[0] ?>
            </a>
        </li>
    <?php endforeach; ?>

    <hr>
    <li class="nav-item">
        <a class="nav-link text-danger fw-bold" href="keamanan/logout.php" onclick="return confirm('Yakin ingin keluar?')">
            <i class="bi bi-box-arrow-right me-2"></i> Keluar
        </a>
    </li>
</ul>