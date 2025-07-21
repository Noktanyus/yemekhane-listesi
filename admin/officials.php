<div class="card">
    <div class="card-header">
        <h3>Yetkili Bilgileri Yönetimi</h3>
    </div>
    <div class="card-body">
        <p>Bu sayfadaki bilgiler, web sitesinin çeşitli alanlarında (örneğin, e-posta şablonları veya altbilgi) kullanılabilir.</p>
        <form id="officials-form">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
            
            <div class="form-group">
                <label for="sks-daire-baskani">S.K.S Daire Başkanı</label>
                <input type="text" id="sks-daire-baskani" name="sks_daire_baskani" class="form-control" placeholder="Ad Soyad">
            </div>
            
            <div class="form-group">
                <label for="yemekhane-mudur-yrd">Yemekhane Müdür Yrd.</label>
                <input type="text" id="yemekhane-mudur-yrd" name="yemekhane_mudur_yrd" class="form-control" placeholder="Ad Soyad">
            </div>
            
            <div class="form-group">
                <label for="diyetisyen">Diyetisyen</label>
                <input type="text" id="diyetisyen" name="diyetisyen" class="form-control" placeholder="Ad Soyad">
            </div>
            
            <div class="form-group">
                <label for="yemekhane-email">Yemekhane İletişim E-postası</label>
                <input type="email" id="yemekhane-email" name="yemekhane_email" class="form-control" placeholder="iletisim@akdeniz.edu.tr">
            </div>
            
            <button type="submit" class="btn btn-primary w-100 mt-3">
                <i class="fas fa-save"></i> Bilgileri Kaydet
            </button>
        </form>
    </div>
</div>
