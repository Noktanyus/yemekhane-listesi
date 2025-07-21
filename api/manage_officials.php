<?php

require_once __DIR__ . '/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');


// İsteğin türüne göre işlemleri ayır
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // GET istekleri halka açıktır, yetki kontrolü GEREKMEZ.
    try {
        $stmt = $pdo->query("SELECT setting_key, setting_value FROM site_settings WHERE setting_key IN ('sks_daire_baskani', 'yemekhane_mudur_yrd', 'diyetisyen', 'yemekhane_email')");
        $settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        echo json_encode(['success' => true, 'data' => $settings]);
    } catch (PDOException $e) {
        http_response_code(500);
        error_log("Get settings error: " . $e->getMessage());
        die(json_encode(['success' => false, 'message' => 'Ayarlar alınırken bir hata oluştu.']));
    }

} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // POST istekleri YETKİ GEREKTİRİR.

    // 1. Yetki Kontrolü (Web ve Mobil için)
    $is_authenticated = false;
    if (defined('IS_MOBILE_API_CALL')) {
        // Mobil API için JWT ile gelen kullanıcıyı kontrol et
        if (!empty($GLOBALS['current_admin_username'])) {
            $is_authenticated = true;
            $admin_username = $GLOBALS['current_admin_username'];
        }
    } else {
        // Web arayüzü için session'ı kontrol et
        if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
            $is_authenticated = true;
            $admin_username = $_SESSION['admin_username'];
            verify_csrf_token_and_exit(); // CSRF kontrolü sadece session tabanlı web istekleri için yapılır.
        }
    }

    if (!$is_authenticated) {
        http_response_code(403);
        die(json_encode(['success' => false, 'message' => 'Bu işlemi yapmak için yetkiniz yok.']));
    }

    // 2. Veri İşleme (Yetki kontrolü başarılıysa)
    try {
        $pdo->beginTransaction();

        $allowed_keys = ['sks_daire_baskani', 'yemekhane_mudur_yrd', 'diyetisyen', 'yemekhane_email'];

        $stmt = $pdo->prepare(
            "INSERT INTO site_settings (setting_key, setting_value) 
             VALUES (:key, :value) 
             ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)"
        );

        foreach ($allowed_keys as $key) {
            if (isset($_POST[$key])) {
                $value = trim($_POST[$key]);
                $stmt->execute([':key' => $key, ':value' => $value]);
            }
        }

        log_action('settings_update', $admin_username, 'Site ayarları (yetkililer) güncellendi.');

        $pdo->commit();

        echo json_encode(['success' => true, 'message' => 'Yetkili bilgileri başarıyla güncellendi.']);

    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        http_response_code(500);
        error_log("Yetkili yönetimi hatası: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'İşlem sırasında bir hata oluştu: ' . $e->getMessage()]);
    }

} else {
    http_response_code(405);
    die(json_encode(['success' => false, 'message' => 'Geçersiz istek metodu.']));
}

