<?php
require_once __DIR__ . '/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    http_response_code(403);
    die(json_encode(['success' => false, 'message' => 'Erişim engellendi.']));
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        $stmt = $pdo->query("SELECT setting_key, setting_value FROM site_settings");
        $settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        echo json_encode(['success' => true, 'data' => $settings]);
    } catch (PDOException $e) {
        http_response_code(500);
        error_log("Get settings error: " . $e->getMessage());
        die(json_encode(['success' => false, 'message' => 'Ayarlar alınırken bir hata oluştu.']));
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        http_response_code(403);
        die(json_encode(['success' => false, 'message' => 'Geçersiz CSRF token.']));
    }

    $allowed_keys = ['sks_daire_baskani', 'yemekhane_mudur_yrd', 'diyetisyen', 'yemekhane_email'];
    
    try {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare("INSERT INTO site_settings (setting_key, setting_value) VALUES (:key, :value) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");

        foreach ($allowed_keys as $key) {
            if (isset($_POST[$key])) {
                $stmt->execute([':key' => $key, ':value' => trim($_POST[$key])]);
            }
        }
        
        $pdo->commit();
        log_action('Yetkili Bilgileri Güncellendi', 'Site ayarları başarıyla güncellendi.');
        echo json_encode(['success' => true, 'message' => 'Yetkili bilgileri başarıyla kaydedildi.']);

    } catch (PDOException $e) {
        $pdo->rollBack();
        http_response_code(500);
        error_log("Update settings error: " . $e->getMessage());
        die(json_encode(['success' => false, 'message' => 'Ayarlar kaydedilirken bir veritabanı hatası oluştu.']));
    }
} else {
    http_response_code(405);
    die(json_encode(['success' => false, 'message' => 'Geçersiz istek metodu.']));
}
