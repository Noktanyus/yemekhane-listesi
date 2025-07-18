<?php
require_once __DIR__ . '/bootstrap.php';

$action = $_POST['action'] ?? '';

// 'analyze' ve 'commit' dışındaki eylemleri engelle
if (!in_array($action, ['analyze', 'commit'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Geçersiz eylem.']);
    exit;
}

try {
    if ($action === 'analyze') {
        handle_analysis($pdo);
    } elseif ($action === 'commit') {
        handle_commit($pdo, $admin_username);
    }
} catch (Throwable $t) {
    http_response_code(500);
    error_log("CSV Upload Error: " . $t->getMessage() . " in " . $t->getFile() . " on line " . $t->getLine());
    echo json_encode([
        'success' => false,
        'message' => 'Sunucu tarafında beklenmedik bir hata oluştu. Lütfen sistem yöneticisine başvurun.'
    ]);
}

function handle_analysis($pdo) {
    if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'message' => 'Dosya yükleme hatası: ' . ($_FILES['csv_file']['error'] ?? 'Bilinmiyor')]);
        exit;
    }

    setlocale(LC_ALL, 'tr_TR.UTF-8', 'tr.UTF-8', 'Turkish');
    $file_path = $_FILES['csv_file']['tmp_name'];
    $file_handle = fopen($file_path, 'r');
    if (!$file_handle) {
        echo json_encode(['success' => false, 'message' => 'Yüklenen dosya açılamadı.']);
        exit;
    }

    $bom = "\xEF\xBB\xBF";
    if (fgets($file_handle, 4) !== $bom) {
        rewind($file_handle);
    }

    $preview_data = [];
    $all_meal_names = array_flip(array_map('strtolower', $pdo->query("SELECT name FROM meals")->fetchAll(PDO::FETCH_COLUMN)));
    
    fgetcsv($file_handle, 0, ';'); // Başlık satırını atla
    $row_number = 1;

    while (($row = fgetcsv($file_handle, 0, ';')) !== false) {
        $row_number++;
        $date_str = trim($row[0] ?? '');
        $is_special = !empty(trim($row[7] ?? ''));
        $day_data = ['original_date' => $date_str, 'date' => null, 'is_special' => $is_special, 'exists' => false, 'meals' => [], 'error' => null];

        if (empty($date_str)) {
            if (count($row) <= 1 && empty(implode('', (array)$row))) continue;
            $day_data['error'] = 'Tarih sütunu boş.';
            $preview_data[] = $day_data;
            continue;
        }

        $sql_date = null;
        if (preg_match('/^(\d{1,2})[.\-\/](\d{1,2})[.\-\/](\d{4})$/', $date_str, $matches)) {
            $day = (int)$matches[1];
            $month = (int)$matches[2];
            $year = (int)$matches[3];
            if (checkdate($month, $day, $year)) {
                $sql_date = sprintf('%04d-%02d-%02d', $year, $month, $day);
            }
        }

        if ($sql_date === null) {
            $day_data['error'] = 'Geçersiz tarih formatı. Lütfen GG.AA.YYYY formatında girin.';
            $preview_data[] = $day_data;
            continue;
        }

        $day_data['date'] = $sql_date;

        $stmt_exists = $pdo->prepare("SELECT (SELECT COUNT(*) FROM menus WHERE menu_date = :dt1) + (SELECT COUNT(*) FROM special_days WHERE event_date = :dt2)");
        $stmt_exists->execute([':dt1' => $sql_date, ':dt2' => $sql_date]);
        $day_data['exists'] = $stmt_exists->fetchColumn() > 0;

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

function handle_commit($pdo, $admin_username) {
    $data_to_commit = json_decode($_POST['data'], true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        echo json_encode(['success' => false, 'message' => 'Geçersiz veri formatı.']);
        exit;
    }

    $processed_count = 0;
    
    $pdo->beginTransaction();
    
    foreach ($data_to_commit as $day_data) {
        if (empty($day_data['date'])) {
            continue;
        }
        
        $sql_date = $day_data['date'];
        
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

                $stmt = $pdo->prepare("SELECT id FROM meals WHERE LOWER(name) = LOWER(?)");
                $stmt->execute([$meal_name]);
                $meal_id = $stmt->fetchColumn();

                if (!$meal_id) {
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
    create_log($pdo, $admin_username, 'CSV_UPLOAD', 'Toplu Menü Yüklendi', "$processed_count günün menüsü CSV dosyası ile başarıyla işlendi.");
    echo json_encode(['success' => true, 'message' => "$processed_count günün menüsü başarıyla kaydedildi."]);
}
