<?php

require_once __DIR__ . '/bootstrap.php';

if (!defined('IS_MOBILE_API_CALL')) {
    // Bu API'nin sadece adminler tarafından kullanılabilmesini sağla
    if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
        http_response_code(403);
        die(json_encode(['success' => false, 'message' => 'Yetkisiz erişim.']));
    }
}

// Metot ve Eylem Belirleme
$method = $_SERVER['REQUEST_METHOD'];
$action = $_REQUEST['action'] ?? ''; // GET veya POST olabilir

try {
    // GET işlemleri için kontrol
    if ($method === 'GET') {
        switch ($action) {
            case 'get_single':
                $id = $_GET['id'] ?? null;
                if (!$id) {
                    throw new Exception('Yemek ID\'si bulunamadı.');
                }
                $stmt = $pdo->prepare("SELECT * FROM meals WHERE id = ?");
                $stmt->execute([$id]);
                echo json_encode($stmt->fetch(PDO::FETCH_ASSOC));
                break;

            case 'get_all':
                $stmt = $pdo->query("SELECT id, name, calories, is_vegetarian, is_gluten_free, has_allergens FROM meals ORDER BY name");
                echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
                break;

            default:
                throw new Exception('Geçersiz GET eylemi.');
        }
    }
    // POST işlemleri için kontrol
    elseif ($method === 'POST') {
        if (!defined('IS_MOBILE_API_CALL')) {
            verify_csrf_token_and_exit();
        }

        switch ($action) {
            case 'create':
            case 'update':
                $name = trim($_POST['name'] ?? '');
                if (empty($name)) {
                    throw new Exception('Yemek adı boş olamaz.');
                }

                $params = [
                    'name' => $name,
                    'calories' => empty($_POST['calories']) ? null : (int)$_POST['calories'],
                    'ingredients' => $_POST['ingredients'] ?? null,
                    'is_vegetarian' => isset($_POST['is_vegetarian']) ? 1 : 0,
                    'is_gluten_free' => isset($_POST['is_gluten_free']) ? 1 : 0,
                    'has_allergens' => isset($_POST['has_allergens']) ? 1 : 0,
                ];

                try {
                    if ($action === 'update') {
                        $id = $_POST['meal_id'] ?? null;
                        if (!$id) {
                            throw new Exception('Güncellenecek yemek ID\'si bulunamadı.');
                        }
                        $sql = "UPDATE meals SET name = :name, calories = :calories, ingredients = :ingredients, is_vegetarian = :is_vegetarian, is_gluten_free = :is_gluten_free, has_allergens = :has_allergens WHERE id = :id";
                        $params['id'] = $id;
                        $log_details = "Yemek güncellendi: {$name}";
                    } else {
                        $sql = "INSERT INTO meals (name, calories, ingredients, is_vegetarian, is_gluten_free, has_allergens) VALUES (:name, :calories, :ingredients, :is_vegetarian, :is_gluten_free, :has_allergens)";
                        $log_details = "Yeni yemek eklendi: {$name}";
                    }

                    $stmt = $pdo->prepare($sql);
                    $stmt->execute($params);

                    log_action('meal_management', $admin_username, $log_details);

                    echo json_encode(['success' => true, 'message' => 'Yemek başarıyla kaydedildi.']);

                } catch (PDOException $e) {
                    if ($e->getCode() == '23000') {
                        throw new Exception('Bu isimde bir yemek zaten mevcut.');
                    }
                    throw $e; // Diğer veritabanı hatalarını ana bloğa fırlat
                }
                break;

            case 'delete':
                $id = $_POST['id'] ?? null;
                if (!$id) {
                    throw new Exception('Silinecek yemek ID\'si bulunamadı.');
                }

                $stmt_get_name = $pdo->prepare("SELECT name FROM meals WHERE id = ?");
                $stmt_get_name->execute([$id]);
                $meal_name = $stmt_get_name->fetchColumn();

                $stmt = $pdo->prepare("DELETE FROM meals WHERE id = ?");
                $stmt->execute([$id]);

                log_action('meal_management', $admin_username, "Yemek silindi: {$meal_name} (ID: {$id})");

                echo json_encode(['success' => true, 'message' => 'Yemek başarıyla silindi.']);
                break;

            default:
                throw new Exception('Geçersiz POST eylemi.');
        }
    }
    // Diğer metotlara izin verme
    else {
        http_response_code(405);
        throw new Exception('Geçersiz istek metodu.');
    }
} catch (Exception $e) {
    http_response_code(400); // Genel olarak client-side hatalar için 400 daha uygun
    error_log("Manage Meal Error: " . $e->getMessage());
    die(json_encode(['success' => false, 'message' => $e->getMessage()]));
}
