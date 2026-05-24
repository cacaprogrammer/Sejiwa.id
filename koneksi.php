<?php
$host     = "localhost";
$user     = "root";
$pass     = "";
$database = "db_sejiwa";

$conn = new mysqli($host, $user, $pass, $database);

if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}
?>