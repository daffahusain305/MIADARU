<?php
include __DIR__ . '/../koneksi.php';

$aksi = $_GET['aksi'] ?? '';
$url_kembali = "../index.php?page=retur";

if ($aksi === 'tambah') {
    $id_varian    = mysqli_real_escape_string($conn, $_POST['id_varian']);
    
    // Normalisasi nama ukuran
    $ukuran_raw   = mysqli_real_escape_string($conn, $_POST['ukuran']);
    $ukuran_clean = strtolower(str_replace('stok_', '', trim($ukuran_raw))); 
    
    $jumlah_retur = intval($_POST['jumlah_retur']);
    $keterangan   = mysqli_real_escape_string($conn, $_POST['keterangan']);
    $nama_kolom_stok = "stok_" . $ukuran_clean;

    // 1. Ambil info varian & stok saat ini
    $v_info = mysqli_query($conn, "SELECT p.nama_produk, v.nama_warna, v.$nama_kolom_stok FROM varian_warna v JOIN produk p ON v.id_produk = p.id_produk WHERE v.id_varian = '$id_varian'");
    
    if (mysqli_num_rows($v_info) > 0) {
        $data_v      = mysqli_fetch_assoc($v_info);
        $nama_produk = mysqli_real_escape_string($conn, $data_v['nama_produk']);
        $nama_warna  = mysqli_real_escape_string($conn, $data_v['nama_warna']);
        $stok_ada    = intval($data_v[$nama_kolom_stok]);

        // Cek kecukupan stok
        if ($stok_ada < $jumlah_retur) {
            echo "<script>alert('Gagal! Stok tersedia hanya $stok_ada Pcs, tidak cukup untuk retur $jumlah_retur Pcs.'); window.location='$url_kembali';</script>";
            exit();
        }

        // 2. Simpan Catatan Retur ke log_retur
        $insert = mysqli_query($conn, "INSERT INTO log_retur (id_varian, nama_produk, nama_warna, ukuran, jumlah_retur, keterangan, status) 
                                       VALUES ('$id_varian', '$nama_produk', '$nama_warna', '$ukuran_clean', '$jumlah_retur', '$keterangan', 'Pending')");
        
        if ($insert) {
            // 3. Potong stok di varian_warna
            $update_stok = mysqli_query($conn, "UPDATE varian_warna SET $nama_kolom_stok = $nama_kolom_stok - $jumlah_retur WHERE id_varian = '$id_varian'");

            if ($update_stok) {
                echo "<script>alert('Data retur cacat berhasil dicatat dan stok berhasil dipotong sebanyak $jumlah_retur Pcs!'); window.location='$url_kembali';</script>";
            } else {
                echo "<script>alert('Log retur tercatat, tapi gagal memotong stok di database!'); window.location='$url_kembali';</script>";
            }
        } else {
            echo "<script>alert('Gagal menyimpan catatan retur!'); window.location='$url_kembali';</script>";
        }
    } else {
        echo "<script>alert('Varian produk tidak ditemukan!'); window.location='$url_kembali';</script>";
    }

} elseif ($aksi === 'edit') {
    $id_retur          = mysqli_real_escape_string($conn, $_POST['id_retur']);
    $jumlah_retur_baru = intval($_POST['jumlah_retur_baru']);
    $keterangan_baru   = mysqli_real_escape_string($conn, $_POST['keterangan_baru']);
    $submit_type       = $_POST['submit_type'] ?? 'simpan'; // Penentu tombol mana yang diklik

    // Ambil data retur lama sebelum di-update
    $q_lama = mysqli_query($conn, "SELECT id_varian, ukuran, jumlah_retur FROM log_retur WHERE id_retur = '$id_retur'");
    
    if (mysqli_num_rows($q_lama) > 0) {
        $data_lama         = mysqli_fetch_assoc($q_lama);
        $id_varian         = $data_lama['id_varian'];
        $jumlah_retur_lama = intval($data_lama['jumlah_retur']);
        $ukuran_clean      = strtolower(str_replace('stok_', '', trim($data_lama['ukuran'])));
        $nama_kolom_stok   = "stok_" . $ukuran_clean;

        // --- SKENARIO 1: TOMBOL "SIMPAN PERUBAHAN" DIKLIK ---
        if ($submit_type === 'simpan') {
            $selisih = $jumlah_retur_baru - $jumlah_retur_lama;

            if ($selisih != 0) {
                $q_stok = mysqli_query($conn, "SELECT $nama_kolom_stok FROM varian_warna WHERE id_varian = '$id_varian'");
                $d_stok = mysqli_fetch_assoc($q_stok);
                $stok_saat_ini = intval($d_stok[$nama_kolom_stok]);

                if ($selisih > 0 && $stok_saat_ini < $selisih) {
                    echo "<script>alert('Gagal mengedit! Stok tidak cukup untuk menambah retur sebanyak $selisih Pcs lagi.'); window.location='$url_kembali';</script>";
                    exit();
                }

                // Update penyesuaian pemotongan stok
                mysqli_query($conn, "UPDATE varian_warna SET $nama_kolom_stok = $nama_kolom_stok - ($selisih) WHERE id_varian = '$id_varian'");
            }

            // Update log_retur
            $update = mysqli_query($conn, "UPDATE log_retur SET jumlah_retur = '$jumlah_retur_baru', keterangan = '$keterangan_baru' WHERE id_retur = '$id_retur'");

            if ($update) {
                echo "<script>alert('Perubahan data retur dan penyesuaian stok berhasil disimpan!'); window.location='$url_kembali';</script>";
            } else {
                echo "<script>alert('Gagal mengubah data retur!'); window.location='$url_kembali';</script>";
            }

        // --- SKENARIO 2: TOMBOL "SELESAIKAN" DIKLIK ---
        } elseif ($submit_type === 'selesaikan') {
            $stok_kembali = 0;

            if (strtolower($keterangan_baru) === 'noda') {
                $noda_bersih = intval($_POST['cacat_noda_bersih'] ?? 0);

                if ($noda_bersih > $jumlah_retur_baru) {
                    echo "<script>alert('Gagal! Jumlah noda bersih ($noda_bersih Pcs) tidak boleh melebihi Total Cacat ($jumlah_retur_baru Pcs).'); window.location='$url_kembali';</script>";
                    exit();
                }

                $stok_kembali = $noda_bersih;
                $sisa_rusak   = $jumlah_retur_baru - $noda_bersih;
                $pesan_stok   = "Stok sebanyak $stok_kembali Pcs berhasil dipulihkan ke gudang. Sisa $sisa_rusak Pcs hangus/cacat permanen.";
            } else {
                // Jika Cacat Kain
                $pesan_stok   = "Cacat kain diselesaikan. Stok tidak ada yang dikembalikan ke gudang.";
            }

            // Memasukkan barang yang bersih kembali ke stok gudang (jika ada)
            if ($stok_kembali > 0) {
                mysqli_query($conn, "UPDATE varian_warna SET $nama_kolom_stok = $nama_kolom_stok + $stok_kembali WHERE id_varian = '$id_varian'");
            }

            // Ubah status retur menjadi Resolved
            $update = mysqli_query($conn, "UPDATE log_retur SET jumlah_retur = '$jumlah_retur_baru', keterangan = '$keterangan_baru', status = 'Resolved' WHERE id_retur = '$id_retur'");

            if ($update) {
                echo "<script>alert('Retur berhasil diselesaikan! $pesan_stok'); window.location='$url_kembali';</script>";
            } else {
                echo "<script>alert('Gagal menyelesaikan retur!'); window.location='$url_kembali';</script>";
            }
        }

    } else {
        echo "<script>alert('Data retur tidak ditemukan!'); window.location='$url_kembali';</script>";
    }

} elseif ($aksi === 'resolved') {
    $id_retur = mysqli_real_escape_string($conn, $_GET['id']);

    $q_retur = mysqli_query($conn, "SELECT id_varian, ukuran, jumlah_retur, keterangan, status FROM log_retur WHERE id_retur = '$id_retur'");
    if (mysqli_num_rows($q_retur) > 0) {
        $r = mysqli_fetch_assoc($q_retur);
        
        if ($r['status'] === 'Pending') {
            $id_varian    = $r['id_varian'];
            $ukuran_clean = strtolower(str_replace('stok_', '', trim($r['ukuran'])));
            $ukuran_kolom = "stok_" . $ukuran_clean;
            $qty_pulih    = intval($r['jumlah_retur']);
            $jenis_cacat  = strtolower($r['keterangan']);

            if ($jenis_cacat === 'noda') {
                mysqli_query($conn, "UPDATE varian_warna SET $ukuran_kolom = $ukuran_kolom + $qty_pulih WHERE id_varian = '$id_varian'");
                $pesan_stok = "Barang cacat noda telah selesai ditangani. Stok sebanyak $qty_pulih Pcs dikembalikan ke gudang.";
            } else {
                $pesan_stok = "Barang cacat kain diselesaikan. Stok tetap terpotong (tidak bertambah).";
            }

            $update_status = mysqli_query($conn, "UPDATE log_retur SET status = 'Resolved' WHERE id_retur = '$id_retur'");

            if ($update_status) {
                echo "<script>alert('Status berhasil diubah ke Resolved! $pesan_stok'); window.location='$url_kembali';</script>";
            } else {
                echo "<script>alert('Gagal memperbarui status resolved!'); window.location='$url_kembali';</script>";
            }
        } else {
            echo "<script>alert('Data ini sudah berstatus Resolved sebelumnya.'); window.location='$url_kembali';</script>";
        }
    } else {
        echo "<script>alert('Data tidak valid!'); window.location='$url_kembali';</script>";
    }

} elseif ($aksi === 'hapus') {
    $id_retur = mysqli_real_escape_string($conn, $_GET['id']);
    
    $q_get = mysqli_query($conn, "SELECT id_varian, ukuran, jumlah_retur, status FROM log_retur WHERE id_retur = '$id_retur'");
    if (mysqli_num_rows($q_get) > 0) {
        $r = mysqli_fetch_assoc($q_get);
        
        if ($r['status'] === 'Pending') {
            $id_varian    = $r['id_varian'];
            $ukuran_clean = strtolower(str_replace('stok_', '', trim($r['ukuran'])));
            $ukuran_kolom = "stok_" . $ukuran_clean;
            $qty          = intval($r['jumlah_retur']);

            mysqli_query($conn, "UPDATE varian_warna SET $ukuran_kolom = $ukuran_kolom + $qty WHERE id_varian = '$id_varian'");
        }

        $delete = mysqli_query($conn, "DELETE FROM log_retur WHERE id_retur = '$id_retur'");

        if ($delete) {
            echo "<script>alert('Catatan cacat berhasil dihapus dan stok dikembalikan!'); window.location='$url_kembali';</script>";
        } else {
            echo "<script>alert('Gagal menghapus data!'); window.location='$url_kembali';</script>";
        }
    }
}
?>