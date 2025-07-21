<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Durum Mesajları için
$message = '';
if (isset($_SESSION['setup_message'])) {
    $message = $_SESSION['setup_message'];
    unset($_SESSION['setup_message']);
}

// --- Durum Kontrolleri ---

// 1. Veritabanı Bağlantısı
$db_connected = false;
$db_error = '';
try {
    require_once __DIR__ . '/../db_connect.php';
    $db_connected = true;
} catch (PDOException $e) {
    $db_error = "Veritabanı bağlantısı kurulamadı. Lütfen `config.php` dosyasını kontrol edin. Hata: " . $e->getMessage();
} catch (Error $e) {
    $db_error = "Veritabanı bağlantı dosyası (`db_connect.php`) veya `config.php` bulunamadı/hatalı.";
}


// 2. Composer Bağımlılıkları
$dependencies_installed = file_exists(__DIR__ . '/../vendor/autoload.php');

// 3. Veritabanı Şeması ve Admin Hesabı
$schema_created = false;
if ($db_connected) {
    try {
        // Hem admin tablosunun hem de admin kullanıcısının varlığını kontrol et
        $stmt = $pdo->query("SELECT COUNT(*) FROM `admins`");
        $schema_created = $stmt->fetchColumn() > 0;
    } catch (Exception $e) {
        $schema_created = false;
    }
}

// 4. Örnek Veriler (Yemekler)
$data_seeded = false;
if ($db_connected) {
    try {
        $stmt = $pdo->query("SELECT COUNT(*) FROM `meals`");
        $data_seeded = $stmt->fetchColumn() > 0;
    } catch (Exception $e) {
        // meals tablosu yoksa, bu adım tamamlanmamış sayılır.
        $data_seeded = false;
    }
}

$all_done = $dependencies_installed && $schema_created;

?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Proje Kurulum Sihirbazı</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; line-height: 1.6; color: #333; max-width: 800px; margin: 20px auto; padding: 20px; background-color: #f4f7f9; }
        .container { background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        h1, h2 { color: #2c3e50; border-bottom: 2px solid #e0e0e0; padding-bottom: 10px; }
        .step { display: flex; align-items: center; justify-content: space-between; padding: 15px; border: 1px solid #ddd; border-radius: 5px; margin-bottom: 15px; transition: all 0.3s ease; }
        .step-name { font-weight: bold; font-size: 1.1em; }
        .status { font-weight: bold; padding: 5px 10px; border-radius: 15px; color: #fff; }
        .status-ok { background-color: #27ae60; }
        .status-pending { background-color: #e67e22; }
        .btn { text-decoration: none; color: #fff; padding: 10px 18px; border-radius: 5px; font-weight: bold; transition: background-color 0.2s ease; border: none; cursor: pointer; font-size: 1em; }
        .btn-primary { background-color: #3498db; }
        .btn-primary:hover { background-color: #2980b9; }
        .btn-secondary { background-color: #7f8c8d; }
        .btn-secondary:hover { background-color: #6c7a7d; }
        .btn-disabled { background-color: #bdc3c7; cursor: not-allowed; }
        .btn-all { background-color: #8e44ad; display: inline-block; width: calc(100% - 36px); text-align: center; margin-top: 20px; }
        .btn-all:hover { background-color: #732d91; }
        .message { padding: 15px; border-radius: 5px; margin-bottom: 20px; color: #fff; }
        .message-success { background-color: #27ae60; }
        .message-error { background-color: #c0392b; }
        .db-error { color: #c0392b; font-weight: bold; padding: 10px; border: 1px solid #c0392b; border-radius: 5px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Proje Kurulum Sihirbazı</h1>

        <?php if ($message): ?>
            <div class="message <?= strpos($message, 'Hata') === false ? 'message-success' : 'message-error'; ?>">
                <?= $message ?>
            </div>
        <?php endif; ?>

        <?php if ($db_error): ?>
            <div class="db-error"><?= $db_error ?></div>
        <?php else: ?>
            <h2>Kurulum Adımları</h2>

            <!-- Adım 1: Composer Bağımlılıkları -->
            <div class="step">
                <span class="step-name">1. Gerekli Kütüphanelerin Kurulumu (Composer)</span>
                <span class="status <?= $dependencies_installed ? 'status-ok' : 'status-pending' ?>">
                    <?= $dependencies_installed ? 'Tamamlandı' : 'Bekliyor' ?>
                </span>
                <a href="install_dependencies.php" class="btn <?= $dependencies_installed ? 'btn-secondary' : 'btn-primary' ?>"><?= $dependencies_installed ? 'Yeniden Kur' : 'Kur' ?></a>
            </div>

            <!-- Adım 2: Veritabanı Şeması ve Admin -->
            <div class="step">
                <span class="step-name">2. Veritabanı Tablolarını ve Admin Hesabını Oluştur</span>
                <span class="status <?= $schema_created ? 'status-ok' : 'status-pending' ?>">
                     <?= $schema_created ? 'Tamamlandı' : 'Bekliyor' ?>
                </span>
                <?php if ($schema_created): ?>
                     <a href="create_schema.php" onclick="return confirm('Mevcut tüm veriler silinecek ve admin hesabı sıfırlanacak. Emin misiniz?');" class="btn btn-secondary">Yeniden Oluştur</a>
                <?php else: ?>
                     <a href="create_schema.php" class="btn <?= $dependencies_installed ? 'btn-primary' : 'btn-disabled' ?>" <?= !$dependencies_installed ? 'onclick="event.preventDefault(); alert(\'Önce 1. adımı tamamlayın.\');"' : '' ?>>Oluştur</a>
                <?php endif; ?>
            </div>

            <!-- Adım 3: Başlangıç Verileri -->
            <div class="step">
                <span class="step-name">3. Örnek Yemek Verilerini Ekle (İsteğe Bağlı)</span>
                <span class="status <?= $data_seeded ? 'status-ok' : 'status-pending' ?>">
                    <?= $data_seeded ? 'Tamamlandı' : 'Bekliyor' ?>
                </span>
                 <a href="seed_data.php" class="btn <?= !$schema_created ? 'btn-disabled' : ($data_seeded ? 'btn-secondary' : 'btn-primary') ?>" <?= !$schema_created ? 'onclick="event.preventDefault(); alert(\'Önce 2. adımı tamamlayın.\');"' : '' ?>><?= $data_seeded ? 'Yeniden Ekle' : 'Ekle' ?></a>
            </div>

            <hr>

            <?php if ($all_done): ?>
                 <div class="message message-success">Tebrikler! Temel kurulum tamamlandı. Projeyi kullanmaya başlayabilirsiniz. Güvenlik için `setup` klasörünü silmeniz veya erişimi kısıtlamanız önerilir.</div>
            <?php else: ?>
                <a href="install_dependencies.php?run-all=true" class="btn btn-all">Tüm Kurulumu Başlat (Sırayla)</a>
            <?php endif; ?>

        <?php endif; ?>
    </div>
</body>
</html>