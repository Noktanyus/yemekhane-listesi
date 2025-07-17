<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

try {
    // Autocomplete ve yemek listesi için tüm yemekleri çek
    $stmt = $pdo->query("SELECT id, name FROM meals ORDER BY name");
    $all_meals = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Yemekler alınırken hata oluştu: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Paneli - Yemek Listesi Yönetimi</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/admin.css">
</head>
<body>
    <header class="admin-header">
        <h1>Yönetim Paneli</h1>
        <div>
            <span>Hoş geldiniz, <strong><?php echo htmlspecialchars($_SESSION['admin_username']); ?></strong></span>
            <button id="help-btn" class="help-button" title="Yardım">?</button>
            <a href="logout.php" class="logout-link">Çıkış Yap</a>
        </div>
    </header>

    <main class="admin-container">
        <div class="tabs">
            <button class="tab-link active" data-tab="tab-date-management">Tarih Yönetimi</button>
            <button class="tab-link" data-tab="tab-meal-management">Yemek Yönetimi</button>
            <button class="tab-link" data-tab="tab-excel-import">Toplu Yükle</button>
            <button class="tab-link" data-tab="tab-logs">İşlem Kayıtları</button>
        </div>

        <!-- Tarih Yönetimi Sekmesi -->
        <div id="tab-date-management" class="tab-content active">
            <div class="date-management-layout">
                <!-- Haftalık Bakış -->
                <div class="week-view-container">
                    <h3>Haftalık Bakış</h3>
                    <div class="week-navigation">
                        <button id="prev-week">&lt; Önceki Hafta</button>
                        <span id="week-range"></span>
                        <button id="next-week">Sonraki Hafta &gt;</button>
                    </div>
                    <div id="week-view-list">
                        <!-- Haftanın günleri buraya JS ile yüklenecek -->
                    </div>
                </div>
                <!-- Düzenleme Formu -->
                <div class="edit-form-container">
                    <form id="manage-date-form" class="manage-date-form">
                        <h3 id="form-title">Tarih Seçin veya Oluşturun</h3>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="menu-date">Tarih</label>
                                <input type="date" id="menu-date" name="menu_date" required>
                            </div>
                            <div class="form-group checkbox-group">
                                <input type="checkbox" id="is-special-day" name="is_special_day">
                                <label for="is-special-day">Bu bir özel gün mü?</label>
                            </div>
                        </div>
                        <div id="meal-inputs-container">
                            <label>O Günün Menüsü</label>
                            <div id="meal-select-list"></div>
                            <button type="button" id="btn-add-meal-to-menu" class="btn-add-meal">+ Yemek Ekle</button>
                        </div>
                        <div id="special-day-container" class="hidden">
                            <div class="form-group">
                                <label for="special-day-message">Özel Gün Mesajı</label>
                                <textarea id="special-day-message" name="special_day_message" placeholder="Örn: Bayram nedeniyle yemekhane kapalıdır."></textarea>
                            </div>
                        </div>
                        <button type="submit" class="btn-submit">Değişiklikleri Kaydet</button>
                        <div id="form-response" class="hidden"></div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Yemek Yönetimi Sekmesi -->
        <div id="tab-meal-management" class="tab-content">
             <h3>Sistemdeki Yemekler</h3>
            <button id="btn-add-new-meal" class="btn-primary">Yeni Yemek Ekle</button>
            <div class="table-container">
                <table id="meals-table">
                    <thead>
                        <tr>
                            <th>Yemek Adı</th>
                            <th>Kalori</th>
                            <th>İşlemler</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>

        <!-- Excel/CSV ile Toplu Yükleme Sekmesi -->
        <div id="tab-excel-import" class="tab-content">
            <h3>CSV ile Toplu Menü Yükle</h3>
            <div class="edit-form-container">
                <h4>1. Adım: Dosya Yükle ve Analiz Et</h4>
                <p>Menüleri toplu olarak yüklemek için <strong>.csv</strong> formatında bir dosya seçin. Sistem, dosyayı veritabanına kaydetmeden önce analiz ederek size bir önizleme sunacaktır. Detaylı bilgi için <strong id="open-help-from-tab" style="cursor:pointer; color: var(--admin-secondary); text-decoration: underline;">yardım ikonuna (?)</strong> tıklayın.</p>
                <form id="csv-upload-form" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="csv-file">CSV Dosyası Seçin</label>
                        <input type="file" id="csv-file" name="csv_file" class="form-control" accept=".csv" required>
                    </div>
                    <button type="submit" class="btn-submit">Dosyayı Analiz Et</button>
                </form>
            </div>
            
            <!-- 2. Adım: Önizleme ve Onaylama -->
            <div id="csv-preview-container" class="hidden" style="margin-top: 2rem;">
                <div class="edit-form-container">
                    <h4>2. Adım: Önizlemeyi Kontrol Edin ve Onaylayın</h4>
                    <p>Aşağıda yüklenen dosyanın bir özeti bulunmaktadır. Sistemde bulunmayan yeni yemekler <span class="new-meal-label">Yeni</span> olarak işaretlenmiştir. Bu yemekleri olduğu gibi ekleyebilir veya listeden mevcut bir yemekle değiştirebilirsiniz.</p>
                    <div id="csv-preview-list">
                        <!-- Analiz sonuçları buraya JS ile yüklenecek -->
                    </div>
                    <div id="csv-preview-actions" style="margin-top: 1.5rem; display: flex; gap: 1rem;">
                        <button id="btn-commit-csv" class="btn-submit" style="width: auto; flex-grow: 1;">Onayla ve Menüleri Kaydet</button>
                        <button id="btn-cancel-csv" class="btn-delete" style="width: auto;">İptal</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- İşlem Kayıtları Sekmesi -->
        <div id="tab-logs" class="tab-content">
            <h3>Sistem İşlem Kayıtları</h3>
            <div class="table-container">
                <table id="logs-table">
                    <thead>
                        <tr>
                            <th>Tarih</th>
                            <th>Yönetici</th>
                            <th>Eylem</th>
                            <th>Detaylar</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Loglar JS ile buraya yüklenecek -->
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <!-- Yemek Ekleme/Düzenleme Modalı -->
    <div id="meal-modal" class="modal hidden">
        <div class="modal-content">
            <button class="modal-close">&times;</button>
            <h3 id="modal-title-meal">Yeni Yemek Ekle</h3>
            <form id="meal-form">
                <input type="hidden" name="meal_id" id="meal-id">
                <div class="form-group"><label for="meal-name">Yemek Adı</label><input type="text" id="meal-name" name="name" required></div>
                <div class="form-group"><label for="meal-calories">Kalori</label><input type="number" id="meal-calories" name="calories"></div>
                <div class="form-group"><label for="meal-ingredients">İçerik (Malzemeler)</label><textarea id="meal-ingredients" name="ingredients" rows="4"></textarea></div>
                <div class="form-group diet-options">
                    <div class="checkbox-group"><input type="checkbox" id="is_vegetarian" name="is_vegetarian"><label for="is_vegetarian">Vejetaryen</label></div>
                    <div class="checkbox-group"><input type="checkbox" id="is_gluten_free" name="is_gluten_free"><label for="is_gluten_free">Glütensiz</label></div>
                    <div class="checkbox-group"><input type="checkbox" id="has_allergens" name="has_allergens"><label for="has_allergens">Alerjen İçerir</label></div>
                </div>
                <button type="submit" class="btn-submit">Kaydet</button>
            </form>
        </div>
    </div>

    <!-- Yardım Modalı -->
    <div id="help-modal" class="modal hidden">
        <div class="modal-content">
            <button class="modal-close">&times;</button>
            <h3>Yardım</h3>
            <div id="help-content">
                <h4>CSV Dosyası ile Çalışma Rehberi</h4>
                <p><strong>Önemli:</strong> Türkçe karakterlerin (ç, ğ, ı, ö, ş, ü) bozuk görünmemesi için dosyalarınızı doğru formatta kaydetmeniz kritik öneme sahiptir.</p>
                
                <h5>En Güvenilir Yöntem: Metin Düzenleyici Kullanmak</h5>
                <p>Microsoft Excel yerine <strong>Notepad++</strong> veya <strong>Visual Studio Code</strong> gibi ücretsiz bir metin düzenleyici kullanmanızı şiddetle tavsiye ederiz. Bu programlar, dosyayı her zaman doğru format olan <strong>UTF-8</strong>'de tutar.</p>

                <h5>Excel Kullanıyorsanız (Dikkatli Olun):</h5>
                <p>Excel'de bir CSV dosyasını düzenledikten sonra kaydederken şu adımları izlemelisiniz:</p>
                <ol>
                    <li><strong>Dosya (File)</strong> menüsünden <strong>Farklı Kaydet (Save As)</strong> seçeneğine gidin.</li>
                    <li>Açılan pencerede, "Kayıt türü" (Save as type) açılır listesinden <strong>CSV UTF-8 (Virgülle ayrılmış) (*.csv)</strong> seçeneğini bulun ve seçin.</li>
                    <li>Dosyanızı kaydedin.</li>
                </ol>
                <p><strong>Not:</strong> Varsayılan "CSV" formatını seçerseniz, Türkçe karakterleriniz bozulacaktır!</p>

                <hr style="margin: 2rem 0;">

                <h4>Dosya Formatı Kuralları</h4>
                <ul>
                    <li>Sütunlar noktalı virgül (<code>;</code>) ile ayrılmalıdır.</li>
                    <li>Sütun sırası: <code>Tarih;Yemek 1;Yemek 2;Yemek 3;Yemek 4;Yemek 5;Yemek 6;OzelGun</code></li>
                    <li><code>OzelGun</code> sütununa <code>1</code> yazılırsa, o satır özel gün olarak kabul edilir.</li>
                </ul>
                <a href="ornek_menu_noktalivirgul.csv" download="ornek_menu_noktalivirgul.csv" class="btn-primary" style="text-decoration: none;">Doğru Formatlı Örnek CSV İndir</a>
            </div>
        </div>
    </div>

    <!-- Autocomplete için Datalist -->
    <datalist id="meals-datalist">
        <?php foreach ($all_meals as $meal): ?>
            <option value="<?php echo htmlspecialchars($meal['name']); ?>">
        <?php endforeach; ?>
    </datalist>

    <!-- Autocomplete Input Şablonu -->
    <template id="meal-input-template">
        <div class="form-group meal-input-group">
            <input type="text" name="meal_names[]" list="meals-datalist" placeholder="Yemek adını yazın..." class="meal-autocomplete-input">
            <button type="button" class="btn-remove-meal">X</button>
        </div>
    </template>

    <!-- Bildirim (Toast) Kapsayıcısı -->
    <div id="toast-container"></div>

    <script src="assets/js/admin.js"></script>
</body>
</html>