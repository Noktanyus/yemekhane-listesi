<?php

require_once __DIR__ . '/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $stmt = $pdo->query("SELECT setting_key, setting_value FROM site_settings WHERE setting_key IN ('sks_daire_baskani', 'yemekhane_mudur_yrd', 'diyetisyen', 'yemekhane_email')");
    $settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    // Anahtarları daha anlaşılır hale getir
    $formatted_settings = [
        'S.K.S Daire Başkanı' => $settings['sks_daire_baskani'] ?? '',
        'Yemekhane Müdür Yrd.' => $settings['yemekhane_mudur_yrd'] ?? '',
        'Diyetisyen' => $settings['diyetisyen'] ?? '',
        'Yemekhane E-posta' => $settings['yemekhane_email'] ?? ''
    ];

    echo json_encode(['success' => true, 'data' => $formatted_settings]);

} catch (PDOException $e) {
    http_response_code(500);
    error_log("Get site info error: " . $e->getMessage());
    die(json_encode(['success' => false, 'message' => 'Site bilgileri alınırken bir hata oluştu.']));
}
