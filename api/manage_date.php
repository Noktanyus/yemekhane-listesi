<?php
session_start();
header('Content-Type: application/json');

// Güvenlik: Sadece giriş yapmış adminler bu API'yi kullanabilir
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Yetkisiz erişim. Lütfen giriş yapın.']);
    exit;
}

require_once '../db_connect.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Geçersiz istek metodu.']);
    exit;
}

$date = $_POST['menu_date'] ?? null;
$is_special_day = isset($_POST['is_special_day']);
$special_day_message = $_POST['special_day_message'] ?? null;
$meal_ids = $_POST['meal_ids'] ?? [];

if (empty($date)) {
    echo json_encode(['success' => false, 'message' => 'Tarih seçimi zorunludur.']);
    exit;
}

// Veritabanı işlemlerini başlat
$pdo->beginTransaction();

try {
    // 1. Bu tarihe ait tüm eski kayıtları temizle
    $stmt = $pdo->prepare("DELETE FROM menus WHERE menu_date = ?");
    $stmt->execute([$date]);

    $stmt = $pdo->prepare("DELETE FROM special_days WHERE event_date = ?");
    $stmt->execute([$date]);

    // 2. Yeni veriyi ekle
    if ($is_special_day) {
        // Bu bir özel gün
        if (empty($special_day_message)) {
            throw new Exception('Özel gün mesajı boş olamaz.');
        }
        $stmt = $pdo->prepare("INSERT INTO special_days (event_date, message) VALUES (?, ?)");
        $stmt->execute([$date, $special_day_message]);
        $message = 'Özel gün başarıyla kaydedildi.';
    } else {
        // Bu bir normal menü günü
        $meal_ids = $_POST['meal_ids'] ?? [];
        
        // Gelen yemek ID'lerinde duplikasyon varsa temizle. Bu, "Duplicate entry" hatasını önler.
        $unique_meal_ids = array_unique(array_filter($meal_ids, fn($id) => !empty($id) && is_numeric($id)));
        
        if (empty($unique_meal_ids)) {
            // Eğer ID gelmediyse, belki yemek isimleri gelmiştir (autocomplete için)
            $meal_names = $_POST['meal_names'] ?? [];
            $unique_meal_names = array_unique(array_filter($meal_names, fn($name) => !empty(trim($name))));

            if (empty($unique_meal_names)) {
                throw new Exception('En az bir geçerli yemek seçmelisiniz veya eklemelisiniz.');
            }

            // İsimlerden ID'leri bul
            $placeholders = rtrim(str_repeat('?,', count($unique_meal_names)), ',');
            $stmt_find_ids = $pdo->prepare("SELECT id FROM meals WHERE name IN ($placeholders)");
            $stmt_find_ids->execute($unique_meal_names);
            $unique_meal_ids = $stmt_find_ids->fetchAll(PDO::FETCH_COLUMN);

            if (count($unique_meal_ids) !== count($unique_meal_names)) {
                throw new Exception('Gönderilen yemeklerden bazıları sistemde bulunamadı. Lütfen önce Yemek Yönetimi sayfasından ekleyin.');
            }
        }
        
        $stmt = $pdo->prepare("INSERT INTO menus (menu_date, meal_id) VALUES (?, ?)");
        foreach ($unique_meal_ids as $meal_id) {
            $stmt->execute([$date, $meal_id]);
        }
        $message = 'Menü başarıyla kaydedildi.';
    }

    // 3. İşlemi onayla
    $pdo->commit();
    echo json_encode(['success' => true, 'message' => $message]);

} catch (Exception $e) {
    // Hata olursa geri al
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => 'Bir hata oluştu: ' . $e->getMessage()]);
}
