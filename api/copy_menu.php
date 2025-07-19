<?php
header('Content-Type: application/json; charset=utf-8');
session_start();
require_once '../db_connect.php';
require_once '../includes/functions.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Yetkisiz erişim.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Geçersiz istek metodu.']);
    exit;
}

$source_date = $_POST['source_date'] ?? null;
$target_date = $_POST['target_date'] ?? null;

if (empty($source_date) || empty($target_date)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Kaynak ve hedef tarihler boş olamaz.']);
    exit;
}

if ($source_date === $target_date) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Kaynak ve hedef tarihler aynı olamaz.']);
    exit;
}

try {
    $pdo->beginTransaction();

    // 1. Kaynak tarihteki yemek ID'lerini al
    $stmt_get = $pdo->prepare("SELECT meal_id FROM menus WHERE menu_date = ?");
    $stmt_get->execute([$source_date]);
    $meal_ids = $stmt_get->fetchAll(PDO::FETCH_COLUMN);

    if (empty($meal_ids)) {
        $pdo->rollBack();
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Kaynak tarihte kopyalanacak menü bulunamadı.']);
        exit;
    }

    // 2. Hedef tarihteki mevcut menüyü (varsa) sil
    $stmt_delete = $pdo->prepare("DELETE FROM menus WHERE menu_date = ?");
    $stmt_delete->execute([$target_date]);
    
    // Hedef tarihin özel gün olup olmadığını kontrol et ve gerekirse sil
    $stmt_delete_special = $pdo->prepare("DELETE FROM special_days WHERE event_date = ?");
    $stmt_delete_special->execute([$target_date]);

    // 3. Yemek ID'lerini hedef tarihe ekle
    $stmt_insert = $pdo->prepare("INSERT INTO menus (menu_date, meal_id) VALUES (?, ?)");
    foreach ($meal_ids as $meal_id) {
        $stmt_insert->execute([$target_date, $meal_id]);
    }

    $pdo->commit();

    // Loglama
    $admin_username = $_SESSION['admin_username'] ?? 'Bilinmeyen';
    $summary = "$source_date menüsü $target_date tarihine kopyalandı.";
    log_action('menu_copy', $admin_username, $summary);

    echo json_encode(['success' => true, 'message' => 'Menü başarıyla kopyalandı!']);

} catch (PDOException $e) {
    $pdo->rollBack();
    http_response_code(500);
    error_log("Menu copy error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Veritabanı hatası nedeniyle menü kopyalanamadı.']);
}
