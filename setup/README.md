# Proje Kurulumu

Bu klasör, projenin ilk kurulum adımlarını içerir.

Kurulumu başlatmak için web tarayıcınızda `http://localhost/setup/` adresine gidin. (Projenizin URL'sine göre adresi güncelleyin).

Karşınıza çıkan **Kurulum Sihirbazı**, size adım adım yol gösterecektir. Adımları tek tek çalıştırabilir veya "Tüm Kurulumu Başlat" butonu ile tüm süreci otomatikleştirebilirsiniz.

## Adımlar

1.  **Gerekli Kütüphanelerin Kurulumu:** `composer.phar` kullanarak `vendor` klasörünü ve projenin bağımlı olduğu kütüphaneleri kurar.
2.  **Veritabanı Tablolarını Oluştur:** `schema.sql` dosyasını kullanarak veritabanı yapısını oluşturur.
3.  **Başlangıç Verilerini Ekle:** Yönetici paneline giriş yapabilmeniz için varsayılan bir `admin` kullanıcısı oluşturur.

**ÖNEMLİ:** Kurulum tamamlandıktan sonra güvenlik nedeniyle bu `setup` klasörünü silmeniz veya adını değiştirerek erişilemez hale getirmeniz şiddetle tavsiye edilir.
