<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

// POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    // Tambah pengadaan (Draft)
    if ($action === 'tambah') {
        check_role(['admin', 'apoteker']);
        verify_csrf();
        
        $stmt = $pdo->prepare("INSERT INTO pengadaan_obat (id_obat, jumlah_pesan, harga_beli, supplier, status, id_pengaju) VALUES (:io, :jml, :hb, :sup, 'draft', :uid)");
        $stmt->execute([
            ':io'  => (int)$_POST['id_obat'],
            ':jml' => (int)$_POST['jumlah_pesan'],
            ':hb'  => (float)$_POST['harga_beli'],
            ':sup' => trim($_POST['supplier'] ?? ''),
            ':uid' => $_SESSION['user_id']
        ]);
        
        set_flash('success', 'Pengajuan pengadaan berhasil dibuat dan menunggu persetujuan.');
        redirect('index.php');
    }
    
    // Terima barang
    if ($action === 'terima') {
        check_role(['admin', 'apoteker']);
        verify_csrf();
        
        $id_pengadaan = (int)$_POST['id_pengadaan'];
        $tgl_diterima = $_POST['tgl_diterima'];
        $tgl_kadaluarsa = $_POST['tgl_kadaluarsa'];
        $harga_beli = (float)$_POST['harga_beli_aktual'];
        $jumlah_diterima = (int)$_POST['jumlah_diterima'];
        
        try {
            $pdo->beginTransaction();
            
            // Ambil data pengadaan & obat
            $stmt = $pdo->prepare("SELECT p.id_obat, o.stok FROM pengadaan_obat p JOIN obat o ON p.id_obat = o.id_obat WHERE p.id_pengadaan = :id AND p.status = 'disetujui' FOR UPDATE");
            $stmt->execute([':id' => $id_pengadaan]);
            $p = $stmt->fetch();
            
            if (!$p) throw new Exception("Data pengadaan tidak valid atau belum disetujui.");
            
            // Update pengadaan
            $pdo->prepare("UPDATE pengadaan_obat SET status = 'diterima', tgl_diterima = :tgl, tgl_kadaluarsa = :exp, harga_beli = :hb, jumlah_pesan = :jml WHERE id_pengadaan = :id")->execute([
                ':tgl' => $tgl_diterima . ' ' . date('H:i:s'),
                ':exp' => $tgl_kadaluarsa,
                ':hb'  => $harga_beli,
                ':jml' => $jumlah_diterima,
                ':id'  => $id_pengadaan
            ]);
            
            // Update stok & harga beli master
            $stok_sesudah = $p['stok'] + $jumlah_diterima;
            $pdo->prepare("UPDATE obat SET stok = :s, harga_beli = :hb WHERE id_obat = :id")->execute([
                ':s'  => $stok_sesudah,
                ':hb' => $harga_beli,
                ':id' => $p['id_obat']
            ]);
            
            // Log stok masuk
            $pdo->prepare("INSERT INTO stok_obat_log (id_obat, tipe, jumlah, stok_sesudah, referensi_id, referensi_tipe, keterangan, id_user) VALUES (:io, 'masuk', :jml, :ss, :ref, 'pengadaan', 'Penerimaan Pengadaan Obat', :uid)")->execute([
                ':io'  => $p['id_obat'],
                ':jml' => $jumlah_diterima,
                ':ss'  => $stok_sesudah,
                ':ref' => $id_pengadaan,
                ':uid' => $_SESSION['user_id']
            ]);
            
            $pdo->commit();
            set_flash('success', 'Barang pengadaan berhasil diterima. Stok obat telah ditambahkan.');
            redirect('index.php?status=diterima');
            
        } catch (Exception $e) {
            $pdo->rollBack();
            set_flash('error', $e->getMessage());
            redirect("terima.php?id=$id_pengadaan");
        }
    }
}

// GET actions
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? '';
    
    // Setujui
    if ($action === 'setujui') {
        check_role(['admin', 'manajer']);
        $id = (int)$_GET['id'];
        $pdo->prepare("UPDATE pengadaan_obat SET status = 'disetujui', id_penyetuju = :uid, tgl_disetujui = NOW() WHERE id_pengadaan = :id AND status = 'draft'")->execute([
            ':uid' => $_SESSION['user_id'],
            ':id'  => $id
        ]);
        set_flash('success', 'Pengadaan obat berhasil disetujui.');
        redirect('index.php?status=draft');
    }
    
    // Hapus
    if ($action === 'hapus') {
        check_role(['admin', 'apoteker']);
        $id = (int)$_GET['id'];
        $pdo->prepare("DELETE FROM pengadaan_obat WHERE id_pengadaan = :id AND status = 'draft'")->execute([':id' => $id]);
        set_flash('success', 'Pengajuan pengadaan berhasil dihapus.');
        redirect('index.php');
    }
}

redirect('index.php');
