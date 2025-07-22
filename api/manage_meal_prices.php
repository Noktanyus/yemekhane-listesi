<?php

require_once 'bootstrap.php';

// Admin kontrolü
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Yetkisiz erişim.']);
    exit;
}

// CSRF token kontrolü
require_once __DIR__ . '/../includes/csrf.php';
if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Güvenlik hatası.']);
    exit;
}

$action = $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'get_all':
            $stmt = $pdo->prepare("
                SELECT id, group_name, description, price, is_active, sort_order, 
                       created_at, updated_at 
                FROM meal_prices 
                ORDER BY sort_order ASC, id ASC
            ");
            $stmt->execute();
            $prices = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode(['success' => true, 'data' => $prices]);
            break;

        case 'add':
            $group_name = trim($_POST['group_name'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $price = floatval($_POST['price'] ?? 0);
            $is_active = isset($_POST['is_active']) ? 1 : 0;
            $sort_order = intval($_POST['sort_order'] ?? 0);

            if (empty($group_name) || $price <= 0) {
                echo json_encode(['success' => false, 'message' => 'Grup adı ve geçerli bir ücret girilmelidir.']);
                break;
            }

            $stmt = $pdo->prepare("
                INSERT INTO meal_prices (group_name, description, price, is_active, sort_order) 
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([$group_name, $description, $price, $is_active, $sort_order]);

            log_action('Meal Price Added', $admin_username, "Yeni ücret eklendi: $group_name - $price TL");
            echo json_encode(['success' => true, 'message' => 'Yemek ücreti başarıyla eklendi.']);
            break;

        case 'update':
            $id = intval($_POST['price_id'] ?? 0);
            $group_name = trim($_POST['group_name'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $price = floatval($_POST['price'] ?? 0);
            $is_active = isset($_POST['is_active']) ? 1 : 0;
            $sort_order = intval($_POST['sort_order'] ?? 0);

            if ($id <= 0 || empty($group_name) || $price <= 0) {
                echo json_encode(['success' => false, 'message' => 'Geçersiz veri.']);
                break;
            }

            $stmt = $pdo->prepare("
                UPDATE meal_prices 
                SET group_name = ?, description = ?, price = ?, is_active = ?, sort_order = ? 
                WHERE id = ?
            ");
            $stmt->execute([$group_name, $description, $price, $is_active, $sort_order, $id]);

            log_action('Meal Price Updated', $admin_username, "Ücret güncellendi: ID $id - $group_name - $price TL");
            echo json_encode(['success' => true, 'message' => 'Yemek ücreti başarıyla güncellendi.']);
            break;

        case 'delete':
            $id = intval($_POST['price_id'] ?? 0);
            
            if ($id <= 0) {
                echo json_encode(['success' => false, 'message' => 'Geçersiz ID.']);
                break;
            }

            // Önce ücret bilgisini al
            $stmt = $pdo->prepare("SELECT group_name FROM meal_prices WHERE id = ?");
            $stmt->execute([$id]);
            $price_info = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$price_info) {
                echo json_encode(['success' => false, 'message' => 'Ücret bulunamadı.']);
                break;
            }

            $stmt = $pdo->prepare("DELETE FROM meal_prices WHERE id = ?");
            $stmt->execute([$id]);

            log_action('Meal Price Deleted', $admin_username, "Ücret silindi: " . $price_info['group_name']);
            echo json_encode(['success' => true, 'message' => 'Yemek ücreti başarıyla silindi.']);
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Geçersiz işlem.']);
            break;
    }

} catch (PDOException $e) {
    error_log("Yemek ücretleri yönetim hatası: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Veritabanı hatası oluştu.']);
}