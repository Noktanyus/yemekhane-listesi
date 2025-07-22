<?php

require_once __DIR__ . '/partials/header.php';

// Sayfa yönlendirmesi için varsayılan sayfa
$page = $_GET['page'] ?? 'menu';

// Aktif sekmeyi belirlemek için script
echo "<script>
document.addEventListener('DOMContentLoaded', function() {
    const page = '{$page}';
    document.querySelectorAll('.sidebar-nav .tab-link').forEach(link => {
        link.classList.remove('active');
        if (link.getAttribute('data-tab') === page) {
            link.classList.add('active');
        }
    });
});
</script>";

// Modül dosyalarını ve içeriklerini yönet
$all_pages = ['menu', 'meals', 'upload', 'feedback', 'reports', 'logs', 'officials', 'meal_prices'];

foreach ($all_pages as $p) {
    // Aktif sayfa ile eşleşiyorsa 'active' class'ı ekle, değilse ekleme.
    $is_active = ($p === $page) ? 'active' : '';
    echo "<div class='tab-content {$is_active}' data-page='{$p}'>";

    // İlgili modülün PHP dosyasını dahil et
    include __DIR__ . '/' . $p . '.php';

    echo "</div>";
}

require_once __DIR__ . '/partials/footer.php';
