<?php
require_once __DIR__ . '/bootstrap.php';

try {
    // Son 50 kaydı çek
    $stmt = $pdo->query("SELECT id, admin_username, ip_address, action, details, created_at FROM logs ORDER BY created_at DESC LIMIT 50");
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Tarih formatını düzenle
    $formatted_logs = array_map(function($log) {
        try {
            // 'created_at' alanını formatla ve yeni bir anahtara ata
            $log['created_at_formatted'] = (new DateTime($log['created_at']))->format('d.m.Y H:i:s');
        } catch (Exception $e) {
            $log['created_at_formatted'] = 'Geçersiz Tarih';
        }
        // Orijinal 'created_at' alanını kaldırabilir veya tutabilirsiniz.
        // unset($log['created_at']); 
        return $log;
    }, $logs);

    echo json_encode(['success' => true, 'data' => $formatted_logs]);

} catch (Exception $e) {
    http_response_code(500);
    error_log("Log çekme hatası: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'İşlem kayıtları alınırken bir veritabanı hatası oluştu.']);
}