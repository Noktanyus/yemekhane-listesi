<?php
require_once 'db_connect.php';

echo "<pre>"; // Daha okunaklı bir çıktı için

try {
    // 1. Mevcut menü ve özel günleri temizle (isteğe bağlı, tekrar çalıştırılabilirlik için)
    $pdo->exec("DELETE FROM menus");
    echo "Mevcut menüler temizlendi.\n";
    $pdo->exec("DELETE FROM special_days");
    echo "Mevcut özel günler temizlendi.\n";

    // 2. Veritabanındaki tüm yemekleri al
    $stmt = $pdo->query("SELECT id FROM meals");
    $meal_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (count($meal_ids) < 4) {
        die("Hata: Lütfen menü oluşturmak için en az 4 çeşit yemek ekleyin.");
    }
    echo "Veritabanından " . count($meal_ids) . " adet yemek bulundu.\n";

    // 3. Temmuz 2025 için veri oluştur
    $year = 2025;
    $month = 7;
    $days_in_month = cal_days_in_month(CAL_GREGORIAN, $month, $year);
    $menu_insert_count = 0;
    $special_day_insert_count = 0;

    echo "\nTemmuz 2025 için veri oluşturuluyor...\n\n";

    for ($day = 1; $day <= $days_in_month; $day++) {
        $date = new DateTime("$year-$month-$day");
        $day_of_week = (int)$date->format('N'); // 1 (Pazartesi) - 7 (Pazar)
        $current_date_sql = $date->format('Y-m-d');

        // 15 Temmuz'u özel gün olarak ekle
        if ($day === 15) {
            $stmt = $pdo->prepare("INSERT INTO special_days (event_date, message) VALUES (?, ?)");
            $stmt->execute([$current_date_sql, 'Demokrasi ve Milli Birlik Günü']);
            $special_day_insert_count++;
            echo "$current_date_sql: Özel gün eklendi - Demokrasi ve Milli Birlik Günü\n";
            continue; // O gün için menü ekleme
        }

        // Hafta sonlarını (Cumartesi, Pazar) atla
        if ($day_of_week >= 6) {
            echo "$current_date_sql: Hafta sonu, atlanıyor.\n";
            continue;
        }

        // O gün için rastgele 4 yemek seç
        $random_meal_keys = array_rand($meal_ids, 4);
        $selected_meal_ids = array_map(fn($key) => $meal_ids[$key], $random_meal_keys);

        echo "$current_date_sql: Menü oluşturuluyor -> Yemek ID'leri: " . implode(', ', $selected_meal_ids) . "\n";

        // Seçilen yemekleri veritabanına ekle
        $stmt = $pdo->prepare("INSERT INTO menus (menu_date, meal_id) VALUES (?, ?)");
        foreach ($selected_meal_ids as $meal_id) {
            $stmt->execute([$current_date_sql, $meal_id]);
            $menu_insert_count++;
        }
    }

    echo "\n------------------------------------\n";
    echo "Veri oluşturma işlemi tamamlandı!\n";
    echo "Toplam eklenen menü kaydı: $menu_insert_count\n";
    echo "Toplam eklenen özel gün: $special_day_insert_count\n";

} catch (PDOException $e) {
    die("Veritabanı hatası: " . $e->getMessage());
} catch (Exception $e) {
    die("Genel hata: " . $e->getMessage());
}

echo "</pre>";