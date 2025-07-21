<?php

require_once __DIR__ . '/bootstrap.php';

if (!defined('IS_MOBILE_API_CALL')) {
    if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
        http_response_code(403);
        die(json_encode(['success' => false, 'message' => 'Yetkisiz erişim.']));
    }
}

try {
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
    $stmt = $pdo->prepare(
        "SELECT id, admin_username, action_type, details, ip_address, 
                DATE_FORMAT(action_time, '%d.%m.%Y %H:%i:%s') as action_time_formatted 
         FROM logs 
         ORDER BY action_time DESC 
         LIMIT :limit"
    );
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'data' => $logs]);

} catch (Exception $e) {
    http_response_code(500);
    error_log("Log alınırken hata: " . $e->getMessage());
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Log kayıtları alınırken bir sunucu hatası oluştu.', 'error_details' => $e->getMessage()]);
}
