<?php
include __DIR__ . '/../koneksi.php';

$id_log = isset($_GET['id_log']) ? mysqli_real_escape_string($conn, $_GET['id_log']) : '';

$query = mysqli_query($conn, "SELECT l.*, p.nama_produk, v.nama_warna FROM log_stok_masuk l
                              JOIN varian_warna v ON l.id_varian = v.id_varian
                              JOIN produk p ON v.id_produk = p.id_produk
                              WHERE l.id_log_masuk = '$id_log'");
$data = mysqli_fetch_assoc($query);
?>

<form action="proses/proses_edit_nota_utama.php" method="POST">
    <input type="hidden" name="id_log_masuk" value="<?= $data['id_log_masuk'] ?>">

    <div class="mb-3 bg-light p-3 rounded-3">
        <label class="small text-muted d-block mb-1">Informasi Produk</label>
        <span class="fw-bold d-block"><?= $data['nama_produk'] ?></span>
        <small class="text-muted"><?= $data['nama_warna'] ?> | Ukuran: <?= strtoupper($data['ukuran']) ?></small>
    </div>

    <!-- Kolom QTY NOTA bisa diedit disini -->
    <div class="mb-3">
        <label class="small text-muted mb-1">Jumlah Sesuai Nota (QTY NOTA)</label>
        <input type="number" name="jumlah_masuk" class="form-control border-0 bg-light fw-bold text-primary" value="<?= $data['jumlah_masuk'] ?>" min="1" required>
    </div>

    <div class="row mb-3 d-none">
        <!-- Disembunyikan agar admin fokus mengubah QTY NOTA saja -->
        <input type="hidden" name="barang_lebih" value="<?= $data['barang_lebih'] ?>">
        <input type="hidden" name="barang_kurang" value="<?= $data['barang_kurang'] ?>">
    </div>

    <button type="submit" class="btn btn-primary w-100 rounded-pill py-2 fw-bold shadow-sm">
        <i class="bi bi-check-circle-fill me-2"></i> Simpan Perubahan Nota
    </button>
</form>