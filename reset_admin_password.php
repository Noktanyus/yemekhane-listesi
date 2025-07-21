<?php

require_once __DIR__ . '/db_connect.php';

$username_to_reset = 'admin';
$new_password = 'admin123';

try {
    // Yeni şifre için güvenli bir hash oluştur
    $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);

    if (!$new_password_hash) {
        throw new Exception('Şifre özeti oluşturulamadı.');
    }

    // Veritabanını güncelle
    $stmt = $pdo->prepare("UPDATE admins SET password_hash = ? WHERE username = ?");
    $stmt->execute([$new_password_hash, $username_to_reset]);

    // Güncellemenin başarılı olup olmadığını kontrol et
    if ($stmt->rowCount() > 0) {
        echo "<h1>Başarılı!</h1>";
        echo "<p>'{$username_to_reset}' kullanıcısının şifresi başarıyla '{$new_password}' olarak sıfırlandı.</p>";
        echo "<p><b>ÖNEMLİ:</b> Güvenlik nedeniyle bu dosyayı şimdi sunucudan silin!</p>";
    } else {
        echo "<h1>Hata!</h1>";
        echo "<p>'{$username_to_reset}' kullanıcısı bulunamadı. Lütfen kullanıcı adının doğru olduğundan emin olun.</p>";
    }

} catch (Exception $e) {
    echo "<h1>Kritik Hata!</h1>";
    echo "<p>Şifre sıfırlanırken bir hata oluştu: " . $e->getMessage() . "</p>";
}
