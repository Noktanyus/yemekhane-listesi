<?php
header('Content-Type: application/json');
require_once '../db_connect.php';

$response = [
    'is_special' => false,
    'message' => '',
    'menu' => []
];

// Tek bir tarih sorgusu için
if (isset($_GET['date'])) {
    $date = $_GET['date'];

    try {
        // Önce özel gün mü diye kontrol et
        $stmt = $pdo->prepare("SELECT message FROM special_days WHERE event_date = ?");
        $stmt->execute([$date]);
        $special_day = $stmt->fetch();

        if ($special_day) {
            $response['is_special'] = true;
            $response['message'] = htmlspecialchars($special_day['message']);
        } else {
            // Değilse, menüyü ve tüm detayları çek
            $stmt = $pdo->prepare(
                "SELECT ml.id, ml.name, ml.calories, ml.ingredients
                 FROM menus m
                 JOIN meals ml ON m.meal_id = ml.id
                 WHERE m.menu_date = ?
                 ORDER BY ml.id"
            );
            $stmt->execute([$date]);
            $menu_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
            // Tüm metin verilerini güvenli hale getir
            $response['menu'] = array_map(function($item) {
                $item['name'] = htmlspecialchars($item['name']);
                $item['ingredients'] = htmlspecialchars($item['ingredients']);
                return $item;
            }, $menu_items);
        }
    } catch (PDOException $e) {
        http_response_code(500);
        $response['error'] = 'Veritabanı hatası: ' . $e->getMessage();
    }

    echo json_encode($response);
    exit;
}

// Ana takvim için (orijinal fonksiyonellik korunuyor)
$year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');
$month = isset($_GET['month']) ? (int)$_GET['month'] : date('m');

$calendar_response = [
    'menus' => [],
    'special_days' => []
];

try {
    $stmt = $pdo->prepare("SELECT m.menu_date, ml.name, ml.calories FROM menus m JOIN meals ml ON m.meal_id = ml.id WHERE YEAR(m.menu_date) = ? AND MONTH(m.menu_date) = ? ORDER BY m.menu_date, ml.id");
    $stmt->execute([$year, $month]);
    $menus = $stmt->fetchAll();
    foreach ($menus as $menu) {
        $calendar_response['menus'][$menu['menu_date']][] = ['name' => $menu['name'], 'calories' => $menu['calories']];
    }

    $stmt = $pdo->prepare("SELECT event_date, message FROM special_days WHERE YEAR(event_date) = ? AND MONTH(event_date) = ?");
    $stmt->execute([$year, $month]);
    $calendar_response['special_days'] = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

} catch (PDOException $e) {
    http_response_code(500);
    $calendar_response['error'] = 'Veritabanı hatası: ' . $e->getMessage();
}

echo json_encode($calendar_response);
