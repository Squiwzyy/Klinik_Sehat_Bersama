<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
check_role(['admin', 'pendaftaran']);

$action = $_POST['action'] ?? '';

if ($action === 'tambah') {
    verify_csrf();
    $stmt = $pdo->prepare("INSERT INTO pasien (no_rekam_medis, nama_lengkap, tanggal_lahir, jenis_kelamin, alamat, no_telepon, email, golongan_darah, alergi) VALUES (:nrm, :nama, :tgl, :jk, :alamat, :telp, :email, :gd, :alergi)");
    $stmt->execute([
        ':nrm'    => $_POST['no_rekam_medis'],
        ':nama'   => trim($_POST['nama_lengkap']),
        ':tgl'    => $_POST['tanggal_lahir'],
        ':jk'     => $_POST['jenis_kelamin'],
        ':alamat' => trim($_POST['alamat'] ?? ''),
        ':telp'   => trim($_POST['no_telepon'] ?? ''),
        ':email'  => trim($_POST['email'] ?? ''),
        ':gd'     => $_POST['golongan_darah'] ?? null,
        ':alergi' => trim($_POST['alergi'] ?? ''),
    ]);
    set_flash('success', 'Pasien berhasil didaftarkan dengan No. RM: ' . $_POST['no_rekam_medis']);
    redirect('index.php');
}

if ($action === 'edit') {
    verify_csrf();
    $stmt = $pdo->prepare("UPDATE pasien SET nama_lengkap=:nama, tanggal_lahir=:tgl, jenis_kelamin=:jk, alamat=:alamat, no_telepon=:telp, email=:email, golongan_darah=:gd, alergi=:alergi WHERE id_pasien=:id");
    $stmt->execute([
        ':nama'   => trim($_POST['nama_lengkap']),
        ':tgl'    => $_POST['tanggal_lahir'],
        ':jk'     => $_POST['jenis_kelamin'],
        ':alamat' => trim($_POST['alamat'] ?? ''),
        ':telp'   => trim($_POST['no_telepon'] ?? ''),
        ':email'  => trim($_POST['email'] ?? ''),
        ':gd'     => $_POST['golongan_darah'] ?? null,
        ':alergi' => trim($_POST['alergi'] ?? ''),
        ':id'     => (int)$_POST['id_pasien'],
    ]);
    set_flash('success', 'Data pasien berhasil diperbarui.');
    redirect('index.php');
}

if ($action === 'hapus') {
    verify_csrf();
    $id = (int)$_POST['id_pasien'];
    
    // Cek apakah pasien memiliki data terkait (antrian, rekam medis, dll)
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM antrian WHERE id_pasien = :id");
    $stmt->execute([':id' => $id]);
    $has_antrian = $stmt->fetchColumn() > 0;
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM rekam_medis WHERE id_pasien = :id");
    $stmt->execute([':id' => $id]);
    $has_rekam = $stmt->fetchColumn() > 0;
    
    if ($has_antrian || $has_rekam) {
        set_flash('error', 'Pasien tidak dapat dihapus karena masih memiliki data antrian atau rekam medis.');
    } else {
        $stmt = $pdo->prepare("DELETE FROM pasien WHERE id_pasien = :id");
        $stmt->execute([':id' => $id]);
        set_flash('success', 'Data pasien berhasil dihapus.');
    }
    redirect('index.php');
}

redirect('index.php');
