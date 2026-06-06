<?php
/**
 * Klinik Sehat Bersama - Database Configuration
 * Koneksi PDO ke MySQL
 */

define('DB_HOST', 'localhost');
define('DB_NAME', 'klinik_sehat_bersama');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Base URL
define('BASE_URL', '/Klinik_Sehat_Bersama');

// Biaya konsultasi default
define('BIAYA_KONSULTASI', 50000);

try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (PDOException $e) {
    error_log("Database Connection Error: " . $e->getMessage());
    die("Koneksi database gagal. Silakan hubungi administrator.");
}
