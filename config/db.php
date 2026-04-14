<?php
$host = "localhost";
$user = "root";
$pass = "vertrigo"; 
$dbname = "quanly_bida";

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    echo "Lỗi kết nối: " . $e->getMessage();
}
?>