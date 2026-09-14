<?php
include __DIR__ . '/../koneksi.php';

$id_log = isset($_GET['id_log']) ? mysqli_real_escape_string($conn, $_GET['id_log']) : '';

if ($id_log == '') {
    // Jika diklik dari tombol shortcut "Edit Input Terakhir", cari ID log paling baru
    $q_last = mysqli_query($conn, "SELECT id_log_masuk FROM log_stok_masuk ORDER BY tgl_masuk DESC LIMIT 1");
    if (mysqli_num_rows($q_last) > 0) {
        $last = mysqli_fetch_assoc($q_last);
        $id_log = $last['id_log_masuk'];
    } else {
        echo "<div class='alert alert-warning small'>Belum ada riwayat transaksi untuk diedit.</div>";
        exit();
    }
}

// Ambil data detail log
$query = mysqli_query($conn, "SELECT l.*, p.nama_produk, v.nama_warna FROM log_stok_masuk l
                              JOIN varian_warna v ON l.id_varian = v.id_varian
                              JOIN produk p ON v.id_produk = p.id_produk
                              WHERE l.id_log_masuk = '$id_log'");
$data = mysqli_fetch_assoc($query);
?>

<form action="Proses_StokMasuk/proses_edit_selisih.php" method="POST">
    <input type="hidden" name="id_log_masuk" value="<?= $data['id_log_masuk'] ?>">

    <div class="mb-3 bg-light p-3 rounded-3">
        <label class="small text-muted d-block mb-1">Informasi Produk (Terkunci)</label>
        <span class="fw-bold d-block"><?= $data['nama_produk'] ?></span>
        <small class="text-muted"><?= $data['nama_warna'] ?> | Ukuran: <?= strtoupper($data['ukuran']) ?></small>
    </div>

    <div class="mb-3">
        <label class="small text-muted mb-1">Jumlah Sesuai Nota (Terkunci)</label>
        <input type="number" class="form-control border-0 bg-light fw-bold text-muted" value="<?= $data['jumlah_masuk'] ?>" readonly disabled>
    </div>

    <!-- Hanya Bagian Selisih Ini Yang Dapat Diedit -->
    <div class="row mb-3">
        <div class="col-6">
            <label class="small text-success mb-1 fw-bold"><i class="bi bi-plus-circle-fill me-1"></i> Barang Lebih</label>
            <input type="number" name="barang_lebih" class="form-control border-0 bg-light text-success fw-bold" min="0" value="<?= $data['barang_lebih'] ?>" required>
        </div>
        <div class="col-6">
            <label class="small text-danger mb-1 fw-bold"><i class="bi bi-dash-circle-fill me-1"></i> Barang Kurang</label>
            <input type="number" name="barang_kurang" class="form-control border-0 bg-light text-danger fw-bold" min="0" value="<?= $data['barang_kurang'] ?>" required>
        </div>
    </div>

    <!-- Tombol Bersebelahan -->
    <div class="d-flex gap-2">
        <button type="submit" name="aksi" value="simpan" class="btn btn-warning w-50 rounded-pill py-2 fw-bold text-dark shadow-sm">
            <i class="bi bi-check-circle-fill me-1"></i> Simpan
        </button>
        <button type="submit" name="aksi" value="selesaikan" class="btn btn-success w-50 rounded-pill py-2 fw-bold text-white shadow-sm" onclick="return confirm('Selesaikan selisih ini? Jika ada Barang Lebih, stok akan otomatis bertambah.');">
            <i class="bi bi-patch-check-fill me-1"></i> Selesaikan
        </button>
    </div>
</form>