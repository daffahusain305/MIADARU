<?php
include __DIR__ . '/../koneksi.php';

// Menangkap parameter id_pembelian
$id_pembelian = isset($_GET['id_pembelian']) ? mysqli_real_escape_string($conn, $_GET['id_pembelian']) : '';

$query = mysqli_query($conn, "SELECT * FROM pembelian_roll WHERE id_pembelian = '$id_pembelian'");
$data = mysqli_fetch_assoc($query);

if (!$data) {
    echo '<div class="alert alert-danger mb-0">Data pembelian tidak ditemukan.</div>';
    exit;
}
?>

<form action="Proses_StokMasuk/proses_edit_pembelian.php" method="POST">
    <input type="hidden" name="id_pembelian" value="<?= $data['id_pembelian'] ?>">

    <div class="mb-3 bg-light p-3 rounded-3">
        <label class="small text-muted d-block mb-1">Informasi Supplier & Nota</label>
        <span class="fw-bold d-block"><?= htmlspecialchars($data['supplier']) ?></span>
        <small class="text-muted">No. Nota: <?= htmlspecialchars($data['no_nota'] ?? '-') ?></small>
    </div>

    <div class="mb-3">
        <label class="small text-muted mb-1">Nama Produk / Barang</label>
        <input type="text" name="nama_produk_manual" class="form-control" value="<?= htmlspecialchars($data['nama_produk_manual'] ?? '') ?>" required>
    </div>

    <div class="row g-2 mb-3">
        <div class="col-md-6">
            <label class="small text-muted mb-1">Jenis Bahan</label>
            <input type="text" name="jenis_bahan_manual" class="form-control" value="<?= htmlspecialchars($data['jenis_bahan_manual'] ?? '') ?>">
        </div>
        <div class="col-md-6">
            <label class="small text-muted mb-1">Warna</label>
            <input type="text" name="warna_manual" class="form-control" value="<?= htmlspecialchars($data['warna_manual'] ?? '') ?>">
        </div>
    </div>

    <div class="row g-2 mb-3">
        <div class="col-md-6">
            <label class="small text-muted mb-1">Jumlah Roll</label>
            <input type="number" name="jumlah_roll" class="form-control border-0 bg-light fw-bold text-primary" value="<?= $data['jumlah_roll'] ?>" min="1" required>
        </div>
        <div class="col-md-6">
            <label class="small text-muted mb-1">Target Size</label>
            <input type="text" name="target_size" class="form-control" value="<?= htmlspecialchars($data['target_size'] ?? '') ?>">
        </div>
    </div>

    <button type="submit" class="btn btn-primary w-100 rounded-pill py-2 fw-bold shadow-sm">
        <i class="bi bi-check-circle-fill me-2"></i> Simpan Perubahan Pembelian
    </button>
</form>