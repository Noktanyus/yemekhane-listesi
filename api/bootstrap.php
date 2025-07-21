<?php

/**
 * API için başlangıç dosyası.
 * Session başlatma, güvenlik kontrolü ve gerekli dosyaları dahil etme işlemlerini merkezileştirir.
 */

// Hataları raporlamayı geliştirme aşamasında etkin tut, canlıda kapatılabilir.
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Gerekli yapılandırma dosyasını en başta çağır
require_once __DIR__ . '/../config.php';

session_start();
header('Content-Type: application/json');

// Güvenlik: Normalde burada admin kontrolü yapılır, ancak bazı API'ler halka açık olmalı.
// Bu kontrol, her API dosyasının kendi içinde ihtiyaca göre yapılmalıdır.
/*
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    http_response_code(403); // Forbidden
    echo json_encode(['success' => false, 'message' => 'Yetkisiz erişim. Lütfen tekrar giriş yapın.']);
    exit;
}
*/

// Gerekli dosyaları dahil et
require_once __DIR__ . '/../db_connect.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/csrf.php';

// İşlemi yapan yöneticiyi bir değişkene ata
$admin_username = $_SESSION['admin_username'] ?? 'Bilinmeyen Admin';

// Bu noktadan sonra, bu dosyayı çağıran diğer API dosyaları $pdo ve $admin_username değişkenlerine erişebilir.
