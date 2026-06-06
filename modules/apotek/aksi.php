<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

// POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_role(['admin', 'apoteker']);
    $action = $_POST['action'] ?? '';
    
    if ($action === 'selesai_proses') {
        verify_csrf();
        $id_resep = (int)$_POST['id_resep'];
        $details = $_POST['detail'] ?? [];
        
        try {
            $pdo->beginTransaction();
            
            // Verifikasi stok & update detail
            $stmt_update_detail = $pdo->prepare("UPDATE detail_resep SET jumlah = :jml, id_obat_substitusi = :sub WHERE id_detail_resep = :id");
            $stmt_cek_stok = $pdo->prepare("SELECT nama_obat, stok FROM obat WHERE id_obat = :id FOR UPDATE");
            $stmt_kurangi_stok = $pdo->prepare("UPDATE obat SET stok = stok - :jml WHERE id_obat = :id");
            $stmt_log_stok = $pdo->prepare("INSERT INTO stok_obat_log (id_obat, tipe, jumlah, stok_sesudah, referensi_id, referensi_tipe, keterangan, id_user) VALUES (:io, 'keluar', :jml, :ss, :ref, 'resep', 'Penjualan Resep', :uid)");
            
            foreach ($details as $id_detail => $d) {
                $jml = (int)$d['jumlah'];
                $id_obat_asli = (int)$d['id_obat_asli'];
                $id_sub = !empty($d['id_obat_substitusi']) ? (int)$d['id_obat_substitusi'] : null;
                $id_obat_final = $id_sub ?: $id_obat_asli;
                
                $stmt_cek_stok->execute([':id' => $id_obat_final]);
                $obat = $stmt_cek_stok->fetch();
                
                if (!$obat || $obat['stok'] < $jml) {
                    throw new Exception("Stok obat {$obat['nama_obat']} tidak mencukupi. Sisa: " . ($obat['stok']??0));
                }
                
                // Update detail resep
                $stmt_update_detail->execute([
                    ':jml' => $jml,
                    ':sub' => $id_sub,
                    ':id'  => $id_detail
                ]);
                
                // Kurangi stok
                $stmt_kurangi_stok->execute([':jml' => $jml, ':id' => $id_obat_final]);
                
                // Log stok
                $stok_sesudah = $obat['stok'] - $jml;
                $stmt_log_stok->execute([
                    ':io'  => $id_obat_final,
                    ':jml' => $jml,
                    ':ss'  => $stok_sesudah,
                    ':ref' => $id_resep,
                    ':uid' => $_SESSION['user_id']
                ]);
            }
            
            // Update resep status
            $pdo->prepare("UPDATE resep SET status = 'siap' WHERE id_resep = :id")->execute([':id' => $id_resep]);
            
            $pdo->commit();
            set_flash('success', 'Resep berhasil diproses dan siap diambil. Stok obat telah dikurangi.');
            redirect('index.php');
            
        } catch (Exception $e) {
            $pdo->rollBack();
            set_flash('error', $e->getMessage());
            redirect("proses.php?id=$id_resep");
        }
    }
}

// GET actions
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    check_role(['admin', 'apoteker']);
    $action = $_GET['action'] ?? '';
    
    if ($action === 'diambil') {
        $id_resep = (int)$_GET['id'];
        $pdo->prepare("UPDATE resep SET status = 'diambil' WHERE id_resep = :id AND status = 'siap'")->execute([':id' => $id_resep]);
        set_flash('success', 'Status resep berhasil diubah menjadi diambil.');
        redirect('index.php?status=siap');
    }
}

redirect('index.php');
