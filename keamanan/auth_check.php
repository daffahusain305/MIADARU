<?php
session_start();

// Jika tidak ada session username, berarti belum login
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}
?>