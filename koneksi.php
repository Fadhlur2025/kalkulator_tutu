<?php
// Pengaturan default XAMPP: user root, tanpa password
$conn = new mysqli('localhost', 'root', '', 'kalkulator_tutu');
if ($conn->connect_error) {
    die('Koneksi gagal: ' . $conn->connect_error . '. Pastikan MySQL di XAMPP aktif dan database.sql sudah diimpor.');
}
$conn->set_charset('utf8mb4');
