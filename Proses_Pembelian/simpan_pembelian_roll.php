<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include __DIR__ . '/../koneksi.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Ambil data user dari Session
    $id_user  = $_SESSION['id_user'] ?? 0;
    $username = $_SESSION['username'] ?? 'System';
    $role     = $_SESSION['role'] ?? 'User';

    $tgl_pembelian       = $_POST['tgl_pembelian'];
    $no_nota             = $_POST['no_nota'] ?? '';
    $supplier            = $_POST['supplier'];
    $id_varian           = !empty($_POST['id_varian']) ? intval($_POST['id_varian']) : NULL;
    $nama_produk_manual  = $_POST['nama_produk_manual'] ?? '';
    $jenis_bahan_manual  = $_POST['jenis_bahan_manual'] ?? '';
    $warna_manual_input  = $_POST['warna_manual'] ?? '';
    $jumlah_roll         = intval($_POST['jumlah_roll']);
    $target_size         = isset($_POST['target_size']) ? implode(',', $_POST['target_size']) : '';

    $aksi = "PEMBELIAN KAIN";

    // A. JIKA PRODUK BARU / MANUAL (Bisa Multi-Warna)
    if (empty($id_varian) && !empty($warna_manual_input)) {
        
        $daftar_warna = array_map('trim', explode(',', $warna_manual_input));

        foreach ($daftar_warna as $warna_tunggal) {
            if (empty($warna_tunggal)) continue;

            $query = "INSERT INTO pembelian_roll 
                      (tgl_pembelian, no_nota, supplier, id_varian, nama_produk_manual, jenis_bahan_manual, warna_manual, jumlah_roll, target_size, is_resolved) 
                      VALUES 
                      ('$tgl_pembelian', '$no_nota', '$supplier', NULL, '$nama_produk_manual', '$jenis_bahan_manual', '$warna_tunggal', '$jumlah_roll', '$target_size', 0)";
            
            if (mysqli_query($conn, $query)) {
                // CATAT LOG AKTIVITAS (PRODUK MANUAL)
                $keterangan = "Mencatat pembelian " . $jumlah_roll . " roll kain " . $nama_produk_manual . " (" . $warna_tunggal . ") dari " . $supplier;
                
                $q_log = "INSERT INTO log_aktivitas (id_user, username, role, aksi, keterangan) 
                          VALUES ('$id_user', '$username', '$role', '$aksi', '$keterangan')";
                mysqli_query($conn, $q_log);
            }
        }

    } else {
        // B. JIKA PRODUK TERDAFTAR (1 Varian Warna)
        $id_varian_val = $id_varian ? "'$id_varian'" : "NULL";
        
        $query = "INSERT INTO pembelian_roll 
                  (tgl_pembelian, no_nota, supplier, id_varian, nama_produk_manual, jenis_bahan_manual, warna_manual, jumlah_roll, target_size, is_resolved) 
                  VALUES 
                  ('$tgl_pembelian', '$no_nota', '$supplier', $id_varian_val, '$nama_produk_manual', '$jenis_bahan_manual', '$warna_manual_input', '$jumlah_roll', '$target_size', 0)";
        
        if (mysqli_query($conn, $query)) {
            // Ambil nama produk & warna dari DB untuk log yang informatif
            $nama_item = "Produk Varian #" . $id_varian;
            if ($id_varian) {
                $q_info = mysqli_query($conn, "SELECT p.nama_produk, v.nama_warna FROM varian_warna v JOIN produk p ON v.id_produk = p.id_produk WHERE v.id_varian = '$id_varian'");
                if ($d_info = mysqli_fetch_assoc($q_info)) {
                    $nama_item = $d_info['nama_produk'] . " (" . $d_info['nama_warna'] . ")";
                }
            }

            // CATAT LOG AKTIVITAS (PRODUK TERDAFTAR)
            $keterangan = "Mencatat pembelian " . $jumlah_roll . " roll kain " . $nama_item . " dari " . $supplier;
            
            $q_log = "INSERT INTO log_aktivitas (id_user, username, role, aksi, keterangan) 
                      VALUES ('$id_user', '$username', '$role', '$aksi', '$keterangan')";
            mysqli_query($conn, $q_log);
        }
    }

    // Redirect kembali ke halaman riwayat pembelian
    header("Location: ../index.php?page=pembelian&tgl_riwayat=" . $tgl_pembelian);
    exit();
}
?>