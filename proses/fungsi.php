<?php
// proses/fungsi.php

function catatLog($conn, $id_user, $username, $role, $aksi, $keterangan) {
    // Membersihkan input agar aman dari SQL Injection
    $aksi = mysqli_real_escape_string($conn, $aksi);
    $keterangan = mysqli_real_escape_string($conn, $keterangan);
    
    $query = "INSERT INTO log_aktivitas (id_user, username, role, aksi, keterangan) 
              VALUES ('$id_user', '$username', '$role', '$aksi', '$keterangan')";
    
    return mysqli_query($conn, $query);
}
?>