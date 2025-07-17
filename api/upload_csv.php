<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Yetkisiz erişim.']);
    exit;
}

require_once '../db_connect.php';
require_once '../includes/functions.php';

$action = $_POST['action'] ?? 'analyze';

if ($action === 'analyze') {
    handle_analysis();
} elseif ($action === 'commit') {
    handle_commit();
} else {
    echo json_encode(['success' => false, 'message' => 'Geçersiz eylem.']);
}

function handle_analysis() {
    if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'message' => 'Dosya yükleme hatası: ' . ($_FILES['csv_file']['error'] ?? 'Bilinmiyor')]);
        exit;
    }

    // Karakter kodlama sorunlarını çözmek için locale ayarı
    setlocale(LC_ALL, 'tr_TR.UTF-8', 'tr.UTF-8', 'Turkish');

    $file_path = $_FILES['csv_file']['tmp_name'];
    $file_handle = fopen($file_path, 'r');
    if (!$file_handle) {
        echo json_encode(['success' => false, 'message' => 'Yüklenen dosya açılamadı.']);
        exit;
    }

    // UTF-8 BOM'u kontrol et ve atla. Bu, fgetcsv'nin doğru okumasını sağlar.
    $bom = "\xEF\xBB\xBF";
    if (fgets($file_handle, 4) !== $bom) {
        // BOM bulunamadı, dosya işaretçisini başa sar.
        rewind($file_handle);
    }

    global $pdo;
    $preview_data = [];
    $all_meal_names = array_flip(array_map('strtolower', $pdo->query("SELECT name FROM meals")->fetchAll(PDO::FETCH_COLUMN)));
    
    fgetcsv($file_handle, 0, ';'); // Başlık satırını atla
    $row_number = 1;

    while (($row = fgetcsv($file_handle, 0, ';')) !== false) {
        $row_number++;
        $date_str = trim($row[0] ?? '');
        $is_special = !empty(trim($row[7] ?? ''));
        $day_data = ['original_date' => $date_str, 'date' => null, 'is_special' => $is_special, 'meals' => [], 'error' => null];

        if (empty($date_str)) {
            $day_data['error'] = 'Tarih sütunu boş.';
            $preview_data[] = $day_data;
            continue;
        }

        try {
            $date = new DateTime(str_replace('.', '-', $date_str));
            $day_data['date'] = $date->format('Y-m-d');
        } catch (Exception $e) {
            $day_data['error'] = 'Geçersiz tarih formatı.';
            $preview_data[] = $day_data;
            continue;
        }

        if ($is_special) {
            $day_data['meals'][] = ['name' => trim($row[1] ?? ''), 'is_new' => false];
        } else {
            for ($i = 1; $i <= 6; $i++) {
                $meal_name = trim($row[$i] ?? '');
                if (!empty($meal_name)) {
                    $is_new = !isset($all_meal_names[strtolower($meal_name)]);
                    $day_data['meals'][] = ['name' => $meal_name, 'is_new' => $is_new];
                }
            }
        }
        $preview_data[] = $day_data;
    }
    fclose($file_handle);
    echo json_encode(['success' => true, 'data' => $preview_data]);
}

function handle_commit() {
    $data_to_commit = json_decode($_POST['data'], true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        echo json_encode(['success' => false, 'message' => 'Geçersiz veri formatı.']);
        exit;
    }

    global $pdo;
    $admin_username = $_SESSION['admin_username'] ?? 'Bilinmeyen Admin';
    $processed_count = 0;
    $error_count = 0;

    $pdo->beginTransaction();
    try {
        foreach ($data_to_commit as $day_data) {
            if (empty($day_data['date'])) {
                $error_count++;
                continue;
            }
            
            $sql_date = $day_data['date'];
            
            // Eski kayıtları temizle
            $pdo->prepare("DELETE FROM menus WHERE menu_date = ?")->execute([$sql_date]);
            $pdo->prepare("DELETE FROM special_days WHERE event_date = ?")->execute([$sql_date]);

            if ($day_data['is_special']) {
                $message = $day_data['meals'][0]['name'] ?? 'Özel gün';
                $stmt = $pdo->prepare("INSERT INTO special_days (event_date, message) VALUES (?, ?)");
                $stmt->execute([$sql_date, $message]);
            } else {
                foreach ($day_data['meals'] as $meal) {
                    $meal_name = $meal['name'];
                    if (empty($meal_name)) continue;

                    $stmt = $pdo->prepare("SELECT id FROM meals WHERE name = ?");
                    $stmt->execute([$meal_name]);
                    $meal_id = $stmt->fetchColumn();

                    if (!$meal_id) {
                        // Yeni yemeği detaylarıyla ekle
                        $stmt_insert = $pdo->prepare(
                            "INSERT INTO meals (name, calories, ingredients, is_vegetarian, is_gluten_free, has_allergens) 
                             VALUES (:name, :calories, :ingredients, :is_vegetarian, :is_gluten_free, :has_allergens)"
                        );
                        $stmt_insert->execute([
                            ':name' => $meal_name,
                            ':calories' => $meal['calories'] ?? null,
                            ':ingredients' => $meal['ingredients'] ?? null,
                            ':is_vegetarian' => $meal['is_vegetarian'] ?? 0,
                            ':is_gluten_free' => $meal['is_gluten_free'] ?? 0,
                            ':has_allergens' => $meal['has_allergens'] ?? 0,
                        ]);
                        $meal_id = $pdo->lastInsertId();
                    }
                    
                    $stmt_menu = $pdo->prepare("INSERT INTO menus (menu_date, meal_id) VALUES (?, ?)");
                    $stmt_menu->execute([$sql_date, $meal_id]);
                }
            }
            $processed_count++;
        }

        $pdo->commit();
        create_log($pdo, $admin_username, 'CSV İLE TOPLU GÜNCELLEME', "$processed_count günün menüsü başarıyla işlendi.");
        echo json_encode(['success' => true, 'message' => "$processed_count günün menüsü başarıyla kaydedildi."]);

    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Veritabanı hatası: ' . $e->getMessage()]);
    }
}