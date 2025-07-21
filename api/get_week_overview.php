<?php

require_once __DIR__ . '/bootstrap.php';

if (!defined('IS_MOBILE_API_CALL')) {
    // Bu API'nin sadece adminler tarafından kullanılabilmesini sağla
    if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
        http_response_code(403);
        die(json_encode(['success' => false, 'message' => 'Yetkisiz erişim.']));
    }
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    die(json_encode(['success' => false, 'message' => 'Geçersiz istek metodu.']));
}

// Tarihleri manuel olarak Türkçeleştirmek için diziler
$months_tr = [1 => 'Ocak', 2 => 'Şubat', 3 => 'Mart', 4 => 'Nisan', 5 => 'Mayıs', 6 => 'Haziran', 7 => 'Temmuz', 8 => 'Ağustos', 9 => 'Eylül', 10 => 'Ekim', 11 => 'Kasım', 12 => 'Aralık'];
$days_tr = ['Pazar', 'Pazartesi', 'Salı', 'Çarşamba', 'Perşembe', 'Cuma', 'Cumartesi'];

$date_param = $_GET['date'] ?? date('Y-m-d');

try {
    $ref_date = new DateTime($date_param);

    $start_of_week = clone $ref_date;
    $start_of_week->modify('monday this week');
    $end_of_week = clone $start_of_week;
    $end_of_week->modify('+6 days');

    $start_sql = $start_of_week->format('Y-m-d');
    $end_sql = $end_of_week->format('Y-m-d');

    // Veritabanından haftanın verilerini çek (Doğru ve basit yöntem)
    $sql = "
        (SELECT 
            m.menu_date, 
            GROUP_CONCAT(ml.name SEPARATOR ', ') as summary,
            'menu' as type
        FROM menus m
        JOIN meals ml ON m.meal_id = ml.id
        WHERE m.menu_date BETWEEN :start_date AND :end_date
        GROUP BY m.menu_date)
        UNION ALL
        (SELECT 
            event_date as menu_date, 
            message as summary,
            'special' as type
        FROM special_days
        WHERE event_date BETWEEN :start_date_special AND :end_date_special)
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':start_date' => $start_sql,
        ':end_date' => $end_sql,
        ':start_date_special' => $start_sql,
        ':end_date_special' => $end_sql
    ]);
    $week_data_raw = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Veriyi doğru şekilde işle
    $week_data = [];
    foreach ($week_data_raw as $item) {
        $week_data[$item['menu_date']] = ['summary' => $item['summary'], 'type' => $item['type']];
    }

    $response = [
        'start_of_week_formatted' => $start_of_week->format('d') . ' ' . $months_tr[(int)$start_of_week->format('n')],
        'end_of_week_formatted' => $end_of_week->format('d') . ' ' . $months_tr[(int)$end_of_week->format('n')] . ' ' . $end_of_week->format('Y'),
        'days' => []
    ];

    $current_day = clone $start_of_week;
    for ($i = 0; $i < 7; $i++) {
        $date_sql = $current_day->format('Y-m-d');
        $day_info = $week_data[$date_sql] ?? null;

        $response['days'][] = [
            'date_sql' => $date_sql,
            'date_formatted' => $current_day->format('d') . ' ' . $months_tr[(int)$current_day->format('n')] . ', ' . $days_tr[(int)$current_day->format('w')],
            'summary' => $day_info['summary'] ?? 'Menü girilmemiş',
            'is_special' => ($day_info['type'] ?? '') === 'special'
        ];
        $current_day->modify('+1 day');
    }

    // Yanıtı ön yüzün beklediği formatta gönder
    header('Content-Type: application/json');
    echo json_encode($response);

} catch (Exception $e) {
    http_response_code(500);
    error_log("Haftalık genel bakış hatası: " . $e->getMessage());
    // Hata durumunda standart bir JSON yanıtı gönder
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Haftalık veri alınırken bir sunucu hatası oluştu.']);
}
