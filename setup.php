<?php
require_once 'config.php';

try {
    // Veritabanını oluşturmak için geçici olarak dbname olmadan bağlan
    $pdo = new PDO("mysql:host=" . DB_HOST, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // schema.sql dosyasının içeriğini oku
    $sql = file_get_contents('schema.sql');

    if ($sql === false) {
        die("Hata: schema.sql dosyası okunamadı.");
    }

    // SQL komutlarını çalıştır
    $pdo->exec($sql);

    echo "Veritabanı ve tablolar başarıyla oluşturuldu! Örnek veriler eklendi.<br>";
    echo "Artık bu dosyayı silebilirsiniz.";

} catch (PDOException $e) {
    die("Kurulum sırasında hata oluştu: " . $e->getMessage());
}