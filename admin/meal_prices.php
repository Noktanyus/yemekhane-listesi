<div class="card">
    <div class="card-header">
        <h3>Yemek Ücretleri Yönetimi</h3>
        <button id="btn-add-new-price" class="btn btn-primary"><i class="fas fa-plus"></i> Yeni Ücret Ekle</button>
    </div>
    <div class="card-body">
        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i>
            <strong>Bilgi:</strong> Personel kart ücretlerindeki farklılık, Akdeniz Üniversitesi Yönetim Kurulu Kararı ve 09 Ocak 2025 tarih, 32777 sayılı resmi gazetede, Hazine ve Maliye Bakanlığı tarafından yayımlanan merkezi bütçe uygulama tebliğinde belirtilen ek gösterge sınıflamalarına uyularak oluşturulmaktadır.
        </div>
        <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle"></i>
            <strong>Uyarı:</strong> Mezun olacak durumdaki öğrencilerimiz bakiye yüklemelerini mezuniyet tarihinde bitirebilecek kadar yükleme yapmalıdır. Bakiye iadesi yapılmamaktadır.
        </div>
        <div class="table-responsive">
            <table id="meal-prices-table" class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>Yemek Yiyen Grup Özellikleri</th>
                        <th>Açıklama</th>
                        <th>Günlük Ücret</th>
                        <th>Durum</th>
                        <th>Sıralama</th>
                        <th>İşlemler</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- JS ile dolacak -->
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Yemek Ücreti Düzenleme Modalı -->
<div id="meal-price-modal" class="modal hidden">
    <div class="modal-content">
        <button class="modal-close">&times;</button>
        <h3 id="price-modal-title">Yemek Ücreti Düzenle</h3>
        <form id="meal-price-form">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
            <input type="hidden" id="price-id" name="price_id">
            <div class="form-group">
                <label for="price-group-name">Grup Adı</label>
                <input type="text" id="price-group-name" name="group_name" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="price-description">Açıklama</label>
                <input type="text" id="price-description" name="description" class="form-control">
            </div>
            <div class="form-group">
                <label for="price-amount">Ücret (TL)</label>
                <input type="number" id="price-amount" name="price" class="form-control" step="0.01" min="0" required>
            </div>
            <div class="form-group">
                <label for="price-sort-order">Sıralama</label>
                <input type="number" id="price-sort-order" name="sort_order" class="form-control" min="0" value="0">
            </div>
            <div class="form-group">
                <input type="checkbox" id="price-is-active" name="is_active" checked>
                <label for="price-is-active" class="form-check-label">Aktif</label>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Kaydet</button>
                <button type="button" class="btn btn-secondary" onclick="closeMealPriceModal()">İptal</button>
            </div>
        </form>
    </div>
</div>