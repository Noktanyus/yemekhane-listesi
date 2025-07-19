<?php
require_once 'db_connect.php';

echo "<h1>Veritabanı Doldurma (Seeding) İşlemi (Son Düzeltme)</h1>";

function getRandomIP() {
    return rand(1, 254) . "." . rand(0, 255) . "." . rand(0, 255) . "." . rand(0, 255);
}

try {
    // --- 1. Eski Verileri Temizle (İşlem dışında) ---
    echo "<p>Eski veriler temizleniyor (menus, meals, special_days, feedback, logs)...</p>";
    // TRUNCATE, örtük bir COMMIT'e neden olur, bu yüzden işlem dışında çalıştırılır.
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
    $pdo->exec("TRUNCATE TABLE menus;");
    $pdo->exec("TRUNCATE TABLE meals;");
    $pdo->exec("TRUNCATE TABLE special_days;");
    $pdo->exec("TRUNCATE TABLE feedback;");
    $pdo->exec("TRUNCATE TABLE logs;");
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
    echo "<p style='color:green;'>Temizlik tamamlandı.</p>";

    // --- 2. Veri Ekleme İşlemini Başlat ---
    $pdo->beginTransaction();
    echo "<p>Veri ekleme işlemi başlatıldı...</p>";

    // --- Yemek Havuzunu Oluştur ---
    echo "<p>Yemek havuzu oluşturuluyor...</p>";
    $stmt_meal = $pdo->prepare("INSERT INTO meals (name, calories, is_vegetarian, is_gluten_free, has_allergens) VALUES (?, ?, ?, ?, ?)");
    $yemekler = [
        ['Mercimek Çorbası', 150, true, false, true], ['Ezogelin Çorbası', 180, true, false, true],
        ['Yayla Çorbası', 160, true, false, true], ['Domates Çorbası', 140, true, true, false],
        ['Orman Kebabı', 450, false, true, false], ['Tavuk Sote', 400, false, true, false],
        ['Izgara Köfte', 500, false, false, true], ['Kuru Fasulye', 350, true, true, false],
        ['Ispanak Yemeği', 250, true, true, false], ['Fırında Balık', 320, false, true, true],
        ['Pirinç Pilavı', 200, true, true, false], ['Bulgur Pilavı', 180, true, false, true],
        ['Mevsim Salata', 80, true, true, false], ['Cacık', 90, true, true, true],
        ['Sütlaç', 220, true, true, true], ['Kemalpaşa Tatlısı', 300, true, false, true],
        ['Mevsim Meyvesi', 100, true, true, false]
    ];
    foreach ($yemekler as $yemek) {
        $stmt_meal->execute([(string)$yemek[0], (int)$yemek[1], (int)$yemek[2], (int)$yemek[3], (int)$yemek[4]]);
    }
    echo "<p style='color:green;'>" . count($yemekler) . " adet yemek başarıyla eklendi.</p>";

    // --- Örnek Menüleri ve Özel Günleri Oluştur ---
    echo "<p>Örnek menüler ve özel günler oluşturuluyor...</p>";
    $stmt_menu = $pdo->prepare("INSERT INTO menus (menu_date, meal_id) VALUES (?, ?)");
    $stmt_special = $pdo->prepare("INSERT INTO special_days (event_date, message) VALUES (?, ?)");
    $stmt_get_meal_id = $pdo->prepare("SELECT id FROM meals WHERE name = ?");
    $today = new DateTime();
    $start_date = (clone $today)->modify('-1 month');
    $end_date = (clone $today)->modify('+1 month');
    $interval = new DateInterval('P1D');
    $period = new DatePeriod($start_date, $interval, $end_date);
    $menu_count = 0;
    foreach ($period as $date) {
        $date_str = $date->format('Y-m-d');
        $day_of_week = $date->format('N');
        if ($day_of_week >= 6) {
            $stmt_special->execute([$date_str, 'Hafta sonu servisimiz yoktur.']);
        } else {
            $gunun_menusu = [$yemekler[rand(0, 3)][0], $yemekler[rand(4, 9)][0], $yemekler[rand(10, 13)][0], $yemekler[rand(14, 16)][0]];
            foreach ($gunun_menusu as $yemek_adi) {
                $stmt_get_meal_id->execute([$yemek_adi]);
                $meal_id = $stmt_get_meal_id->fetchColumn();
                if ($meal_id) {
                    $stmt_menu->execute([$date_str, $meal_id]);
                    $menu_count++;
                }
            }
        }
    }
    echo "<p style='color:green;'>{$menu_count} adet menü öğesi başarıyla oluşturuldu.</p>";

    // --- Örnek Geri Bildirimleri Oluştur ---
    echo "<p>Örnek geri bildirimler oluşturuluyor...</p>";
    $feedback_stmt = $pdo->prepare("INSERT INTO feedback (name, email, rating, comment, status, is_read, created_at, reply_text, replied_at, replied_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $names = ['Ayşe Yılmaz', 'Mehmet Kaya', 'Fatma Öztürk', 'Ali Veli', 'Zeynep Şahin', 'Mustafa Demir', 'Elif Arslan'];
    $comments = ['Yemekler harikaydı!', 'Porsiyonlar biraz daha büyük olabilir mi?', 'Vejetaryen seçenekler harika.', 'Çorba çok tuzluydu.', 'Ellerinize sağlık.', 'Tatlı çeşitliliği artırılabilir mi?', 'Temizlik ve hijyen çok iyi.'];
    $statuses = ['yeni', 'okundu', 'cevaplandı'];
    for ($i = 0; $i < 15; $i++) {
        $status = $statuses[array_rand($statuses)];
        $reply = null; $replied_at = null; $replied_by = null;
        if ($status === 'cevaplandı') {
            $reply = 'Değerli geri bildiriminiz için teşekkür ederiz.';
            $replied_at = date('Y-m-d H:i:s', time() - rand(86400, 604800));
            $replied_by = 'admin';
        }
        $feedback_stmt->execute([$names[array_rand($names)], 'test' . $i . '@akdeniz.edu.tr', rand(1, 5), $comments[array_rand($comments)], $status, rand(0, 1), date('Y-m-d H:i:s', time() - rand(86400, 1209600)), $reply, $replied_at, $replied_by]);
    }
    echo "<p style='color:green;'>15 adet rastgele geri bildirim eklendi.</p>";

    // --- Örnek Log Kayıtlarını Oluştur ---
    echo "<p>��rnek log kayıtları oluşturuluyor...</p>";
    $log_stmt = $pdo->prepare("INSERT INTO logs (admin_username, ip_address, action, details, created_at) VALUES (?, ?, ?, ?, ?)");
    $actions = ['Giriş Başarılı' => 'Admin kullanıcısı panele giriş yaptı.', 'Menü Güncelleme' => 'Tarihli menü güncellendi.', 'CSV Yükleme' => 'Yeni menü listesi yüklendi.', 'Geri Bildirim Cevaplama' => 'IDli geri bildirim cevaplandı.'];
    for ($i = 0; $i < 30; $i++) {
        $action_key = array_rand($actions);
        $details = str_replace(['Tarihli', 'IDli'], [date('d.m.Y', time() - rand(86400, 1209600)), rand(1, 15)], $actions[$action_key]);
        $log_stmt->execute(['admin', getRandomIP(), $action_key, $details, date('Y-m-d H:i:s', time() - rand(86400, 1209600))]);
    }
    echo "<p style='color:green;'>30 adet rastgele log kaydı eklendi.</p>";

    // --- İşlemi Tamamla ---
    $pdo->commit();
    echo "<h2>Veritabanı başarıyla ve tamamen dolduruldu!</h2>";

} catch (Exception $e) {
    // Eğer işlem hala aktifse (beklenmedik bir hata durumunda), geri al.
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
        echo "<p style='color:orange;'>İşlem geri alındı.</p>";
    }
    die("<p style='color:red;'>Bir hata oluştu: " . $e->getMessage() . "</p>");
}