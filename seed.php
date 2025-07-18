<?php
require_once 'db_connect.php';

echo "<pre style='font-family: monospace; white-space: pre-wrap; background: #f4f4f4; padding: 15px; border-radius: 5px;'>";
echo "Veri tohumlama (seeding) işlemi başlatıldı...\n";

try {
    $pdo->beginTransaction();

    // 1. Adım: Eğer hiç yemek yoksa, örnek yemekleri ekle
    $stmt = $pdo->query("SELECT COUNT(*) FROM meals");
    if ($stmt->fetchColumn() == 0) {
        echo "Yemek tablosu boş. Kapsamlı örnek yemek listesi ekleniyor...\n";
        
        $sample_meals = [
            // Çorbalar
            ['name' => 'Mercimek Çorbası', 'calories' => 150, 'is_vegetarian' => 1],
            ['name' => 'Ezogelin Çorbası', 'calories' => 160, 'is_vegetarian' => 1],
            ['name' => 'Yayla Çorbası', 'calories' => 180],
            ['name' => 'Domates Çorbası', 'calories' => 120, 'is_vegetarian' => 1],
            ['name' => 'Tarhana Çorbası', 'calories' => 170],
            ['name' => 'Brokoli Çorbası', 'calories' => 110, 'is_vegetarian' => 1, 'is_gluten_free' => 1],
            // Ana Yemekler
            ['name' => 'Orman Kebabı', 'calories' => 450, 'has_allergens' => 1],
            ['name' => 'Izgara Tavuk', 'calories' => 350, 'is_gluten_free' => 1],
            ['name' => 'Kuru Fasulye', 'calories' => 400],
            ['name' => 'Nohut Yemeği', 'calories' => 380, 'is_vegetarian' => 1],
            ['name' => 'İzmir Köfte', 'calories' => 420],
            ['name' => 'Tavuk Sote', 'calories' => 380],
            ['name' => 'Sebzeli Güveç', 'calories' => 320, 'is_vegetarian' => 1, 'is_gluten_free' => 1],
            ['name' => 'Fırında Balık', 'calories' => 390, 'is_gluten_free' => 1],
            // Yardımcı Yemekler / Pilavlar / Makarnalar
            ['name' => 'Pirinç Pilavı', 'calories' => 250],
            ['name' => 'Bulgur Pilavı', 'calories' => 220],
            ['name' => 'Fırında Makarna', 'calories' => 300, 'has_allergens' => 1],
            ['name' => 'Patates Püresi', 'calories' => 180, 'is_vegetarian' => 1],
            ['name' => 'Mücver', 'calories' => 200, 'is_vegetarian' => 1, 'has_allergens' => 1],
            // Salata ve Mezeler
            ['name' => 'Mevsim Salata', 'calories' => 80, 'is_vegetarian' => 1, 'is_gluten_free' => 1],
            ['name' => 'Çoban Salata', 'calories' => 90, 'is_vegetarian' => 1, 'is_gluten_free' => 1],
            ['name' => 'Cacık', 'calories' => 100, 'is_vegetarian' => 1],
            ['name' => 'Haydari', 'calories' => 120, 'is_vegetarian' => 1],
            // Tatlı ve Meyveler
            ['name' => 'Sütlaç', 'calories' => 220, 'is_vegetarian' => 1],
            ['name' => 'Meyve Tabağı', 'calories' => 130, 'is_vegetarian' => 1, 'is_gluten_free' => 1],
            ['name' => 'İrmik Helvası', 'calories' => 350, 'is_vegetarian' => 1],
            ['name' => 'Elma', 'calories' => 95, 'is_vegetarian' => 1, 'is_gluten_free' => 1],
            ['name' => 'Muz', 'calories' => 105, 'is_vegetarian' => 1, 'is_gluten_free' => 1],
        ];

        $stmt_insert = $pdo->prepare(
            "INSERT INTO meals (name, calories, ingredients, is_vegetarian, is_gluten_free, has_allergens) \n             VALUES (:name, :calories, :ingredients, :is_vegetarian, :is_gluten_free, :has_allergens)"
        );

        foreach ($sample_meals as $meal) {
            $stmt_insert->execute([
                ':name' => $meal['name'],
                ':calories' => $meal['calories'] ?? null,
                ':ingredients' => $meal['ingredients'] ?? '',
                ':is_vegetarian' => $meal['is_vegetarian'] ?? 0,
                ':is_gluten_free' => $meal['is_gluten_free'] ?? 0,
                ':has_allergens' => $meal['has_allergens'] ?? 0,
            ]);
        }
        echo count($sample_meals) . " adet örnek yemek başarıyla eklendi.\n";
    } else {
        echo "Yemek tablosunda zaten veri var, bu adım atlanıyor.\n";
    }

    // 2. Adım: Temmuz 2025 için menü oluştur (eğer o ay için menü yoksa)
    $year = 2025;
    $month = 7;
    
    $stmt_check_menu = $pdo->prepare("SELECT COUNT(*) FROM menus WHERE YEAR(menu_date) = ? AND MONTH(menu_date) = ?");
    $stmt_check_menu->execute([$year, $month]);
    
    if ($stmt_check_menu->fetchColumn() > 0) {
        echo "\nTemmuz $year için zaten menü oluşturulmuş. İşlem sonlandırılıyor.\n";
        $pdo->rollBack(); // Hiçbir değişiklik yapma
    } else {
        echo "\nTemmuz $year için menü oluşturuluyor...\n";
        
        // Mevcut menü ve özel günleri temizle (Temmuz 2025 için)
        $pdo->prepare("DELETE FROM menus WHERE YEAR(menu_date) = ? AND MONTH(menu_date) = ?")->execute([$year, $month]);
        $pdo->prepare("DELETE FROM special_days WHERE YEAR(event_date) = ? AND MONTH(event_date) = ?")->execute([$year, $month]);
        echo "Temmuz $year için eski veriler temizlendi.\n";

        $stmt_meals = $pdo->query("SELECT id FROM meals");
        $meal_ids = $stmt_meals->fetchAll(PDO::FETCH_COLUMN);

        if (count($meal_ids) < 4) {
            throw new Exception("Hata: Menü oluşturmak için sistemde en az 4 çeşit yemek bulunmalıdır.");
        }

        $days_in_month = cal_days_in_month(CAL_GREGORIAN, $month, $year);
        $menu_insert_count = 0;
        $special_day_insert_count = 0;

        for ($day = 1; $day <= $days_in_month; $day++) {
            $date = new DateTime("$year-$month-$day");
            $day_of_week = (int)$date->format('N'); // 1 (Pzt) - 7 (Paz)
            $current_date_sql = $date->format('Y-m-d');

            // Özel günler
            if ($day === 15) {
                $stmt = $pdo->prepare("INSERT INTO special_days (event_date, message) VALUES (?, ?)");
                $stmt->execute([$current_date_sql, 'Demokrasi ve Milli Birlik Günü']);
                $special_day_insert_count++;
                echo "$current_date_sql: Özel gün eklendi - Demokrasi ve Milli Birlik Günü\n";
                continue;
            }

            // Hafta sonlarını atla
            if ($day_of_week >= 6) {
                echo "$current_date_sql: Hafta sonu, atlanıyor.\n";
                continue;
            }

            // Rastgele 4 yemek seç
            $random_meal_keys = array_rand($meal_ids, 4);
            $selected_meal_ids = array_map(fn($key) => $meal_ids[$key], $random_meal_keys);

            echo "$current_date_sql: Menü oluşturuluyor -> Yemek ID'leri: " . implode(', ', $selected_meal_ids) . "\n";

            $stmt_insert_menu = $pdo->prepare("INSERT INTO menus (menu_date, meal_id) VALUES (?, ?)");
            foreach ($selected_meal_ids as $meal_id) {
                $stmt_insert_menu->execute([$current_date_sql, $meal_id]);
                $menu_insert_count++;
            }
        }

        echo "\n------------------------------------\n";
        echo "Veri oluşturma işlemi tamamlandı!\n";
        echo "Toplam eklenen menü kaydı: $menu_insert_count\n";
        echo "Toplam eklenen özel gün: $special_day_insert_count\n";
        
        $pdo->commit();
    }

} catch (PDOException $e) {
    $pdo->rollBack();
    die("Veritabanı hatası: " . $e->getMessage());
} catch (Exception $e) {
    $pdo->rollBack();
    die("Genel hata: " . $e->getMessage());
}

echo "\n</pre>";
