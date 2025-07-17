<?php require_once 'config.php'; ?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?? SITE_TITLE; ?></title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FullCalendar CSS -->
    <link href='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/main.min.css' rel='stylesheet' />
    <!-- Özel Stiller -->
    <link rel="stylesheet" href="<?= SITE_URL; ?>/assets/css/style.css">
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm">
  <div class="container">
    <a class="navbar-brand" href="<?= SITE_URL; ?>">
        <!-- Buraya logo eklenebilir -->
        <img src="<?= SITE_URL; ?>/assets/img/logo.png" alt="Akdeniz ��niversitesi Logo" style="height: 40px; display: none;" id="logo-placeholder">
        <?= SITE_TITLE; ?>
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav ms-auto">
        <li class="nav-item">
          <a class="nav-link active" aria-current="page" href="<?= SITE_URL; ?>">Ana Sayfa</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="<?= SITE_URL; ?>/admin.php">Admin Paneli</a>
        </li>
      </ul>
    </div>
  </div>
</nav>

<main class="container my-5">
