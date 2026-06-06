<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';
check_login();
$current_page = basename($_SERVER['SCRIPT_FILENAME'], '.php');
$current_dir = basename(dirname($_SERVER['SCRIPT_FILENAME']));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Sistem Informasi Klinik Sehat Bersama - Dashboard Klinik Rawat Jalan">
    <title>Klinik Sehat Bersama</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet">
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-main fixed-top">
        <div class="container-fluid">
            <button class="btn btn-link text-white sidebar-toggle me-2" id="sidebarToggle">
                <i class="bi bi-list fs-4"></i>
            </button>
            <a class="navbar-brand" href="<?= BASE_URL ?>/dashboard/">
                <i class="bi bi-heart-pulse-fill me-2"></i>Klinik Sehat Bersama
            </a>
            <div class="ms-auto d-flex align-items-center">
                <div class="dropdown">
                    <button class="btn btn-link text-white dropdown-toggle text-decoration-none" data-bs-toggle="dropdown">
                        <i class="bi bi-person-circle me-1"></i>
                        <?= e($_SESSION['user_nama'] ?? 'User') ?>
                        <span class="badge bg-white bg-opacity-25 ms-1"><?= e(ucfirst($_SESSION['user_role'] ?? '')) ?></span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="<?= BASE_URL ?>/auth/logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <!-- Sidebar -->
    <?php include __DIR__ . '/sidebar.php'; ?>

    <!-- Main Content -->
    <main class="main-content" id="mainContent">
        <div class="container-fluid p-4">
            <?php display_flash(); ?>
