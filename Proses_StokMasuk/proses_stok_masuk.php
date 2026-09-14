<?php
session_start(); // WAJIB: Agar $_SESSION terbaca
include __DIR__ . '/../koneksi.php';
include __DIR__ . '/../proses/fungsi.php'; // Panggil file fungsi log

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // 1. Tangkap & Validasi Input
    $id_varian    = isset($_POST['id_varian']) ? mysqli_real_escape_string($conn, $_POST['id_varian']) : '';
    $kolom_ukuran = isset($_POST['ukuran_kolom']) ? mysqli_real_escape_string($conn, $_POST['ukuran_kolom']) : ''; 
    $jumlah       = isset($_POST['jumlah']) ? (int)$_POST['jumlah'] : 0;
    
    $barang_lebih  = isset($_POST['barang_lebih']) ? (int)$_POST['barang_lebih'] : 0;
    $barang_kurang = isset($_POST['barang_kurang']) ? (int)$_POST['barang_kurang'] : 0;

    // CEK KEAMANAN: Cegah Fatal Error Foreign Key jika id_varian kosong/tidak valid
    if (empty($id_varian) || empty($kolom_ukuran) || $jumlah <= 0) {
        echo "<script>alert('Gagal: Produk, Varian Warna, atau Ukuran belum dipilih dengan benar!'); window.history.back();</script>";
        exit();
    }

    // Ambil nama ukuran saja (misal stok_s jadi S)
    $nama_ukuran = strtoupper(str_replace('stok_', '', $kolom_ukuran));

    // --- LANGKAH 1: AMBIL DETAIL PRODUK ---
    $res_detail = mysqli_query($conn, "SELECT p.nama_produk, v.nama_warna FROM varian_warna v 
                                       JOIN produk p ON p.id_produk = v.id_produk 
                                       WHERE v.id_varian = '$id_varian'");
    
    // Validasi apakah Varian benar-benar ada di Database
    if (!$res_detail || mysqli_num_rows($res_detail) == 0) {
        echo "<script>alert('Error: Data varian tidak ditemukan di database!'); window.history.back();</script>";
        exit();
    }

    $detail = mysqli_fetch_assoc($res_detail);
    $nama_p = $detail['nama_produk'];
    $warna  = $detail['nama_warna'];

    // --- LANGKAH 2: UPDATE STOK UTAMA (MURNI BERDASARKAN QTY NOTA) ---
    $sql_update = "UPDATE varian_warna SET $kolom_ukuran = $kolom_ukuran + $jumlah WHERE id_varian = '$id_varian'";
    
    // --- LANGKAH 3: CATAT KE TABEL STOK MASUK ---
    $sql_log_masuk = "INSERT INTO log_stok_masuk (id_varian, jumlah_masuk, barang_lebih, barang_kurang, tgl_masuk, ukuran) 
                      VALUES ('$id_varian', '$jumlah', '$barang_lebih', '$barang_kurang', NOW(), '$nama_ukuran')";

    if (mysqli_query($conn, $sql_update) && mysqli_query($conn, $sql_log_masuk)) {
        
        // --- LANGKAH 4: CATAT KE LOG AKTIVITAS (AUDIT TRAIL) ---
        $id_user  = isset($_SESSION['id_user']) ? $_SESSION['id_user'] : 0;
        $username = isset($_SESSION['username']) ? $_SESSION['username'] : 'System';
        $role     = isset($_SESSION['role']) ? $_SESSION['role'] : 'Admin';
        $aksi     = "STOK MASUK";
        
        $keterangan = "Menambah stok $nama_p ($warna) ukuran $nama_ukuran sebanyak $jumlah pcs (Sesuai Nota).";
        if ($barang_lebih > 0) {
            $keterangan .= " Catatan: Kelebihan fisik konveksi sebanyak $barang_lebih pcs.";
        }
        if ($barang_kurang > 0) {
            $keterangan .= " Catatan: Kekurangan fisik konveksi sebanyak $barang_kurang pcs.";
        }

        catatLog($conn, $id_user, $username, $role, $aksi, $keterangan);

        echo "<script>alert('Stok $nama_ukuran berhasil ditambahkan sebanyak $jumlah pcs!'); window.location='../index.php?page=StokMasuk';</script>";
    } else {
        echo "Error Database: " . mysqli_error($conn);
    }
}
?>