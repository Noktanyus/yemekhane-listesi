<?php

require_once __DIR__ . '/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die(json_encode(['success' => false, 'message' => 'Geçersiz istek metodu.']));
}

if (!defined('IS_MOBILE_API_CALL')) {
    // Bu API'nin sadece adminler tarafından kullanılabilmesini sağla
    if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
        http_response_code(403);
        die(json_encode(['success' => false, 'message' => 'Yetkisiz erişim.']));
    }
    verify_csrf_token_and_exit();
}

$date = $_POST['menu_date'] ?? null;
$is_special_day = isset($_POST['is_special_day']);
$special_day_message = $_POST['special_day_message'] ?? '';
$meal_names = $_POST['meal_names'] ?? [];

if (empty($date)) {
    http_response_code(400);
    die(json_encode(['success' => false, 'message' => 'Tarih alanı zorunludur.']));
}

try {
    $pdo->beginTransaction();

    // Önceki kayıtları temizle
    $pdo->prepare("DELETE FROM menus WHERE menu_date = ?")->execute([$date]);
    $pdo->prepare("DELETE FROM special_days WHERE event_date = ?")->execute([$date]);

    if ($is_special_day) {
        if (empty($special_day_message)) {
            throw new Exception("Özel gün mesajı boş olamaz.");
        }
        $stmt = $pdo->prepare("INSERT INTO special_days (event_date, message) VALUES (?, ?)");
        $stmt->execute([$date, $special_day_message]);
        $log_details = "{$date} tarihi özel gün olarak ayarlandı: {$special_day_message}";
    } else {
        $stmt_get_meal = $pdo->prepare("SELECT id FROM meals WHERE name = ?");
        $stmt_add_menu = $pdo->prepare("INSERT INTO menus (menu_date, meal_id) VALUES (?, ?)");

        $inserted_meals = [];
        foreach ($meal_names as $meal_name) {
            if (empty(trim($meal_name))) {
                continue;
            }

            $stmt_get_meal->execute([$meal_name]);
            $meal_id = $stmt_get_meal->fetchColumn();

            if (!$meal_id) {
                continue;
            }

            try {
                $stmt_add_menu->execute([$date, $meal_id]);
                $inserted_meals[] = $meal_name;
            } catch (PDOException $e) {
                if ($e->getCode() != '23000') { // Sadece duplikasyon hatası değilse işlemi durdur
                    throw $e; // Diğer hataları ana catch bloğuna fırlat
                }
                // Duplikasyon hatasını yoksay ve devam et (veya logla)
            }
        }
        $log_details = "{$date} tarihi için menü güncellendi: " . implode(', ', $inserted_meals);
    }

    log_action('menu_update', $admin_username, $log_details);

    $pdo->commit();

    echo json_encode(['success' => true, 'message' => 'Tarih başarıyla kaydedildi.']);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    error_log("Manage Date Error: " . $e->getMessage());
    die(json_encode(['success' => false, 'message' => 'İşlem sırasında bir hata oluştu: ' . $e->getMessage()]));
}
