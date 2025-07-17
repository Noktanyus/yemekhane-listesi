<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../db_connect.php';

// Tek bir tarih sorgusu için (detaylı bilgi)
if (isset($_GET['date'])) {
    $date = $_GET['date'];
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
            $response['is_special'] = false; // Menü varsa bile bunun özel gün olmadığını belirt
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
    } catch (PDOException $e) {
        http_response_code(500);
        // Hata mesajını logla, kullanıcıya genel bir mesaj göster
        error_log("get_menu_events.php (single date) error: " . $e->getMessage());
        echo json_encode(['error' => 'Sunucu hatası oluştu.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}

// Ana takvim için (genel bakış)
$year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');
$month = isset($_GET['month']) ? (int)$_GET['month'] : date('m');

$calendar_response = [
    'menus' => [],
    'special_days' => []
];

try {
    // Menüleri çek
    $stmt = $pdo->prepare(
        "SELECT m.menu_date, ml.name 
         FROM menus m 
         JOIN meals ml ON m.meal_id = ml.id 
         WHERE YEAR(m.menu_date) = ? AND MONTH(m.menu_date) = ? 
         ORDER BY m.menu_date, ml.id"
    );
    $stmt->execute([$year, $month]);
    $menus = $stmt->fetchAll();
    foreach ($menus as $menu) {
        $calendar_response['menus'][$menu['menu_date']][] = ['name' => $menu['name']];
    }

    // Özel günleri çek
    $stmt = $pdo->prepare("SELECT event_date, message FROM special_days WHERE YEAR(event_date) = ? AND MONTH(event_date) = ?");
    $stmt->execute([$year, $month]);
    $calendar_response['special_days'] = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

} catch (PDOException $e) {
    http_response_code(500);
    error_log("get_menu_events.php (calendar) error: " . $e->getMessage());
    echo json_encode(['error' => 'Sunucu hatası oluştu.'], JSON_UNESCAPED_UNICODE);
    exit;
}

echo json_encode($calendar_response, JSON_UNESCAPED_UNICODE);
