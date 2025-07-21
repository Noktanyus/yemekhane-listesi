<?php
session_start();
require_once __DIR__ . '/../db_connect.php';

$sql_file = __DIR__ . '/schema.sql';

if (!file_exists($sql_file)) {
    $_SESSION['setup_message'] = 'Hata: `setup/schema.sql` dosyası bulunamadı.';
    header('Location: index.php');
    exit;
}

try {
    // 1. Şemayı SQL dosyasından yükle
    $sql = file_get_contents($sql_file);
    $pdo->exec($sql);

    // 2. Varsayılan admin kullanıcısını oluştur
    // Önce mevcut adminleri temizle
    $pdo->exec("DELETE FROM `admins`");

    $username = 'admin';
    $password = 'admin123';
    $password_hash = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $pdo->prepare("INSERT INTO `admins` (username, password_hash) VALUES (?, ?)");
    $stmt->execute([$username, $password_hash]);

    $_SESSION['setup_message'] = 'Başarılı: Veritabanı tabloları oluşturuldu ve varsayılan yönetici hesabı eklendi.<br>Kullanıcı Adı: <strong>' . $username . '</strong> | Şifre: <strong>' . $password . '</strong><br>Lütfen giriş yaptıktan sonra şifrenizi değiştirin.';

} catch (PDOException $e) {
    $_SESSION['setup_message'] = 'Hata: Veritabanı kurulumu sırasında bir sorun oluştu. Hata: ' . $e->getMessage();
    header('Location: index.php');
    exit;
}

// Otomatik kurulum modunda bir sonraki adıma geç
if (isset($_GET['run-all'])) {
    header('Location: seed_data.php?run-all=true');
    exit;
}

header('Location: index.php');
exit;
