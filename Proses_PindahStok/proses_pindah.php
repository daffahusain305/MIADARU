<?php
// 1. WAJIB: Start session di baris paling atas agar data $_SESSION terbaca
session_start(); 

// 2. Hubungkan ke database dan file fungsi log
// Pastikan letak file koneksi.php dan fungsi.php sudah benar (naik satu folder)
include __DIR__ . '/../koneksi.php';
include __DIR__ . '/../proses/fungsi.php';

// Cek keamanan: Jika belum login, tendang ke halaman login
if (!isset($_SESSION['username'])) {
    header("Location: ../login.php");
    exit();
}

// Cek apakah ada data yang dikirim melalui POST
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id_varian'])) {
    
    // Ambil data array dari form (karena bisa input banyak baris sekaligus)
    $id_varians = $_POST['id_varian'];
    $ukurans    = $_POST['ukuran'];
    $jumlahs    = $_POST['jumlah'];

    $success_count = 0;

    // Ambil data identitas user dari session untuk pengisian tabel log_aktivitas
    $id_user  = $_SESSION['id_user'];
    $username = $_SESSION['username'];
    $role     = $_SESSION['role'];

    // Lakukan perulangan (Looping) untuk memproses setiap baris barang
    for ($i = 0; $i < count($id_varians); $i++) {
        
        // Bersihkan input untuk keamanan SQL Injection
        $id_v   = mysqli_real_escape_string($conn, $id_varians[$i]);
        $uk     = mysqli_real_escape_string($conn, $ukurans[$i]);
        $qty    = mysqli_real_escape_string($conn, $jumlahs[$i]);

        // Jika data penting kosong, lewati baris ini (continue)
        if (empty($id_v) || empty($qty)) continue;

        // --- LANGKAH 1: AMBIL DETAIL PRODUK ---
        // Digunakan agar keterangan di Log Aktivitas lebih informatif
        $res_detail = mysqli_query($conn, "SELECT p.nama_produk, v.nama_warna FROM varian_warna v 
                                           JOIN produk p ON p.id_produk = v.id_produk 
                                           WHERE v.id_varian = '$id_v'");
        $detail = mysqli_fetch_assoc($res_detail);
        $nama_p = $detail['nama_produk'];
        $warna  = $detail['nama_warna'];

        // --- LANGKAH 2: OTOMASI MUTASI (UPDATE STOK GUDANG) ---
        // Mengurangi stok secara otomatis berdasarkan kolom ukuran yang dipilih
        $kolom_stok = "stok_" . strtolower($uk);
        $sql_update = "UPDATE varian_warna SET $kolom_stok = $kolom_stok - $qty WHERE id_varian = '$id_v'";
        
        // --- LANGKAH 3: CATAT DISTRIBUSI (UNTUK VELOCITY) ---
        // Data ini khusus untuk kebutuhan grafik Fastest/Cold Moving di dashboard
        $sql_distribusi = "INSERT INTO log_distribusi (id_varian, nama_produk, nama_warna, ukuran, jumlah) 
                           VALUES ('$id_v', '$nama_p', '$warna', '$uk', '$qty')";

        // Eksekusi Update Stok dan simpan Log Distribusi
        if (mysqli_query($conn, $sql_update) && mysqli_query($conn, $sql_distribusi)) {
            
            // --- LANGKAH 4: CATAT KE LOG AKTIVITAS (AUDIT TRAIL) ---
            // Inilah bagian RBAC untuk memantau siapa yang memindahkan stok
            $aksi = "MUTASI STOK";
            $keterangan = "Memindahkan $qty pcs $nama_p ($warna) ukuran ".strtoupper($uk)." ke Sales Center.";
            
            // Memanggil fungsi catatLog yang ada di proses/fungsi.php
            catatLog($conn, $id_user, $username, $role, $aksi, $keterangan);
            
            $success_count++;
        }
    }

    // Berikan notifikasi hasil proses ke user
    if ($success_count > 0) {
        echo "<script>alert('Berhasil memindahkan $success_count item ke Sales Center!'); window.location='../index.php?page=Velocity';</script>";
    } else {
        echo "<script>alert('Gagal memproses pemindahan.'); window.location='../index.php?page=Velocity';</script>";
    }

} else {
    // Jika mencoba akses file ini secara ilegal tanpa POST
    header("Location: ../index.php?page=Velocity");
}
?>