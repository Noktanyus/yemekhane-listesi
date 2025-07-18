<?php
require_once __DIR__ . '/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['success' => false, 'message' => 'Geçersiz istek metodu.']);
    exit;
}

$date = $_POST['menu_date'] ?? null;
$is_special_day = isset($_POST['is_special_day']);
$special_day_message = $_POST['special_day_message'] ?? null;

if (empty($date)) {
    http_response_code(400); // Bad Request
    echo json_encode(['success' => false, 'message' => 'Tarih seçimi zorunludur.']);
    exit;
}

$pdo->beginTransaction();

try {
    // Bu tarihe ait tüm eski kayıtları temizle
    $stmt_menu = $pdo->prepare("DELETE FROM menus WHERE menu_date = ?");
    $stmt_menu->execute([$date]);
    $deleted_menu_count = $stmt_menu->rowCount();

    $stmt_special = $pdo->prepare("DELETE FROM special_days WHERE event_date = ?");
    $stmt_special->execute([$date]);
    $deleted_special_count = $stmt_special->rowCount();

    $is_update = ($deleted_menu_count + $deleted_special_count) > 0;

    if ($is_special_day) {
        if (empty($special_day_message)) {
            throw new Exception('Özel gün mesajı boş olamaz.');
        }
        $stmt = $pdo->prepare("INSERT INTO special_days (event_date, message) VALUES (?, ?)");
        $stmt->execute([$date, $special_day_message]);
        
        $action_type = $is_update ? 'SPECIAL_DAY_UPDATE' : 'SPECIAL_DAY_CREATE';
        $action_summary = $is_update ? 'Özel Gün Güncellendi' : 'Özel Gün Oluşturuldu';
        $details = "Tarih: $date, Mesaj: \"$special_day_message\"";
        create_log($pdo, $admin_username, $action_type, $action_summary, $details);
        
        $message = 'Özel gün başarıyla kaydedildi.';

    } else {
        $meal_names = $_POST['meal_names'] ?? [];
        $unique_meal_names = array_unique(array_filter($meal_names, fn($name) => !empty(trim($name))));

        if (empty($unique_meal_names)) {
            if ($is_update) {
                $action_type = 'MENU_DELETE';
                $action_summary = 'Tarih Menüsü Temizlendi';
                $details = "Tarih: $date";
                create_log($pdo, $admin_username, $action_type, $action_summary, $details);
                $message = 'Menü başarıyla temizlendi.';
            } else {
                throw new Exception('En az bir geçerli yemek seçmelisiniz.');
            }
        } else {
            $placeholders = rtrim(str_repeat('?,', count($unique_meal_names)), ',');
            $stmt_find_ids = $pdo->prepare("SELECT id, name FROM meals WHERE name IN ($placeholders)");
            $stmt_find_ids->execute($unique_meal_names);
            $found_meals_data = $stmt_find_ids->fetchAll(PDO::FETCH_ASSOC);

            if (count($found_meals_data) !== count($unique_meal_names)) {
                $found_names = array_column($found_meals_data, 'name');
                $missing_meals = array_diff($unique_meal_names, $found_names);
                throw new Exception('Bulunamayan yemekler: ' . implode(', ', $missing_meals));
            }
            
            $stmt = $pdo->prepare("INSERT INTO menus (menu_date, meal_id) VALUES (?, ?)");
            foreach ($found_meals_data as $meal) {
                $stmt->execute([$date, $meal['id']]);
            }

            $action_type = $is_update ? 'MENU_UPDATE' : 'MENU_CREATE';
            $action_summary = $is_update ? 'Tarih Menüsü Güncellendi' : 'Yeni Tarih Menüsü Oluşturuldu';
            $log_meal_names = array_column($found_meals_data, 'name');
            $details = "Tarih: $date, Yemekler: \"" . implode('", "', $log_meal_names) . "\"";
            create_log($pdo, $admin_username, $action_type, $action_summary, $details);

            $message = 'Menü başarıyla kaydedildi.';
        }
    }

    $pdo->commit();
    echo json_encode(['success' => true, 'message' => $message]);

} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(400); // Bad Request veya 500 Internal Server Error daha uygun olabilir
    echo json_encode(['success' => false, 'message' => 'Bir hata oluştu: ' . $e->getMessage()]);
}
