<?php
require_once __DIR__ . '/bootstrap.php';

try {
    // Son 200 log kaydını en yeniden eskiye doğru çek
    $stmt = $pdo->query(
        "SELECT admin_username, ip_address, action_type, action_summary, details, created_at 
         FROM logs 
         ORDER BY created_at DESC 
         LIMIT 200"
    );
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Tarih formatını güzelleştir
    foreach ($logs as &$log) {
        $date = new DateTime($log['created_at']);
        $date->setTimezone(new DateTimeZone('Europe/Istanbul'));
        $log['created_at_formatted'] = $date->format('d.m.Y H:i:s');
    }

    echo json_encode(['success' => true, 'data' => $logs]);

} catch (PDOException $e) {
    http_response_code(500);
    error_log("Log çekme hatası: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'İşlem kayıtları alınırken bir veritabanı hatası oluştu.']);
}
