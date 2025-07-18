<?php
require_once __DIR__ . '/bootstrap.php';

$action = $_REQUEST['action'] ?? '';

try {
    switch ($action) {
        case 'get_all':
            $stmt = $pdo->query("SELECT id, name, calories FROM meals ORDER BY name");
            echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
            break;

        case 'get_single':
            $id = $_GET['id'] ?? 0;
            $stmt = $pdo->prepare("SELECT * FROM meals WHERE id = ?");
            $stmt->execute([$id]);
            echo json_encode($stmt->fetch(PDO::FETCH_ASSOC));
            break;

        case 'create':
            $is_vegetarian = isset($_POST['is_vegetarian']) ? 1 : 0;
            $is_gluten_free = isset($_POST['is_gluten_free']) ? 1 : 0;
            $has_allergens = isset($_POST['has_allergens']) ? 1 : 0;
            $meal_name = trim($_POST['name']);

            $stmt = $pdo->prepare(
                "INSERT INTO meals (name, calories, ingredients, is_vegetarian, is_gluten_free, has_allergens) 
                 VALUES (?, ?, ?, ?, ?, ?)"
            );
            $stmt->execute([
                $meal_name, 
                $_POST['calories'] ?: null, 
                $_POST['ingredients'], 
                $is_vegetarian, 
                $is_gluten_free, 
                $has_allergens
            ]);
            $new_id = $pdo->lastInsertId();
            create_log($pdo, $admin_username, 'MEAL_CREATE', 'Yeni Yemek Eklendi', "ID: $new_id, Ad: \"$meal_name\"");
            echo json_encode(['success' => true, 'message' => 'Yemek başarıyla eklendi.']);
            break;

        case 'update':
            $is_vegetarian = isset($_POST['is_vegetarian']) ? 1 : 0;
            $is_gluten_free = isset($_POST['is_gluten_free']) ? 1 : 0;
            $has_allergens = isset($_POST['has_allergens']) ? 1 : 0;
            $meal_name = trim($_POST['name']);
            $meal_id = $_POST['meal_id'];

            $stmt = $pdo->prepare(
                "UPDATE meals SET name = ?, calories = ?, ingredients = ?, 
                 is_vegetarian = ?, is_gluten_free = ?, has_allergens = ? 
                 WHERE id = ?"
            );
            $stmt->execute([
                $meal_name, 
                $_POST['calories'] ?: null, 
                $_POST['ingredients'], 
                $is_vegetarian, 
                $is_gluten_free, 
                $has_allergens, 
                $meal_id
            ]);
            create_log($pdo, $admin_username, 'MEAL_UPDATE', 'Yemek Güncellendi', "ID: $meal_id, Yeni Ad: \"$meal_name\"");
            echo json_encode(['success' => true, 'message' => 'Yemek başarıyla güncellendi.']);
            break;

        case 'delete':
            $id = $_POST['id'] ?? 0;
            $stmt_get_name = $pdo->prepare("SELECT name FROM meals WHERE id = ?");
            $stmt_get_name->execute([$id]);
            $meal_name_to_delete = $stmt_get_name->fetchColumn();

            $stmt = $pdo->prepare("DELETE FROM meals WHERE id = ?");
            $stmt->execute([$id]);
            create_log($pdo, $admin_username, 'MEAL_DELETE', 'Yemek Silindi', "ID: $id, Ad: \"$meal_name_to_delete\"");
            echo json_encode(['success' => true, 'message' => 'Yemek başarıyla silindi.']);
            break;

        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Geçersiz eylem.']);
            break;
    }
} catch (PDOException $e) {
    http_response_code(500);
    error_log("Yemek yönetimi hatası: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Veritabanı hatası oluştu.']);
}
