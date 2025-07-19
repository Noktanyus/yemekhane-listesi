<?php
require_once __DIR__ . '/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

// Sayfalama ve filtreleme parametreleri
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = isset($_GET['limit']) && is_numeric($_GET['limit']) ? (int)$_GET['limit'] : 25;
$status_filter = $_GET['status'] ?? 'all';
$search_term = $_GET['search'] ?? '';
$start_date = $_GET['start_date'] ?? '';
$end_date = $_GET['end_date'] ?? '';

$offset = ($page - 1) * $limit;

// SQL sorgusunu dinamik olarak oluştur
$where_clauses = [];
$params = [];

if ($status_filter !== 'all') {
    $where_clauses[] = "status = :status";
    $params[':status'] = $status_filter;
}

if (!empty($search_term)) {
    $where_clauses[] = "(name LIKE :search_name OR comment LIKE :search_comment)";
    $params[':search_name'] = '%' . $search_term . '%';
    $params[':search_comment'] = '%' . $search_term . '%';
}

if (!empty($start_date)) {
    $where_clauses[] = "created_at >= :start_date";
    $params[':start_date'] = $start_date;
}

if (!empty($end_date)) {
    // Bitiş tarihini gün sonuna ayarlamak için
    $where_clauses[] = "created_at <= :end_date";
    $params[':end_date'] = $end_date . ' 23:59:59';
}

$sql_where = "";
if (!empty($where_clauses)) {
    $sql_where = "WHERE " . implode(' AND ', $where_clauses);
}

try {
    // Toplam sonuç sayısını filtreye göre al
    $total_stmt = $pdo->prepare("SELECT COUNT(*) FROM feedback " . $sql_where);
    $total_stmt->execute($params);
    $total_results = $total_stmt->fetchColumn();
    $total_pages = ceil($total_results / $limit);

    // Sayfa için geri bildirimleri filtreye göre al
    // LIMIT ve OFFSET, tamsayıya çevrildiği için doğrudan sorguya eklenmesi güvenlidir.
    $data_sql = "SELECT id, name, email, rating, comment, image_path, status, is_read, created_at, replied_by, replied_at, reply_text 
                 FROM feedback " . $sql_where . " 
                 ORDER BY created_at DESC 
                 LIMIT " . (int)$limit . " OFFSET " . (int)$offset;
    
    $stmt = $pdo->prepare($data_sql);
    $stmt->execute($params); // Parametreleri dizi olarak gönder
    $feedback = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Tarih formatını düzenle
    foreach ($feedback as &$fb) {
        $fb['created_at_formatted'] = (new DateTime($fb['created_at']))->format('d.m.Y H:i');
    }

    echo json_encode([
        'success' => true,
        'data' => $feedback,
        'pagination' => [
            'current_page' => $page,
            'total_pages' => (int)$total_pages,
            'total_results' => (int)$total_results
        ]
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    error_log("Get feedback error: " . $e->getMessage());
    die(json_encode(['success' => false, 'message' => 'Geri bildirimler alınırken bir veritabanı hatası oluştu.']));
}
