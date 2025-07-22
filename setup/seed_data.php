<?php
session_start();
require_once __DIR__ . '/../db_connect.php';

// Hata ayıklamayı kolaylaştırmak için
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    // --- BÖLÜM 1: Tabloları Temizleme (Transaction DIŞINDA) ---
    $tables_to_clean = ['logs', 'feedback', 'special_days', 'menus', 'site_settings', 'meals', 'meal_prices'];
    
    $pdo->exec("SET FOREIGN_KEY_CHECKS=0;");
    foreach ($tables_to_clean as $table) {
        $pdo->exec("DELETE FROM `$table`");
        $pdo->exec("ALTER TABLE `$table` AUTO_INCREMENT = 1");
    }
    $pdo->exec("SET FOREIGN_KEY_CHECKS=1;");

    // --- BÖLÜM 2: Veri Ekleme (Transaction İÇİNDE) ---
    $pdo->beginTransaction();

    // 1. Çok Sayıda Örnek Yemek Ekle (`meals`)
    $meals_data = [
        // Çorbalar
        ['Mercimek Çorbası', 150, 'Mercimek, soğan, havuç', 1, 1, 0], ['Ezogelin Çorbası', 180, 'Kırmızı mercimek, nane, bulgur', 1, 0, 0], ['Yayla Çorbası', 160, 'Yoğurt, pirinç, nane', 1, 0, 0], ['Domates Çorbası', 130, 'Domates, kaşar peyniri', 1, 0, 0], ['Brokoli Çorbası', 120, 'Brokoli, krema', 1, 1, 0], ['Mantar Çorbası', 140, 'Mantar, krema', 1, 1, 0], ['Tavuk Suyu Çorba', 190, 'Tavuk, şehriye', 0, 0, 0],
        // Ana Yemekler (Et)
        ['Orman Kebabı', 450, 'Kuşbaşı et, bezelye, patates', 0, 0, 0], ['Izgara Köfte', 400, 'Kıyma, soğan, baharat', 0, 0, 0], ['Dana Rosto', 480, 'Dana but, kök sebzeler', 0, 1, 0], ['Hünkar Beğendi', 520, 'Kuzu eti, patlıcan, beşamel', 0, 0, 0], ['Adana Kebap', 550, 'Kıyma, acı biber', 0, 0, 0],
        // Ana Yemekler (Tavuk)
        ['Izgara Tavuk', 350, 'Tavuk göğsü, baharatlar', 0, 1, 0], ['Tavuk Sote', 380, 'Tavuk, biber, domates', 0, 1, 0], ['Körili Tavuk', 410, 'Tavuk, köri, krema', 0, 1, 0], ['Tavuk Şnitzel', 430, 'Tavuk göğsü, galeta unu', 0, 0, 0],
        // Ana Yemekler (Balık)
        ['Fırında Somon', 340, 'Somon fileto, dereotu', 0, 1, 0], ['Izgara Levrek', 320, 'Levrek, limon, zeytinyağı', 0, 1, 0],
        // Ana Yemekler (Vejetaryen/Vegan)
        ['Kuru Fasulye', 380, 'Kuru fasulye, soğan, salça', 1, 1, 0], ['Sebzeli Güveç', 280, 'Patlıcan, kabak, biber, domates', 1, 1, 0], ['Nohut Yemeği', 360, 'Nohut, soğan, salça', 1, 1, 0], ['Ispanak Yemeği', 250, 'Ispanak, pirinç, soğan', 1, 1, 0], ['Mercimek Köftesi', 290, 'Kırmızı mercimek, bulgur, yeşillik', 1, 0, 0],
        // Yardımcı Yemekler
        ['Nohutlu Pilav', 300, 'Pirinç, nohut, tereyağı', 1, 1, 0], ['Bulgur Pilavı', 280, 'Bulgur, domates, biber', 1, 0, 0], ['Glutensiz Makarna', 320, 'Mısır unu makarnası, domates sos', 1, 1, 0], ['Patates Püresi', 220, 'Patates, süt, tereyağı', 1, 1, 0], ['Mücver', 180, 'Kabak, un, dereotu', 1, 0, 0], ['Peynirli Börek', 350, 'Yufka, peynir', 0, 0, 0],
        // Salata ve Mezeler
        ['Mevsim Salata', 80, 'Marul, domates, salatalık', 1, 1, 0], ['Çoban Salata', 90, 'Domates, salatalık, biber, soğan', 1, 1, 0], ['Cacık', 110, 'Yoğurt, salatalık, nane', 1, 1, 0], ['Gavurdağı Salata', 150, 'Domates, ceviz, nar ekşisi', 1, 1, 0], ['Humus', 200, 'Nohut, tahin', 1, 1, 0],
        // Tatlılar ve Meyveler
        ['Sütlaç', 250, 'Süt, pirinç, şeker', 1, 1, 0], ['Revani', 350, 'İrmik, şeker, şerbet', 0, 0, 0], ['Meyve Tabağı', 120, 'Mevsim meyveleri', 1, 1, 0], ['İrmik Helvası', 400, 'İrmik, şeker, çam fıstığı', 1, 0, 0], ['Profiterol', 380, 'Kremalı top, çikolata sosu', 0, 0, 0], ['Kazandibi', 280, 'Süt, tavuk göğsü (geleneksel)', 0, 1, 0]
    ];
    $stmt = $pdo->prepare("INSERT INTO `meals` (name, calories, ingredients, is_vegetarian, is_gluten_free, has_allergens) VALUES (?, ?, ?, ?, ?, ?)");
    foreach ($meals_data as $meal) {
        $stmt->execute($meal);
    }

    $all_meals = $pdo->query("SELECT id, name FROM meals")->fetchAll(PDO::FETCH_ASSOC);
    $meal_ids_by_name = array_column($all_meals, 'id', 'name');

    // 2. Bir Aylık Menü Oluştur (`menus`)
    $stmt = $pdo->prepare("INSERT INTO `menus` (menu_date, meal_id) VALUES (?, ?)");
    for ($i = -15; $i <= 15; $i++) {
        $menu_date = date('Y-m-d', strtotime("$i days"));
        // Her gün için rastgele 4 farklı yemek seç (bu sadece bir örnek, daha karmaşık kurallar eklenebilir)
        shuffle($all_meals);
        for ($j = 0; $j < 4; $j++) {
            if(isset($all_meals[$j])) {
                $stmt->execute([$menu_date, $all_meals[$j]['id']]);
            }
        }
    }

    // 3. Çok Sayıda Özel Gün Ekle (`special_days`)
    $special_days = [
        [date('Y-m-d', strtotime('+10 day')), 'Resmi Tatil Nedeniyle Yemekhane Kapalıdır'],
        [date('Y-m-d', strtotime('+11 day')), 'Genel Bakım Çalışması'],
        [date('Y-m-d', strtotime('-20 day')), 'Bahar Şenlikleri Özel Menüsü'],
        [date('Y-m-d', strtotime('+45 day')), 'Yaz Okulu Başlangıcı'],
    ];
    $stmt = $pdo->prepare("INSERT INTO `special_days` (event_date, message) VALUES (?, ?)");
    foreach ($special_days as $day) {
        $stmt->execute($day);
    }

    // 4. Çok Sayıda Geri Bildirim Ekle (`feedback`)
    $admin_id = $pdo->query("SELECT id FROM admins LIMIT 1")->fetchColumn();
    $feedbacks = [
        ['Ayşe Yılmaz', 'ayse@example.com', 4, 'Yemekler genel olarak güzeldi ama pilav biraz daha sıcak olabilirdi.', null, 0, 0, null, null, null],
        ['Mehmet Vural', 'mehmet@example.com', 5, 'Bugünkü menü harikaydı, özellikle orman kebabı. Ellerinize sağlık!', null, 1, 0, null, null, null],
        ['Elif Öztürk', 'elif@example.com', 2, 'Makarna çok tuzluydu, yiyemedim.', null, 1, 1, 'Dikkate alınacaktır, teşekkürler.', date("Y-m-d H:i:s", strtotime('-1 day')), $admin_id],
        ['Bora Can', 'bora@example.com', 5, 'Vejetaryen seçeneklerin artması çok güzel olmuş.', null, 1, 1, 'Afiyet olsun!', date("Y-m-d H:i:s", strtotime('-2 day')), $admin_id],
        ['Selin Demir', 'selin@example.com', 3, 'Çorbalarınız çok lezzetli ama ana yemekler bazen soğuk geliyor.', null, 0, 0, null, null, null],
        ['Kaan Arslan', 'kaan@example.com', 4, 'Temizlik ve hijyen konusunda çok iyisiniz, teşekkürler.', null, 1, 0, null, null, null],
        ['Zeynep Kaya', 'zeynep@example.com', 1, 'Köfteler yanmıştı ve çok kuruydu.', null, 1, 1, 'Yaşanan aksaklık için özür dileriz, ilgili birimler uyarıldı.', date("Y-m-d H:i:s", strtotime('-3 day')), $admin_id],
    ];
    $stmt = $pdo->prepare("INSERT INTO `feedback` (name, email, rating, comment, image_path, is_read, is_archived, reply_message, replied_at, replied_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    for ($k=0; $k<3; $k++) { // Aynı feedbackleri 3 kere ekleyerek sayıyı arttır
        foreach ($feedbacks as $fb) {
            $stmt->execute($fb);
        }
    }

    // 5. Site Ayarlarını Ekle (`site_settings`)
    // Not: Bu ayarlar, `api/manage_officials.php` tarafından kullanılan anahtarlarla uyumludur.
    $settings = [
        ['site_title', 'Akdeniz Üniversitesi Yemekhane Servisi'],
        ['sks_daire_baskani', 'Doç. Dr. Veli ÇELİK'],
        ['yemekhane_mudur_yrd', 'Öğr. Gör. Ayşe YILMAZ'],
        ['diyetisyen', 'Uzm. Dyt. Fatma ÖZTÜRK'],
        ['yemekhane_email', 'yemekhane@akdeniz.edu.tr']
    ];
    $stmt = $pdo->prepare("INSERT INTO `site_settings` (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
    foreach ($settings as $setting) {
        $stmt->execute($setting);
    }

    // 6. Sahte Log Kayıtları Oluştur (`logs`)
    $logs = [
        ['admin', 'Login', 'Başarılı giriş', '127.0.0.1'],
        ['admin', 'Menu Updated', '2024-10-28 menüsü güncellendi.', '127.0.0.1'],
        ['admin', 'Meal Added', 'Yeni yemek eklendi: Patlıcan Musakka', '127.0.0.1'],
        ['admin', 'Feedback Replied', 'ID 3 olan geri bildirim yanıtlandı.', '127.0.0.1'],
        ['admin', 'Settings Changed', 'Site başlığı güncellendi.', '127.0.0.1'],
    ];
    $stmt = $pdo->prepare("INSERT INTO `logs` (admin_username, action_type, details, ip_address) VALUES (?, ?, ?, ?)");
    for ($k=0; $k<5; $k++) { // Logları 5 kere ekle
        foreach ($logs as $log) {
            $stmt->execute($log);
        }
    }

    // 7. Yemek Ücretleri Ekle (`meal_prices`)
    $meal_prices = [
        ['ÖĞRENCİ YEMEK ÜCRETİ', 'Öğrenci yemek ücreti', 40.00, 1, 1],
        ['GÜN İÇİNDE 2. ÖĞÜN YEMEK ÜCRETİ', 'Gün içinde 2. öğün yemek ücreti', 70.00, 1, 2],
        ['SÖZL. PERSONEL (4/B) 0-600 EK GÖSTERGE', 'Sözleşmeli personel 0-600 ek gösterge', 80.00, 1, 3],
        ['601-2800 (DAHİL) EK GÖSTERGE', 'Personel 601-2800 ek gösterge', 85.00, 1, 4],
        ['2801-3000 (DAHİL) EK GÖSTERGE', 'Personel 2801-3000 ek gösterge', 95.00, 1, 5],
        ['3001-4200 (DAHİL) EK GÖSTERGE', 'Personel 3001-4200 ek gösterge', 100.00, 1, 6],
        ['4201-5400 (DAHİL) EK GÖSTERGE', 'Personel 4201-5400 ek gösterge', 110.00, 1, 7],
        ['5401-7000 ve ÜSTÜ EK GÖSTERGE', 'Personel 5401-7000 ve üstü ek gösterge', 120.00, 1, 8],
        ['PERSONEL İÇİN GÜN İCİNDE TEKRAR GEÇİŞLER', 'Personel için gün içinde tekrar geçişler', 220.00, 1, 9]
    ];
    $stmt = $pdo->prepare("INSERT INTO `meal_prices` (group_name, description, price, is_active, sort_order) VALUES (?, ?, ?, ?, ?)");
    foreach ($meal_prices as $price) {
        $stmt->execute($price);
    }

    $pdo->commit();
    $_SESSION['setup_message'] = 'Başarılı: Siteyi tamamen dolduracak kadar çok sayıda örnek veri eklendi.';

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $_SESSION['setup_message'] = 'Hata: Örnek veriler eklenirken bir sorun oluştu. Hata: ' . $e->getMessage();
    error_log("Seed Data Error: " . $e->getMessage());
    header('Location: index.php');
    exit;
}

// Yönlendirme
if (isset($_GET['run-all'])) {
    header('Location: index.php');
    exit;
}

header('Location: index.php');
exit;
