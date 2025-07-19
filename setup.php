<?php
require_once 'db_connect.php'; // Sadece $pdo'yu almak için

echo "<h1>Veritabanı Kurulumu Başlatılıyor...</h1>";

try {
    // 1. Schema.sql dosyasının içeriğini oku
    $sql_file = __DIR__ . '/schema.sql';
    if (!file_exists($sql_file)) {
        throw new Exception("schema.sql dosyası bulunamadı!");
    }
    $sql = file_get_contents($sql_file);

    // 2. SQL komutlarını çalıştır
    // Not: Bu yöntem, dosyada birden fazla sorgu varsa ve bunlar noktalı virgülle ayrılmışsa çalışır.
    // PDO::exec() genellikle tek seferde birden fazla sorguyu desteklemez, ancak bu basit senaryoda çalışabilir.
    // Daha karmaşık durumlar için sorguları ayırıp tek tek çalıştırmak gerekir.
    $pdo->exec($sql);

    echo "<p style='color:green;'>Veritabanı şeması başarıyla yüklendi veya güncellendi.</p>";
    
    // 3. Varsayılan admin kullanıcısını kontrol et ve ekle
    $stmt = $pdo->query("SELECT id FROM admins WHERE username = 'admin'");
    if ($stmt->fetch()) {
        echo "<p style='color:blue;'>'admin' kullanıcısı zaten mevcut.</p>";
    } else {
        $username = 'admin';
        $password = '123123'; // İstek üzerine güncellendi
        $password_hash = password_hash($password, PASSWORD_DEFAULT);

        $insert_stmt = $pdo->prepare("INSERT INTO admins (username, password_hash) VALUES (?, ?)");
        $insert_stmt->execute([$username, $password_hash]);
        echo "<p style='color:green;'>Varsayılan admin kullanıcısı oluşturuldu.</p>";
        echo "<p><strong>Kullanıcı Adı:</strong> admin</p>";
        echo "<p><strong>Şifre:</strong> 123123</p>";
        echo "<p style='color:red;'><strong>GÜVENLİK UYARISI:</strong> Bu şifre güvensizdir. Lütfen ilk girişten sonra değiştirin!</p>";
    }

} catch (Exception $e) {
    die("<p style='color:red;'>Bir hata oluştu: " . $e->getMessage() . "</p>");
}

echo "<h2>Kurulum Tamamlandı.</h2>";
