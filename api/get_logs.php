<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Yetkisiz erişim.']);
    exit;
}

require_once '../db_connect.php';

try {
    // Son 100 log kaydını en yeniden eskiye doğru çek
    $stmt = $pdo->query("SELECT admin_username, action, details, created_at FROM logs ORDER BY created_at DESC LIMIT 100");
    $logs = $stmt->fetchAll();
    
    // Tarih formatını güzelleştir
    foreach ($logs as &$log) {
        $date = new DateTime($log['created_at']);
        // Türkiye saat dilimine göre formatla
        $date->setTimezone(new DateTimeZone('Europe/Istanbul'));
        $log['created_at_formatted'] = $date->format('d.m.Y H:i:s');
    }

    echo json_encode(['success' => true, 'data' => $logs]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Veritabanı hatası: ' . $e->getMessage()]);
}
