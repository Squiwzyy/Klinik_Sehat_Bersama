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

// GET actions
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? '';

    if ($action === 'delete') {
        $id = (int)$_GET['id'];

        // Check for related records that would block deletion
        // Note: stok_obat_log is NOT blocking — it will be cascade-deleted
        $checks = [
            ['table' => 'detail_resep',   'column' => 'id_obat',  'label' => 'Detail Resep'],
            ['table' => 'detail_resep',   'column' => 'id_obat_substitusi', 'label' => 'Substitusi Resep'],
            ['table' => 'pengadaan_obat', 'column' => 'id_obat',  'label' => 'Pengadaan Obat'],
        ];

        $blocking = [];
        foreach ($checks as $chk) {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM {$chk['table']} WHERE {$chk['column']} = :id");
            $stmt->execute([':id' => $id]);
            $count = $stmt->fetchColumn();
            if ($count > 0) {
                $blocking[] = "{$chk['label']} ({$count} data)";
            }
        }

        if (!empty($blocking)) {
            set_flash('error', 'Obat tidak dapat dihapus karena masih memiliki data terkait: ' . implode(', ', $blocking) . '.');
            redirect('index.php');
        }

        try {
            $pdo->beginTransaction();
            // Cascade-delete log stok (historical tracking data)
            $pdo->prepare("DELETE FROM stok_obat_log WHERE id_obat = :id")->execute([':id' => $id]);
            // Delete the obat record
            $pdo->prepare("DELETE FROM obat WHERE id_obat = :id")->execute([':id' => $id]);
            $pdo->commit();
            set_flash('success', 'Data obat berhasil dihapus.');
        } catch (Exception $e) {
            $pdo->rollBack();
            set_flash('error', 'Gagal menghapus obat: ' . $e->getMessage());
        }
        redirect('index.php');
    }
}

redirect('index.php');

