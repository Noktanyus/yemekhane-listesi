       
        </main> <!-- .main-content -->
    </div> <!-- .admin-wrapper -->

    <!-- Modallar -->
    <div id="meal-modal" class="modal hidden">
        <div class="modal-content card">
            <div class="card-header"><h3 id="modal-title-meal"></h3><button class="modal-close">&times;</button></div>
            <div class="card-body">
                <form id="meal-form">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>"><input type="hidden" name="meal_id">
                    <div class="form-group"><label for="meal-name">Yemek Adı</label><input type="text" id="meal-name" name="name" class="form-control" required></div>
                    <div class="form-group"><label for="meal-calories">Kalori</label><input type="number" id="meal-calories" name="calories" class="form-control"></div>
                    <div class="form-group"><label for="meal-ingredients">İçerik</label><textarea id="meal-ingredients" name="ingredients" class="form-control" rows="3"></textarea></div>
                    <div class="d-flex justify-content-between">
                        <div class="form-group"><input type="checkbox" id="is_vegetarian" name="is_vegetarian" value="1"><label for="is_vegetarian" class="form-check-label">Vejetaryen</label></div>
                        <div class="form-group"><input type="checkbox" id="is_gluten_free" name="is_gluten_free" value="1"><label for="is_gluten_free" class="form-check-label">Glütensiz</label></div>
                        <div class="form-group"><input type="checkbox" id="has_allergens" name="has_allergens" value="1"><label for="has_allergens" class="form-check-label">Alerjen İçerir</label></div>
                    </div>
                    <button type="submit" class="btn btn-primary w-100 mt-2">Kaydet</button>
                </form>
            </div>
        </div>
    </div>
    <div id="reply-modal" class="modal hidden">
        <div class="modal-content card">
            <div class="card-header"><h3>Geri Bildirime Cevap Ver</h3><button class="modal-close">&times;</button></div>
            <div class="card-body">
                <form id="reply-form">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    <input type="hidden" id="reply-feedback-id" name="id">
                    <input type="hidden" id="reply-feedback-email" name="email">
                    <input type="hidden" id="reply-feedback-name" name="name">
                    <div class="form-group">
                        <label for="reply-text">Cevap Metni</label>
                        <textarea id="reply-text" name="reply_text" class="form-control" rows="8" required></textarea>
                    </div>
                    <div class="d-flex justify-content-between mt-3">
                        <button type="button" id="btn-use-template" class="btn btn-secondary">Şablon Kullan</button>
                        <button type="submit" class="btn btn-primary">Cevabı Gönder</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Resim Görüntüleyici Modalı -->
    <div id="image-viewer-modal" class="modal hidden">
        <div class="modal-content image-modal-content">
            <div class="image-viewer-header">
                <div class="image-viewer-controls">
                    <button id="zoom-in-btn" class="btn btn-sm btn-secondary" title="Yakınlaştır"><i class="fas fa-search-plus"></i></button>
                    <button id="zoom-out-btn" class="btn btn-sm btn-secondary" title="Uzaklaştır"><i class="fas fa-search-minus"></i></button>
                    <a id="download-btn" href="#" download class="btn btn-sm btn-primary" title="İndir"><i class="fas fa-download"></i></a>
                </div>
                <button class="modal-close">&times;</button>
            </div>
            <div class="image-viewer-body">
                <img id="image-viewer-img" src="" alt="Geri Bildirim Eki">
            </div>
        </div>
    </div>

    <!-- Datalist ve Şablonlar -->
    <div id="toast-container"></div>
    <datalist id="meals-datalist"></datalist>
    <template id="meal-input-template">
        <div class="meal-input-group">
            <input type="text" name="meal_names[]" list="meals-datalist" class="form-control" placeholder="Yemek adını yazın...">
            <button type="button" class="btn btn-danger btn-sm btn-remove-meal"><i class="fas fa-trash-alt"></i></button>
        </div>
    </template>

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
    <script src="../assets/js/admin.js?v=<?php echo time(); ?>"></script>
</body>
</html>
