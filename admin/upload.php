<div class="card">
    <div class="card-header">
        <h3>CSV ile Toplu Menü Yükle</h3>
    </div>
    <div class="card-body">
        <div id="csv-upload-area">
            <p>Menüleri toplu olarak yüklemek için <strong>.csv</strong> formatında bir dosya seçin. Dosya formatının doğru olduğundan emin olmak için örnek dosyayı indirebilirsiniz.</p>
            <a href="../ornek_menu_noktalivirgul.csv" download class="btn btn-secondary mb-3"><i class="fas fa-download"></i> Örnek CSV İndir</a>
            <form id="csv-upload-form" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                <div class="form-group">
                    <label for="csv-file">CSV Dosyası Seçin</label>
                    <input type="file" id="csv-file" name="csv_file" class="form-control" accept=".csv" required>
                </div>
                <button type="submit" class="btn btn-info w-100"><i class="fas fa-cogs"></i> Dosyayı Analiz Et</button>
            </form>
        </div>
        <div id="csv-preview-container" class="hidden mt-3">
            <h4>Önizleme ve Onay</h4>
            <p>Aşağıda yüklenen dosyanın bir özeti bulunmaktadır. Lütfen kontrol edip onaylayın. <strong class="new-meal-label">Yeni yemekler yeşil renkle işaretlenmiştir.</strong></p>
            <div id="csv-preview-list" class="mb-3"></div>
            <div class="d-flex justify-content-between">
                <button id="btn-cancel-csv" class="btn btn-secondary"><i class="fas fa-times"></i> İptal Et</button>
                <button id="btn-commit-csv" class="btn btn-success"><i class="fas fa-check"></i> Onayla ve Veritabanına Kaydet</button>
            </div>
        </div>
    </div>
</div>
