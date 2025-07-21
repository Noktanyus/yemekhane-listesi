<?php

require_once __DIR__ . '/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    die(json_encode(['success' => false, 'message' => 'Geçersiz istek metodu.']));
}

// Gelen parametreleri kontrol et
$year = filter_input(INPUT_GET, 'year', FILTER_VALIDATE_INT);
$month = filter_input(INPUT_GET, 'month', FILTER_VALIDATE_INT);
$single_date = filter_input(INPUT_GET, 'date', FILTER_SANITIZE_STRING);

// Duruma göre işlem yap
if ($year && $month) {
    // Aylık takvim görünümü için veri çek
    get_monthly_data($pdo, $year, $month);
} elseif ($single_date) {
    // Tek bir günün detayları için veri çek
    get_single_day_data($pdo, $single_date);
} else {
    http_response_code(400);
    die(json_encode(['success' => false, 'message' => 'Eksik veya geçersiz parametreler. Yıl/ay veya tek bir tarih gereklidir.']));
}

/**
 * Belirtilen ay için tüm menü ve özel gün verilerini çeker.
 */
function get_monthly_data($pdo, $year, $month)
{
    $start_date = "$year-$month-01";
    $end_date = date("Y-m-t", strtotime($start_date));

    $response = [
        'menus' => [],
        'special_days' => []
    ];

    try {
        // Menüleri çek
        $stmt = $pdo->prepare(
            "SELECT m.menu_date, ml.name, ml.calories
             FROM menus m
             JOIN meals ml ON m.meal_id = ml.id
             WHERE m.menu_date BETWEEN :start_date AND :end_date
             ORDER BY m.menu_date, ml.id"
        );
        $stmt->execute([':start_date' => $start_date, ':end_date' => $end_date]);
        $menus = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($menus as $meal) {
            $date = $meal['menu_date'];
            if (!isset($response['menus'][$date])) {
                $response['menus'][$date] = [
                    'meals' => [],
                    'total_calories' => 0
                ];
            }
            $response['menus'][$date]['meals'][] = ['name' => $meal['name']];
            $response['menus'][$date]['total_calories'] += (int)$meal['calories'];
        }

        // Özel günleri çek
        $stmt = $pdo->prepare("SELECT event_date, message FROM special_days WHERE event_date BETWEEN :start_date AND :end_date");
        $stmt->execute([':start_date' => $start_date, ':end_date' => $end_date]);
        $special_days = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($special_days as $day) {
            $response['special_days'][$day['event_date']] = $day['message'];
        }

        echo json_encode($response);

    } catch (PDOException $e) {
        http_response_code(500);
        error_log("get_monthly_data error: " . $e->getMessage());
        die(json_encode(['success' => false, 'message' => 'Aylık menü verileri alınırken sunucu hatası oluştu.']));
    }
}

/**
 * Tek bir günün detaylı menü veya özel gün bilgisini çeker.
 */
function get_single_day_data($pdo, $date)
{
    $response = [
        'is_special' => false,
        'message' => '',
        'menu' => []
    ];

    try {
        $stmt = $pdo->prepare("SELECT message FROM special_days WHERE event_date = ?");
        $stmt->execute([$date]);
        $special_day = $stmt->fetch();

        if ($special_day) {
            $response['is_special'] = true;
            $response['message'] = $special_day['message'];
        } else {
            $stmt = $pdo->prepare(
                "SELECT ml.id, ml.name, ml.calories, ml.ingredients, ml.is_vegetarian, ml.is_gluten_free, ml.has_allergens
                 FROM menus m
                 JOIN meals ml ON m.meal_id = ml.id
                 WHERE m.menu_date = ?
                 ORDER BY ml.id"
            );
            $stmt->execute([$date]);
            $response['menu'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        echo json_encode($response);

    } catch (PDOException $e) {
        http_response_code(500);
        error_log("get_single_day_data error: " . $e->getMessage());
        die(json_encode(['success' => false, 'message' => 'Günlük menü detayı alınırken sunucu hatası oluştu.']));
    }
}
