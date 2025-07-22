<?php
session_start();
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../includes/functions.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../login.php'); // Adjust path for subdirectory
    exit;
}
$csrf_token = generate_csrf_token();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Yönetim Paneli - Akdeniz Üniversitesi</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/admin.css?v=<?php echo time(); ?>">
    <link rel="icon" href="../assets/logo.png">
</head>
<body>
    <div class="admin-wrapper">
        <!-- Kenar Çubuğu (Sidebar) -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <img src="../assets/logo.png" alt="Akdeniz Üniversitesi Logo" class="sidebar-logo">
                <div class="sidebar-title">
                    <h4>Yönetim Paneli</h4>
                    <span>Akdeniz Üniversitesi</span>
                </div>
            </div>
            <nav class="sidebar-nav">
                <ul>
                    <!-- Active class will be managed by the main index file -->
                    <li><a href="index.php?page=menu" class="tab-link" data-tab="menu"><i class="fas fa-calendar-alt fa-fw"></i> Menü Yönetimi</a></li>
                    <li><a href="index.php?page=meals" class="tab-link" data-tab="meals"><i class="fas fa-utensils fa-fw"></i> Yemek Havuzu</a></li>
                    <li><a href="index.php?page=upload" class="tab-link" data-tab="upload"><i class="fas fa-file-csv fa-fw"></i> Toplu Yükleme</a></li>
                    <li><a href="index.php?page=feedback" class="tab-link" data-tab="feedback"><i class="fas fa-comments fa-fw"></i> Geri Bildirimler</a></li>
                    <li><a href="index.php?page=reports" class="tab-link" data-tab="reports"><i class="fas fa-chart-bar fa-fw"></i> Raporlar</a></li>
                    <li><a href="index.php?page=logs" class="tab-link" data-tab="logs"><i class="fas fa-clipboard-list fa-fw"></i> İşlem Kayıtları</a></li>
                    <li><a href="index.php?page=officials" class="tab-link" data-tab="officials"><i class="fas fa-users-cog fa-fw"></i> Yetkililer</a></li>
                    <li><a href="index.php?page=meal_prices" class="tab-link" data-tab="meal_prices"><i class="fas fa-money-bill-wave fa-fw"></i> Yemek Ücretleri</a></li>
                </ul>
            </nav>
            <div class="sidebar-footer">
                <a href="../logout.php?csrf_token=<?php echo $csrf_token; ?>" class="logout-link"><i class="fas fa-sign-out-alt"></i> Güvenli Çıkış</a>
                <span>Kullanıcı: <strong><?php echo htmlspecialchars($_SESSION['admin_username']); ?></strong></span>
            </div>
        </aside>
        <!-- Ana İçerik -->
        <main class="main-content">
