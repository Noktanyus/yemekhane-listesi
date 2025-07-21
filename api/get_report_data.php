<?php

require_once __DIR__ . '/bootstrap.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    http_response_code(403);
    die(json_encode(['success' => false, 'message' => 'Yetkisiz erişim.']));
}

header('Content-Type: application/json; charset=utf-8');

try {
    // 1. Genel İstatistik Kartları
    $stats_sql = "
        SELECT
            (SELECT COUNT(*) FROM feedback) as total_feedback,
            (SELECT AVG(rating) FROM feedback WHERE is_archived = 0) as average_rating,
            (SELECT COUNT(*) FROM feedback WHERE is_read = 0 AND is_archived = 0) as new_feedback_count
    ";
    $stats_stmt = $pdo->query($stats_sql);
    $general_stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);
    $general_stats['average_rating'] = round($general_stats['average_rating'] ?? 0, 2);

    // 2. Ayın En Popüler Yemekleri
    $top_meals_sql = "
        SELECT m.name, COUNT(mn.meal_id) as count
        FROM menus mn
        JOIN meals m ON mn.meal_id = m.id
        WHERE MONTH(mn.menu_date) = MONTH(CURRENT_DATE()) AND YEAR(mn.menu_date) = YEAR(CURRENT_DATE())
        GROUP BY m.name
        ORDER BY count DESC
        LIMIT 10
    ";
    $top_meals_stmt = $pdo->query($top_meals_sql);
    $top_meals = $top_meals_stmt->fetchAll(PDO::FETCH_ASSOC);
    $top_meals_chart_data = [
        'labels' => array_column($top_meals, 'name'),
        'datasets' => [[
            'label' => 'Servis Sayısı',
            'data' => array_column($top_meals, 'count'),
            'backgroundColor' => '#007bff'
        ]]
    ];

    // 3. Puan Dağılımı
    $ratings_sql = "SELECT rating, COUNT(*) as count FROM feedback WHERE is_archived = 0 GROUP BY rating ORDER BY rating ASC";
    $ratings_stmt = $pdo->query($ratings_sql);
    $ratings_data = $ratings_stmt->fetchAll(PDO::FETCH_ASSOC);
    $ratings_chart_data = [
        'labels' => array_map(function ($r) { return $r['rating'] . ' Yıldız'; }, $ratings_data),
        'datasets' => [[
            'label' => 'Puan Sayısı',
            'data' => array_column($ratings_data, 'count'),
            'backgroundColor' => ['#dc3545', '#ffc107', '#fd7e14', '#28a745', '#007bff']
        ]]
    ];

    // 4. Şikayetlerde Öne Çıkan Kelimeler (1 ve 2 yıldızlı yorumlardan)
    $complaints_sql = "SELECT comment FROM feedback WHERE rating <= 2 AND is_archived = 0 AND comment IS NOT NULL AND comment != ''";
    $complaints_stmt = $pdo->query($complaints_sql);
    $comments = $complaints_stmt->fetchAll(PDO::FETCH_COLUMN);

    $stopwords = ['ama', 've', 'bir', 'çok', 'gibi', 'ile', 'için', 'bu', 'şu', 'o', 'da', 'de', 'ki', 'mi', 'mı', 'mu', 'mü', 'en', 'hep', 'hiç', 'sadece', 'ancak', 'daha', 'kadar', 'bence', 'böyle', 'şöyle', 'yok', 'evet', 'hayır', 'ben', 'sen', 'biz', 'siz', 'onlar', 'şey', 'her', 'bazı', 'kez', 'kere', 'defa', 'yani', 'zaten', 'ise', 'idi', 'oldu', 'olmuş', 'olan', 'olarak', 'çoktu', 'yoktu', 'vardı'];
    $word_counts = [];
    foreach ($comments as $comment) {
        $words = preg_split('/[\s,.;!?]+/', mb_strtolower($comment, 'UTF-8'));
        foreach ($words as $word) {
            if (mb_strlen($word) > 3 && !in_array($word, $stopwords)) {
                $word_counts[$word] = ($word_counts[$word] ?? 0) + 1;
            }
        }
    }
    arsort($word_counts);
    $top_complaint_words = array_slice($word_counts, 0, 10);

    echo json_encode([
        'success' => true,
        'data' => [
            'general_stats' => $general_stats,
            'top_meals_chart' => $top_meals_chart_data,
            'ratings_chart' => $ratings_chart_data,
            'complaint_words' => $top_complaint_words
        ]
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    error_log("Get report data error: " . $e->getMessage());
    die(json_encode(['success' => false, 'message' => 'Rapor verileri alınırken bir veritabanı hatası oluştu: ' . $e->getMessage()]));
}
