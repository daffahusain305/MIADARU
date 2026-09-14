<?php
session_start();
include __DIR__ . '/../koneksi.php';

if(isset($_POST['username']) && isset($_POST['password'])){
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = $_POST['password'];

    $query = mysqli_query($conn, "SELECT u.*, r.nama_role FROM users u 
                                  JOIN roles r ON u.id_role = r.id_role 
                                  WHERE u.username = '$username'");

    if (mysqli_num_rows($query) == 1) {
        $user = mysqli_fetch_assoc($query);
        
        if (password_verify($password, $user['password'])) {
            $_SESSION['id_user']   = $user['id_user'];
            $_SESSION['username']  = $user['username'];
            $_SESSION['nama']      = $user['nama_lengkap'];
            $_SESSION['role']      = $user['nama_role'];
            
            header("Location: ../index.php"); 
            exit();
        } else {
            header("Location: ../login.php?pesan=gagal"); 
            exit();
        }
    } else {
        header("Location: ../login.php?pesan=gagal");
        exit();
    }
} else {
    header("Location: ../login.php");
    exit();
}
?>