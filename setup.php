<?php
require_once 'config.php';

echo "<pre style='font-family: monospace; white-space: pre-wrap; background: #f4f4f4; padding: 15px; border-radius: 5px;'>";
echo "Kurulum başlatıldı...\n";

try {
    // Veritabanını oluşturmak için geçici olarak dbname olmadan bağlan
    $pdo = new PDO("mysql:host=" . DB_HOST, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec("CREATE DATABASE IF NOT EXISTS " . DB_NAME . " CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE " . DB_NAME);
    echo "Veritabanı bağlantısı başarılı ve '" . DB_NAME . "' seçildi.\n";

    // schema.sql dosyasının içeriğini oku
    $sql_script = file_get_contents('schema.sql');
    if ($sql_script === false) {
        die("HATA: schema.sql dosyası okunamadı.");
    }

    // SQL dosyasını noktalı virgüllere göre komutlara ayır
    // Not: Bu basit ayırıcı, SQL içinde noktalı virgül içeren (örn: trigger, procedure) durumlar için uygun değildir.
    // Ancak mevcut şemamız için yeterlidir.
    $commands = explode(';', $sql_script);

    foreach ($commands as $command) {
        $command = trim($command);
        if ($command !== '') {
            try {
                $pdo->exec($command);
                echo "BAŞARILI: " . htmlspecialchars(substr($command, 0, 80)) . "...\n";
            } catch (PDOException $e) {
                // Hata kodu '1060' Duplicate column hatasıdır. Bu hatayı görmezden gel.
                if ($e->errorInfo[1] == 1060) {
                    echo "UYARI: Sütun zaten mevcut, atlanıyor. (" . htmlspecialchars(substr($command, 0, 80)) . ")...)\n";
                } else {
                    // Diğer tüm hataları göster
                    throw $e;
                }
            }
        }
    }

    echo "\nKurulum başarıyla tamamlandı!\n";
    echo "Artık bu dosyayı güvenle silebilirsiniz.\n";

} catch (PDOException $e) {
    die("KURULUM BAŞARISIZ: " . $e->getMessage());
} finally {
    echo "</pre>";
}
