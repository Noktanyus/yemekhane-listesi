<?php
session_start();
require_once __DIR__ . '/../config.php';

// config.php içinde PHP_EXECUTABLE_PATH tanımlı değilse, varsayılan bir değer ata
if (!defined('PHP_EXECUTABLE_PATH')) {
    // Windows için genel bir varsayım, gerekirse config.php'den değiştirilebilir.
    define('PHP_EXECUTABLE_PATH', 'php'); 
}

$root_dir = realpath(__DIR__ . '/../');
$composer_path = $root_dir . DIRECTORY_SEPARATOR . 'composer.phar';
$php_path = PHP_EXECUTABLE_PATH;

// Composer'ın HOME dizinini proje içinde güvenli bir konuma ayarla
$composer_home = $root_dir . DIRECTORY_SEPARATOR . '.composer-cache';
if (!is_dir($composer_home)) {
    mkdir($composer_home, 0777, true);
}
putenv('COMPOSER_HOME=' . $composer_home);

// Komutu çalıştırmadan önce projenin ana dizinine geç
// Bu, `cd` komutunu platformdan bağımsız hale getirir.
if (!chdir($root_dir)) {
    $_SESSION['setup_message'] = 'Hata: Proje dizinine geçilemedi: ' . htmlspecialchars($root_dir);
    header('Location: index.php');
    exit;
}

// Komut artık `cd` içermiyor.
$command = escapeshellarg($php_path) . ' ' . escapeshellarg($composer_path) . ' install --no-interaction --prefer-dist --optimize-autoloader 2>&1';

$output = "<pre>";

// Komutu çalıştır
exec($command, $lines, $return_var);
$output .= "Komut: " . htmlspecialchars($command) . "\n\n";
$output .= "Çıktı:\n" . htmlspecialchars(implode("\n", $lines)) . "\n";
$output .= "</pre>";

if ($return_var === 0) {
    $_SESSION['setup_message'] = 'Başarılı: Gerekli kütüphaneler (vendor) başarıyla kuruldu.';
} else {
    $_SESSION['setup_message'] = 'Hata: Kütüphaneler kurulurken bir sorun oluştu. Lütfen çıktıyı kontrol edin: ' . $output;
}

// Otomatik kurulum modunda bir sonraki adıma geç
if (isset($_GET['run-all'])) {
    header('Location: create_schema.php?run-all=true');
    exit;
}

header('Location: index.php');
exit;
