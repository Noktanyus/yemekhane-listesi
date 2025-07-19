<?php
require_once __DIR__ . '/bootstrap.php';

try {
    // IP adresi sütunu şemada olmadığı için sorgudan kaldırıldı.
    $stmt = $pdo->query("SELECT id, admin_username, action as action_type, action as action_summary, details, created_at FROM logs ORDER BY created_at DESC LIMIT 100");
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $formatted_logs = array_map(function($log) {
        try {
            $log['created_at_formatted'] = (new DateTime($log['created_at']))->format('d.m.Y H:i:s');
        } catch (Exception $e) {
            $log['created_at_formatted'] = 'Geçersiz Tarih';
        }
        return $log;
    }, $logs);

    echo json_encode(['success' => true, 'data' => $formatted_logs]);

} catch (Exception $e) {
    http_response_code(500);
    error_log("Log çekme hatası: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'İşlem kayıtları alınırken bir veritabanı hatası oluştu.']);
}
