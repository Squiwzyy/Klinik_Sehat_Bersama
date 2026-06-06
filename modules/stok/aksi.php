<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
check_role(['admin', 'apoteker']);

$action = $_POST['action'] ?? '';

if ($action === 'tambah') {
    verify_csrf();
    $stok = (int)$_POST['stok'];
    
    $stmt = $pdo->prepare("INSERT INTO obat (nama_obat, kategori, satuan, harga_beli, harga_jual, stok, stok_minimum) VALUES (:nama, :kat, :sat, :hb, :hj, :stok, :min)");
    $stmt->execute([
        ':nama' => trim($_POST['nama_obat']),
        ':kat'  => trim($_POST['kategori'] ?? ''),
        ':sat'  => $_POST['satuan'],
        ':hb'   => (float)$_POST['harga_beli'],
        ':hj'   => (float)$_POST['harga_jual'],
        ':stok' => $stok,
        ':min'  => (int)$_POST['stok_minimum']
    ]);
    
    $id_obat = $pdo->lastInsertId();
    
    if ($stok > 0) {
        $stmt_log = $pdo->prepare("INSERT INTO stok_obat_log (id_obat, tipe, jumlah, stok_sesudah, keterangan, id_user) VALUES (:io, 'masuk', :jml, :ss, 'Stok Awal', :uid)");
        $stmt_log->execute([
            ':io'  => $id_obat,
            ':jml' => $stok,
            ':ss'  => $stok,
            ':uid' => $_SESSION['user_id']
        ]);
    }
    
    set_flash('success', 'Data obat berhasil ditambahkan.');
    redirect('index.php');
}

if ($action === 'edit') {
    verify_csrf();
    $stmt = $pdo->prepare("UPDATE obat SET nama_obat=:nama, kategori=:kat, satuan=:sat, harga_beli=:hb, harga_jual=:hj, stok_minimum=:min WHERE id_obat=:id");
    $stmt->execute([
        ':nama' => trim($_POST['nama_obat']),
        ':kat'  => trim($_POST['kategori'] ?? ''),
        ':sat'  => $_POST['satuan'],
        ':hb'   => (float)$_POST['harga_beli'],
        ':hj'   => (float)$_POST['harga_jual'],
        ':min'  => (int)$_POST['stok_minimum'],
        ':id'   => (int)$_POST['id_obat']
    ]);
    set_flash('success', 'Data obat berhasil diperbarui.');
    redirect('index.php');
}

redirect('index.php');
