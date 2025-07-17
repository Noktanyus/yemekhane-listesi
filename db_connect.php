<?php
// config.php dosyasını dahil et
require_once 'config.php';

$pdo = null;

try {
    // PDO ile veritabanı bağlantısı oluşturma
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8mb4' COLLATE 'utf8mb4_unicode_ci'"
    ];
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (PDOException $e) {
    // Hata mesajını kullanıcıya gösterme, log dosyasına yaz.
    error_log("Veritabanı bağlantı hatası: " . $e->getMessage());
    // Kullanıcıya genel bir mesaj göster.
    die("Sistemsel bir hata oluştu. Lütfen daha sonra tekrar deneyin.");
}