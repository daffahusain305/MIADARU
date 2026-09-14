<?php
include __DIR__ . '/../koneksi.php';

$query = mysqli_query($conn, "SELECT * FROM log_retur WHERE status = 'Pending' ORDER BY tgl_retur ASC");

if(mysqli_num_rows($query) > 0) {
    echo '<div class="table-responsive">
            <table class="table table-sm table-hover align-middle">
                <thead class="small text-muted border-bottom">
                    <tr>
                        <th>TGL</th>
                        <th>PRODUK</th>
                        <th class="text-center">SIZE</th>
                        <th class="text-center">QTY</th>
                        <th class="text-end">AKSI</th>
                    </tr>
                </thead>
                <tbody>';
                
    while($row = mysqli_fetch_assoc($query)) {
        $id_retur = $row['id_retur'];
        $qty_skrg = $row['jumlah_retur'];

        echo '<tr>
                <td class="small">'.date('d/m/y', strtotime($row['tgl_retur'])).'</td>
                <td>
                    <strong>'.$row['nama_produk'].'</strong><br>
                    <small class="text-muted">'.$row['nama_warna'].'</small>
                </td>
                <td class="text-center">
                    <span class="badge bg-light text-dark border">'.strtoupper($row['ukuran']).'</span>
                </td>
                <td class="text-center text-danger fw-bold">'.$qty_skrg.'</td>
                <td class="text-end">
                    <div class="btn-group">
                        <a href="Proses_ReturBarang/aksi_retur.php?aksi=resolved&id='.$id_retur.'" class="btn btn-sm btn-success py-0">
                           <i class="bi bi-check2"></i>
                        </a>

                        <button type="button" class="btn btn-sm btn-warning py-0" 
                            onclick="
                                var q = prompt(\'Masukkan jumlah retur baru:\', \''.$qty_skrg.'\');
                                if(q !== null && q !== \'\') {
                                    var f = document.createElement(\'form\');
                                    f.method = \'POST\';
                                    f.action = \'Proses_ReturBarang/aksi_retur.php?aksi=edit\';
                                    
                                    var i1 = document.createElement(\'input\'); i1.type=\'hidden\'; i1.name=\'id\'; i1.value=\''.$id_retur.'\'; f.appendChild(i1);
                                    var i3 = document.createElement(\'input\'); i3.type=\'hidden\'; i3.name=\'new_qty\'; i3.value=q; f.appendChild(i3);
                                    
                                    document.body.appendChild(f);
                                    f.submit();
                                }
                            ">
                            <i class="bi bi-pencil"></i>
                        </button>

                        <a href="Proses_ReturBarang/aksi_retur.php?aksi=hapus&id='.$id_retur.'" 
                           class="btn btn-sm btn-danger py-0" 
                           onclick="return confirm(\'Hapus data ini?\')">
                           <i class="bi bi-trash"></i>
                        </a>
                    </div>
                </td>
              </tr>';
    }
    echo '</tbody></table></div>';
} else {
    echo '<div class="text-center py-5"><p class="text-muted">Tidak ada retur pending.</p></div>';
}
?>