<?php
include __DIR__ . '/../koneksi.php';

// Query mengambil data dari tabel pembelian_roll JOIN varian & produk
$sql = "SELECT 
            pr.*,
            v.nama_warna,
            p.nama_produk
        FROM pembelian_roll pr
        LEFT JOIN varian_warna v ON pr.id_varian = v.id_varian
        LEFT JOIN produk p ON v.id_produk = p.id_produk
        WHERE pr.is_resolved = 0 OR pr.is_resolved IS NULL
        ORDER BY pr.id_pembelian DESC"; 

$query = mysqli_query($conn, $sql);

if (!$query) {
    echo '<div class="alert alert-danger rounded-3">Error Query: ' . mysqli_error($conn) . '</div>';
    exit;
}

if (mysqli_num_rows($query) == 0) {
    echo '<div class="alert alert-light text-center py-4 rounded-3 border">Belum ada data riwayat pembelian.</div>';
    exit;
}
?>

<!-- Wadah Tabel Bergaya Card Modern dengan Scroll Internal -->
<div class="bg-light p-3 rounded-4 border">
    <!-- max-height: 360px membatasi tampilan sekitar 5 baris data; overflow-y: auto mengaktifkan scroll -->
    <div class="table-responsive bg-white rounded-3 p-2 shadow-sm" style="max-height: 360px; overflow-y: auto;">
        <table class="table table-hover align-middle mb-0">
            <!-- sticky-top agar header tabel tidak ikut ter-scroll -->
            <thead class="bg-white sticky-top shadow-sm" style="z-index: 1;">
                <tr class="text-secondary border-bottom small fw-bold bg-white">
                    <th width="5%" class="py-3 bg-white">No</th>
                    <th class="py-3 bg-white">Tanggal</th>
                    <th class="py-3 bg-white">Supplier / No. Nota</th>
                    <th class="py-3 bg-white">Produk & Bahan</th>
                    <th class="py-3 bg-white">Warna</th>
                    <th class="py-3 bg-white">Jumlah Roll</th>
                    <th class="py-3 bg-white">Target Size</th>
                    <th width="12%" class="text-center py-3 bg-white">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $no = 1;
                while ($row = mysqli_fetch_assoc($query)): 
                    $idPembelian = $row['id_pembelian'];
                    $isResolved  = $row['is_resolved'];

                    // Fallback data manual vs data JOIN
                    $namaProduk = !empty($row['nama_produk_manual']) ? $row['nama_produk_manual'] : ($row['nama_produk'] ?? '-');
                    $warna      = !empty($row['warna_manual']) ? $row['warna_manual'] : ($row['nama_warna'] ?? '-');
                ?>
                    <tr class="border-bottom">
                        <td class="fw-bold text-muted small"><?= $no++; ?></td>
                        <td class="small">
                            <span class="fw-bold text-dark d-block"><?= date('d M Y', strtotime($row['tgl_pembelian'])); ?></span>
                        </td>
                        <td class="small">
                            <strong class="text-dark"><?= htmlspecialchars($row['supplier']); ?></strong>
                            <div class="text-muted small">Nota: <?= htmlspecialchars($row['no_nota'] ?? '-'); ?></div>
                        </td>
                        <td class="small">
                            <strong class="text-dark"><?= htmlspecialchars($namaProduk); ?></strong>
                            <?php if(!empty($row['jenis_bahan_manual'])): ?>
                                <div class="text-muted small"><?= htmlspecialchars($row['jenis_bahan_manual']); ?></div>
                            <?php endif; ?>
                        </td>
                        <td class="small text-muted"><?= htmlspecialchars($warna); ?></td>
                        <td>
                            <span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25 px-2 py-1 rounded-pill">
                                <?= $row['jumlah_roll']; ?> Roll
                            </span>
                        </td>
                        <td>
                            <span class="badge bg-secondary bg-opacity-10 text-secondary border px-2 py-1 rounded-2">
                                <?= htmlspecialchars($row['target_size']); ?>
                            </span>
                        </td>
                        <td class="text-center">
                            <?php if ($isResolved == 0): ?>
                                <a href="Proses_StokMasuk/resolve_pembelian.php?id_pembelian=<?= $idPembelian; ?>&aksi=1" 
                                   class="btn btn-sm btn-outline-success rounded-pill px-3 fw-bold" 
                                   onclick="return confirm('Tandai riwayat ini sebagai Selesai/Resolve?');">
                                    <i class="bi bi-check-circle me-1"></i> Selesai
                                </a>
                            <?php else: ?>
                                <a href="Proses_StokMasuk/resolve_pembelian.php?id_pembelian=<?= $idPembelian; ?>&aksi=0" 
                                   class="btn btn-sm btn-light text-success border rounded-pill px-3 fw-bold" 
                                   onclick="return confirm('Kembalikan status menjadi Pending?');">
                                    <i class="bi bi-check-circle-fill me-1"></i> Selesai
                                </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>