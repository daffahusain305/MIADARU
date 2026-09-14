<?php
include __DIR__ . '/../koneksi.php';

$jenis = $_GET['jenis'] ?? 'kain';
$kategori_judul = ($jenis === 'kain') ? "✂️ CACAT KAIN (SOBEK/BOLONG)" : "💧 CACAT NODA (TINTA/KOTOR/MINYAK)";

// Tarik semua data retur aktif (status 'Pending') berdasarkan jenis filternya
$query = mysqli_query($conn, "SELECT * FROM log_retur WHERE keterangan = '$jenis' AND status = 'Pending' ORDER BY tgl_retur DESC");

// Susun teks pembuka pesan WhatsApp
$pesan = "*LAPORAN PRODUK CACAT BARU*\n";
$pesan .= "Kategori: " . $kategori_judul . "\n";
$pesan .= "Tanggal Kirim: " . date('d-m-Y H:i') . "\n";
$pesan .= "-------------------------------------------\n\n";

if (mysqli_num_rows($query) > 0) {
    $no = 1;
    $total_pcs = 0;
    
    while ($d = mysqli_fetch_assoc($query)) {
        $ukuran = strtoupper($d['ukuran']);
        $pesan .= "$no. *{$d['nama_produk']}*\n";
        $pesan .= "    Varian: {$d['nama_warna']}\n";
        $pesan .= "    Size: $ukuran | Qty: *{$d['jumlah_retur']} PCS*\n";
        $pesan .= "    Tgl Masuk: " . date('d-m-Y', strtotime($d['tgl_retur'])) . "\n\n";
        
        $total_pcs += intval($d['jumlah_retur']);
        $no++;
    }
    
    $pesan .= "-------------------------------------------\n";
    $pesan .= "*TOTAL KESELURUHAN: $total_pcs PCS*\n\n";
    $pesan .= "Mohon untuk segera dicek / ditindaklanjuti. Terima kasih.";
} else {
    $pesan .= "Alhamdulillah, saat ini tidak ada data produk cacat aktif untuk kategori ini.";
}

// Encode teks pesan agar valid dimasukkan ke link URL WhatsApp
$pesan_encoded = urlencode($pesan);

// Masukkan nomor WhatsApp admin/tujuan di sini (Gunakan kode negara '62' di depannya)
$nomor_wa = "62895365668157"; 

$wa_url = "https://api.whatsapp.com/send?phone=" . $nomor_wa . "&text=" . $pesan_encoded;

// Alihkan halaman ke aplikasi WhatsApp
header("Location: " . $wa_url);
exit();
?>