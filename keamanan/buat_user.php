<?php
include dirname(__FILE__) . '/../koneksi.php';

$user = 'miadaru';
$pass = password_hash('123', PASSWORD_DEFAULT); // Password: miadaru2024
$nama = 'Owner Miadaru';
$role = 1; //  Owner

$sql = "INSERT INTO users (username, password, nama_lengkap, id_role) VALUES ('$user', '$pass', '$nama', '$role')";

if(mysqli_query($conn, $sql)){
    echo "User Owner Berhasil Dibuat! <br> Username: $user <br> Password: miadaru2024";
} else {
    echo "Gagal: " . mysqli_error($conn);
}
?>