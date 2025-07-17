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
    echo "Veritabanı bağlantısı başarılı ve '" . DB_NAME . "' seçildi.\n\n";

    // schema.sql dosyasının içeriğini oku ve çalıştır
    echo "Veritabanı şeması uygulanıyor...\n";
    $sql_script = file_get_contents('schema.sql');
    if ($sql_script === false) {
        die("HATA: schema.sql dosyası okunamadı.");
    }
    
    // SQL dosyasını çalıştır
    $pdo->exec($sql_script);
    echo "Şema başarıyla uygulandı.\n\n";

    // Yönetici hesabını kontrol et ve oluştur/güncelle
    echo "Yönetici hesabı kontrol ediliyor...\n";
    $admin_username = 'admin';
    $admin_password = '123123'; // Kullanıcının istediği şifre
    $password_hash = password_hash($admin_password, PASSWORD_DEFAULT);

    $stmt = $pdo->prepare("SELECT id FROM admins WHERE username = ?");
    $stmt->execute([$admin_username]);
    $admin_exists = $stmt->fetch();

    if ($admin_exists) {
        // Şifreyi güncelle
        $stmt = $pdo->prepare("UPDATE admins SET password_hash = ? WHERE username = ?");
        $stmt->execute([$password_hash, $admin_username]);
        echo "Mevcut 'admin' kullanıcısının şifresi güncellendi.\n";
    } else {
        // Yeni admin oluştur
        $stmt = $pdo->prepare("INSERT INTO admins (username, password_hash) VALUES (?, ?)");
        $stmt->execute([$admin_username, $password_hash]);
        echo "'admin' kullanıcısı başarıyla oluşturuldu.\n";
    }
    echo "Yönetici kullanıcı adı: $admin_username\n";
    echo "Yönetici şifresi: $admin_password\n";


    echo "\n------------------------------------\n";
    echo "Kurulum başarıyla tamamlandı!\n";
    echo "Artık `setup.php` ve `seed.php` dosyalarını güvenle silebilirsiniz.\n";
    echo "Giriş için: /login.php\n";

} catch (PDOException $e) {
    die("KURULUM BAŞARISIZ: " . $e->getMessage());
} finally {
    echo "</pre>";
}