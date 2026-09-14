<?php
session_start();
session_destroy(); // Hapus semua data login
header("Location: ../login.php");
exit();
?>