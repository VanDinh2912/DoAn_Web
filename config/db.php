<?php
declare(strict_types=1);

$host = "localhost";
$user = "root";
$pass = "vertrigo";
$dbname = "QLBD";

try {
    $dsn = "mysql:host={$host};dbname={$dbname};charset=utf8mb4";
    $conn = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (PDOException $e) {
    error_log("Database connection failed: " . $e->getMessage());
    http_response_code(500);
    exit("Lỗi kết nối cơ sở dữ liệu.");
}