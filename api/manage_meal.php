<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Yetkisiz erişim.']);
    exit;
}

require_once '../db_connect.php';
require_once '../includes/functions.php';

$admin_username = $_SESSION['admin_username'] ?? 'Bilinmeyen Admin';

$action = $_REQUEST['action'] ?? '';

try {
    switch ($action) {
        case 'get_all':
            $stmt = $pdo->query("SELECT id, name, calories FROM meals ORDER BY name");
            echo json_encode($stmt->fetchAll());
            break;

        case 'get_single':
            $id = $_GET['id'] ?? 0;
            $stmt = $pdo->prepare("SELECT * FROM meals WHERE id = ?");
            $stmt->execute([$id]);
            echo json_encode($stmt->fetch());
            break;

        case 'create':
            // Checkbox'lardan gelen değerleri işle (eğer işaretliyse 'on' gelir, değilse hiç gelmez)
            $is_vegetarian = isset($_POST['is_vegetarian']) ? 1 : 0;
            $is_gluten_free = isset($_POST['is_gluten_free']) ? 1 : 0;
            $has_allergens = isset($_POST['has_allergens']) ? 1 : 0;

            $stmt = $pdo->prepare(
                "INSERT INTO meals (name, calories, ingredients, is_vegetarian, is_gluten_free, has_allergens) 
                 VALUES (?, ?, ?, ?, ?, ?)"
            );
            $stmt->execute([
                $_POST['name'], 
                $_POST['calories'] ?: null, 
                $_POST['ingredients'], 
                $is_vegetarian, 
                $is_gluten_free, 
                $has_allergens
            ]);
            $new_id = $pdo->lastInsertId();
            create_log($pdo, $admin_username, 'YEMEK OLUŞTURULDU', "ID: $new_id, Ad: {$_POST['name']}");
            echo json_encode(['success' => true, 'message' => 'Yemek başarıyla eklendi.']);
            break;

        case 'update':
            $is_vegetarian = isset($_POST['is_vegetarian']) ? 1 : 0;
            $is_gluten_free = isset($_POST['is_gluten_free']) ? 1 : 0;
            $has_allergens = isset($_POST['has_allergens']) ? 1 : 0;

            $stmt = $pdo->prepare(
                "UPDATE meals SET name = ?, calories = ?, ingredients = ?, 
                 is_vegetarian = ?, is_gluten_free = ?, has_allergens = ? 
                 WHERE id = ?"
            );
            $stmt->execute([
                $_POST['name'], 
                $_POST['calories'] ?: null, 
                $_POST['ingredients'], 
                $is_vegetarian, 
                $is_gluten_free, 
                $has_allergens, 
                $_POST['meal_id']
            ]);
            create_log($pdo, $admin_username, 'YEMEK GÜNCELLENDİ', "ID: {$_POST['meal_id']}, Ad: {$_POST['name']}");
            echo json_encode(['success' => true, 'message' => 'Yemek başarıyla güncellendi.']);
            break;

        case 'delete':
            $id = $_POST['id'] ?? 0;
            // Silmeden önce yemeğin adını alalım ki log daha anlamlı olsun
            $stmt_get_name = $pdo->prepare("SELECT name FROM meals WHERE id = ?");
            $stmt_get_name->execute([$id]);
            $meal_name = $stmt_get_name->fetchColumn();

            $stmt = $pdo->prepare("DELETE FROM meals WHERE id = ?");
            $stmt->execute([$id]);
            create_log($pdo, $admin_username, 'YEMEK SİLİNDİ', "ID: $id, Ad: $meal_name");
            echo json_encode(['success' => true, 'message' => 'Yemek başarıyla silindi.']);
            break;

        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Geçersiz eylem.']);
            break;
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Veritabanı hatası: ' . $e->getMessage()]);
}
