<?php

require_once __DIR__ . '/bootstrap.php';

if (!defined('IS_MOBILE_API_CALL')) {
    if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
        http_response_code(403);
        die(json_encode(['success' => false, 'message' => 'Yetkisiz erişim.']));
    }
}

header('Content-Type: application/json; charset=utf-8');

try {
    // Filtreleme ve sayfalama parametreleri
    $status_filter = $_GET['status'] ?? 'all';
    $search_term = $_GET['search'] ?? '';
    $start_date = $_GET['start_date'] ?? '';
    $end_date = $_GET['end_date'] ?? '';
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 25;
    $offset = ($page - 1) * $limit;

    $where_clauses = [];
    $params = [];

    // Durum filtresi
    switch ($status_filter) {
        case 'yeni':
            $where_clauses[] = "f.is_read = 0 AND f.is_archived = 0 AND f.reply_message IS NULL";
            break;
        case 'okundu':
            $where_clauses[] = "f.is_read = 1 AND f.is_archived = 0 AND f.reply_message IS NULL";
            break;
        case 'cevaplandı':
            $where_clauses[] = "f.reply_message IS NOT NULL AND f.is_archived = 0";
            break;
        case 'arsivlendi':
            $where_clauses[] = "f.is_archived = 1";
            break;
    }

    // Arama filtresi
    if (!empty($search_term)) {
        // Her yer tutucu için benzersiz bir isim kullanıyoruz
        $where_clauses[] = "(f.name LIKE :search_name OR f.comment LIKE :search_comment)";
        $search_like = '%' . $search_term . '%';
        $params['search_name'] = $search_like;
        $params['search_comment'] = $search_like;
    }

    // Tarih filtresi
    if (!empty($start_date)) {
        $where_clauses[] = "f.created_at >= :start_date";
        $params['start_date'] = $start_date;
    }
    if (!empty($end_date)) {
        $where_clauses[] = "f.created_at <= :end_date";
        $params['end_date'] = $end_date . ' 23:59:59';
    }

    $sql_where = !empty($where_clauses) ? "WHERE " . implode(' AND ', $where_clauses) : "";

    // Toplam sonuç sayısını al
    $total_stmt = $pdo->prepare("SELECT COUNT(*) FROM feedback f " . $sql_where);
    $total_stmt->execute($params);
    $total_results = $total_stmt->fetchColumn();
    $total_pages = ceil($total_results / $limit);

    // Sayfalanmış veriyi al
    $data_sql = "SELECT f.id, f.name, f.email, f.rating, f.comment, f.image_path, f.is_read, f.is_archived, f.created_at, f.reply_message, f.replied_at, a.username as replied_by_username
                 FROM feedback f
                 LEFT JOIN admins a ON f.replied_by = a.id
                 " . $sql_where . " 
                 ORDER BY f.created_at DESC 
                 LIMIT :limit OFFSET :offset";

    $stmt = $pdo->prepare($data_sql);

    // execute() fonksiyonuna gönderilen dizideki anahtarların başında ':' olmamalıdır.
    $final_params = $params;
    $final_params['limit'] = (int)$limit;
    $final_params['offset'] = (int)$offset;

    $stmt->execute($final_params);

    $feedback_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Frontend için durum ve formatlanmış tarih ekle
    $feedback_processed = array_map(function ($fb) {
        if ($fb['is_archived']) {
            $fb['status'] = 'arsivlendi';
        } elseif (!empty($fb['reply_message'])) {
            $fb['status'] = 'cevaplandı';
        } elseif ($fb['is_read']) {
            $fb['status'] = 'okundu';
        } else {
            $fb['status'] = 'yeni';
        }
        $fb['created_at_formatted'] = date('d.m.Y H:i', strtotime($fb['created_at']));
        return $fb;
    }, $feedback_data);


    echo json_encode([
        'success' => true,
        'data' => $feedback_processed,
        'pagination' => [
            'current_page' => $page,
            'total_pages' => (int)$total_pages,
            'total_results' => (int)$total_results,
            'limit' => (int)$limit
        ]
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    error_log("Get feedback error: " . $e->getMessage());
    // Hata ayıklama için detaylı mesajı konsola gönder
    die(json_encode(['success' => false, 'message' => 'Geri bildirimler alınırken bir veritabanı hatası oluştu.', 'error_details' => $e->getMessage()]));
}
