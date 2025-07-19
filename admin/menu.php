<div class="page-grid">
    <!-- Sol Sütun: Haftalık Bakış -->
    <div class="grid-col-span-2">
        <div class="card">
            <div class="card-header">
                <h3>Haftalık Bakış</h3>
                <div class="week-navigation">
                    <button id="prev-week" class="btn btn-secondary btn-sm" title="Önceki Hafta"><i class="fas fa-chevron-left"></i></button>
                    <h4 id="week-range">Yükleniyor...</h4>
                    <button id="next-week" class="btn btn-secondary btn-sm" title="Sonraki Hafta"><i class="fas fa-chevron-right"></i></button>
                </div>
            </div>
            <div class="card-body">
                <div id="week-view-list">
                    <!-- JS ile dolacak -->
                </div>
            </div>
        </div>
    </div>
    <!-- Sağ Sütun: Düzenleme Formu -->
    <div>
        <div class="card">
            <div class="card-header"><h3 id="form-title">Menü Düzenle</h3></div>
            <div class="card-body">
                <form id="manage-date-form">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    <div class="form-group">
                        <label for="menu-date">Tarih</label>
                        <input type="date" id="menu-date" name="menu_date" class="form-control" required>
                    </div>
                    <div id="menu-details-section" class="hidden">
                        <div class="form-group">
                            <input type="checkbox" id="is-special-day" name="is_special_day">
                            <label for="is-special-day" class="form-check-label">Bu bir özel gün mü?</label>
                        </div>
                        <div id="meal-inputs-container">
                            <label>O Günün Menüsü</label>
                            <div id="meal-select-list"></div>
                            <button type="button" id="btn-add-meal-to-menu" class="btn btn-info btn-sm mt-2"><i class="fas fa-plus"></i> Yemek Satırı Ekle</button>
                        </div>
                        <div id="special-day-container" class="hidden">
                            <div class="form-group">
                                <label for="special-day-message">Özel Gün Mesajı</label>
                                <textarea id="special-day-message" name="special_day_message" class="form-control" placeholder="Örn: Bayram nedeniyle yemekhane kapalıdır."></textarea>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary w-100 mt-3"><i class="fas fa-save"></i> Değişiklikleri Kaydet</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
