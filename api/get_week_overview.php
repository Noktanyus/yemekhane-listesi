<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Yetkisiz erişim.']);
    exit;
}

require_once '../db_connect.php';

// Tarihleri manuel olarak Türkçeleştirmek için diziler
$months_tr = [1 => 'Ocak', 2 => 'Şubat', 3 => 'Mart', 4 => 'Nisan', 5 => 'Mayıs', 6 => 'Haziran', 7 => 'Temmuz', 8 => 'Ağustos', 9 => 'Eylül', 10 => 'Ekim', 11 => 'Kasım', 12 => 'Aralık'];
$days_tr = ['Pazar', 'Pazartesi', 'Salı', 'Çarşamba', 'Perşembe', 'Cuma', 'Cumartesi'];

$year = filter_input(INPUT_GET, 'year', FILTER_VALIDATE_INT, ['options' => ['default' => date('Y')]]);
$month = filter_input(INPUT_GET, 'month', FILTER_VALIDATE_INT, ['options' => ['default' => date('m')]]);
$day = filter_input(INPUT_GET, 'day', FILTER_VALIDATE_INT, ['options' => ['default' => date('d')]]);

try {
    $ref_date = new DateTime("$year-$month-$day");
    
    $start_of_week = clone $ref_date;
    $start_of_week->modify('monday this week');
    $end_of_week = clone $start_of_week;
    $end_of_week->modify('+6 days');

    $start_sql = $start_of_week->format('Y-m-d');
    $end_sql = $end_of_week->format('Y-m-d');

    $sql = "
        SELECT m.menu_date, GROUP_CONCAT(ml.name SEPARATOR ', ') as menu_summary
        FROM menus m
        JOIN meals ml ON m.meal_id = ml.id
        WHERE m.menu_date BETWEEN ? AND ?
        GROUP BY m.menu_date
        UNION
        SELECT event_date as menu_date, message as menu_summary
        FROM special_days
        WHERE event_date BETWEEN ? AND ?
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$start_sql, $end_sql, $start_sql, $end_sql]);
    $week_data = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    $response = [
        'start_of_week_formatted' => $start_of_week->format('d') . ' ' . $months_tr[(int)$start_of_week->format('n')] . ' ' . $start_of_week->format('Y'),
        'end_of_week_formatted' => $end_of_week->format('d') . ' ' . $months_tr[(int)$end_of_week->format('n')] . ' ' . $end_of_week->format('Y'),
        'days' => []
    ];

    $current_day = clone $start_of_week;
    for ($i = 0; $i < 7; $i++) {
        $date_sql = $current_day->format('Y-m-d');
        $is_special = isset($week_data[$date_sql]) && !str_contains($week_data[$date_sql], ',');
        
        $day_num = (int)$current_day->format('d');
        $month_name = $months_tr[(int)$current_day->format('n')];
        $day_name = $days_tr[(int)$current_day->format('w')];

        $response['days'][] = [
            'date_sql' => $date_sql,
            'date_formatted' => "$day_num $month_name, $day_name",
            'summary' => $week_data[$date_sql] ?? 'Menü girilmemiş',
            'is_special' => $is_special
        ];
        $current_day->modify('+1 day');
    }

    echo json_encode($response);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Haftalık veri alınırken hata oluştu: ' . $e->getMessage()]);
}