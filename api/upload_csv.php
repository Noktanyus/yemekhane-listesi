<?php

require_once __DIR__ . '/bootstrap.php';

// --- HELPER FUNCTIONS ---
function validate_date($date, $format = 'd.m.Y')
{
    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) === $date;
}

function handle_file_upload()
{
    if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Dosya yüklenirken bir hata oluştu veya dosya seçilmedi.');
    }

    // --- GÜVENLİK KONTROLLERİ ---
    $file = $_FILES['csv_file'];

    // 1. Dosya Boyutu Kontrolü (örn: 5MB limit)
    $max_size = 5 * 1024 * 1024; // 5 MB
    if ($file['size'] > $max_size) {
        throw new Exception('Dosya boyutu çok büyük. Lütfen 5MB\'dan küçük bir dosya yükleyin.');
    }

    // 2. MIME Tipi Kontrolü
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime_type = $finfo->file($file['tmp_name']);
    $allowed_mime_types = ['text/csv', 'text/plain', 'application/csv'];
    if (!in_array($mime_type, $allowed_mime_types, true)) {
        throw new Exception('Geçersiz dosya formatı. Lütfen sadece .csv uzantılı dosya yükleyin.');
    }

    // 3. Dosya Uzantısı Kontrolü
    $file_name = basename($file['name']);
    $extension = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
    if ($extension !== 'csv') {
        throw new Exception('Geçersiz dosya uzantısı. Lütfen sadece .csv uzantılı dosya yükleyin.');
    }
    // --- GÜVENLİK KONTROLLERİ SONU ---

    $temp_dir = __DIR__ . '/../uploads/temp_csv/';
    if (!is_dir($temp_dir)) {
        mkdir($temp_dir, 0755, true);
    }

    // Güvenli dosya adı oluşturma
    $safe_filename = uniqid('csv_', true) . '.csv';
    $temp_file_path = $temp_dir . $safe_filename;

    if (!move_uploaded_file($file['tmp_name'], $temp_file_path)) {
        throw new Exception('Dosya sunucuda geçici olarak saklanamadı.');
    }
    return $temp_file_path;
}

function analyze_csv($file_path, $pdo)
{
    $file_content = file_get_contents($file_path);
    if (substr($file_content, 0, 3) === "\xEF\xBB\xBF") {
        $file_content = substr($file_content, 3);
        file_put_contents($file_path, $file_content);
    }

    $preview = [];
    $errors = [];
    $row_num = 1;

    $stmt_check_meal = $pdo->prepare("SELECT id FROM meals WHERE name = ?");

    if (($handle = fopen($file_path, "r")) !== false) {
        fgetcsv($handle, 2000, ";"); // Skip header
        while (($data = fgetcsv($handle, 2000, ";")) !== false) {
            $row_num++;
            if (count($data) < 2) {
                continue;
            } // Boş satırları atla

            $data = array_map('trim', $data);
            $date_str = $data[0];

            if (!validate_date($date_str)) {
                $errors[] = "{$row_num}. satırda geçersiz tarih formatı: '{$date_str}'. (GG.AA.YYYY olmalı)";
                continue;
            }

            $is_special = !empty($data[7]) && (int)$data[7] === 1;
            $meals_in_row = [];

            if ($is_special) {
                if (empty($data[1])) {
                    $errors[] = "{$row_num}. satır özel gün ama açıklama (Yemek 1 sütunu) boş.";
                }
                $meals_in_row[] = ['name' => $data[1], 'is_new' => false];
            } else {
                for ($i = 1; $i <= 6; $i++) {
                    if (!empty($data[$i])) {
                        $stmt_check_meal->execute([$data[$i]]);
                        $exists = $stmt_check_meal->fetchColumn();
                        $meals_in_row[] = ['name' => $data[$i], 'is_new' => !$exists];
                    }
                }
            }

            $preview[] = [
                'date' => $date_str,
                'is_special' => $is_special,
                'meals' => $meals_in_row
            ];
        }
        fclose($handle);
    }

    if (!empty($errors)) {
        unlink($file_path);
        throw new Exception(implode('<br>', $errors));
    }

    return $preview;
}

function commit_data($data, $pdo, $admin_username)
{
    $pdo->beginTransaction();

    $stmt_get_meal = $pdo->prepare("SELECT id FROM meals WHERE name = ?");
    $stmt_add_meal = $pdo->prepare("INSERT INTO meals (name) VALUES (?)");
    $stmt_delete_menu = $pdo->prepare("DELETE FROM menus WHERE menu_date = ?");
    $stmt_delete_special = $pdo->prepare("DELETE FROM special_days WHERE event_date = ?");
    $stmt_add_menu = $pdo->prepare("INSERT INTO menus (menu_date, meal_id) VALUES (?, ?)");
    $stmt_add_special = $pdo->prepare("INSERT INTO special_days (event_date, message) VALUES (?, ?)");

    $imported_rows = 0;
    foreach ($data as $day) {
        $date_sql = DateTime::createFromFormat('d.m.Y', $day['date'])->format('Y-m-d');

        // Önceki kayıtları temizle
        $stmt_delete_menu->execute([$date_sql]);
        $stmt_delete_special->execute([$date_sql]);

        if ($day['is_special']) {
            $stmt_add_special->execute([$date_sql, $day['meals'][0]['name']]);
        } else {
            foreach ($day['meals'] as $meal) {
                $stmt_get_meal->execute([$meal['name']]);
                $meal_id = $stmt_get_meal->fetchColumn();
                if (!$meal_id) {
                    $stmt_add_meal->execute([$meal['name']]);
                    $meal_id = $pdo->lastInsertId();
                }
                $stmt_add_menu->execute([$date_sql, $meal_id]);
            }
        }
        $imported_rows++;
    }

    $pdo->commit();
    log_action('csv_upload', $admin_username, "CSV dosyasından {$imported_rows} günlük menü içe aktarıldı.");
    return $imported_rows;
}


// --- MAIN LOGIC ---
header('Content-Type: application/json');
$action = $_POST['action'] ?? '';

try {
    if ($action === 'analyze') {
        $temp_file_path = handle_file_upload();
        $preview_data = analyze_csv($temp_file_path, $pdo);
        // Analiz başarılıysa, geçici dosyanın yolunu session'a kaydet
        $_SESSION['csv_temp_file'] = $temp_file_path;
        echo json_encode(['success' => true, 'data' => $preview_data]);

    } elseif ($action === 'commit') {
        if (!isset($_SESSION['csv_temp_file']) || !file_exists($_SESSION['csv_temp_file'])) {
            throw new Exception('İşlem zaman aşımına uğradı veya geçici dosya bulunamadı. Lütfen tekrar yükleyin.');
        }
        $temp_file_path = $_SESSION['csv_temp_file'];

        // Analizi tekrar yapıp veriyi al (güvenlik için)
        $data_to_commit = analyze_csv($temp_file_path, $pdo);
        $count = commit_data($data_to_commit, $pdo, $admin_username);

        // İşlem bitince geçici dosyayı ve session'ı temizle
        unlink($temp_file_path);
        unset($_SESSION['csv_temp_file']);

        echo json_encode(['success' => true, 'message' => "{$count} günlük menü başarıyla veritabanına kaydedildi."]);

    } else {
        throw new Exception('Geçersiz eylem.');
    }
} catch (Exception $e) {
    // Hata durumunda geçici dosyayı sil
    if (!empty($_SESSION['csv_temp_file']) && file_exists($_SESSION['csv_temp_file'])) {
        unlink($_SESSION['csv_temp_file']);
        unset($_SESSION['csv_temp_file']);
    }
    http_response_code(500);
    error_log("CSV Upload Error: " . $e->getMessage());
    die(json_encode(['success' => false, 'message' => $e->getMessage()]));
}
