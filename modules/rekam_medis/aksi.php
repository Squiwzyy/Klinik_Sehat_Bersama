<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

// Endpoint untuk AJAX Search ICD-10
if (isset($_GET['action']) && $_GET['action'] === 'search_icd') {
    header('Content-Type: application/json');
    $q = $_GET['q'] ?? '';
    if (strlen($q) < 2) { echo json_encode([]); exit; }
    
    $stmt = $pdo->prepare("SELECT kode, nama_penyakit FROM icd10_codes WHERE kode LIKE :q OR nama_penyakit LIKE :q LIMIT 10");
    $stmt->execute([':q' => "%$q%"]);
    echo json_encode($stmt->fetchAll());
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_role(['admin', 'dokter']);
    $action = $_POST['action'] ?? '';
    
    if ($action === 'simpan_rekam_medis') {
        verify_csrf();
        $id_antrian = (int)$_POST['id_antrian'];
        $id_pasien = (int)$_POST['id_pasien'];
        $id_dokter = (int)$_POST['id_dokter'];
        
        try {
            $pdo->beginTransaction();
            
            // Simpan rekam medis
            $stmt = $pdo->prepare("INSERT INTO rekam_medis (id_antrian, id_pasien, id_dokter, anamnesis, pemeriksaan_fisik, kode_icd, diagnosis, tindakan, catatan_dokter) VALUES (:ia, :ip, :id, :anamnesis, :fisik, :icd, :diagnosis, :tindakan, :catatan)");
            $stmt->execute([
                ':ia'        => $id_antrian,
                ':ip'        => $id_pasien,
                ':id'        => $id_dokter,
                ':anamnesis' => trim($_POST['anamnesis']),
                ':fisik'     => trim($_POST['pemeriksaan_fisik'] ?? ''),
                ':icd'       => trim($_POST['kode_icd'] ?? ''),
                ':diagnosis' => trim($_POST['diagnosis']),
                ':tindakan'  => trim($_POST['tindakan'] ?? ''),
                ':catatan'   => trim($_POST['catatan_dokter'] ?? ''),
            ]);
            $id_rekam_medis = $pdo->lastInsertId();
            
            // Update status antrian -> selesai
            $stmt = $pdo->prepare("UPDATE antrian SET status = 'selesai' WHERE id_antrian = :id");
            $stmt->execute([':id' => $id_antrian]);
            
            // Buat resep jika checkbox dicentang
            if (!empty($_POST['buat_resep']) && !empty($_POST['obat'])) {
                $stmt = $pdo->prepare("INSERT INTO resep (id_rekam_medis, id_pasien, id_dokter, status, catatan) VALUES (:irm, :ip, :id, 'pending', :catatan)");
                $stmt->execute([
                    ':irm'     => $id_rekam_medis,
                    ':ip'      => $id_pasien,
                    ':id'      => $id_dokter,
                    ':catatan' => trim($_POST['catatan_resep'] ?? ''),
                ]);
                $id_resep = $pdo->lastInsertId();
                
                $stmt_detail = $pdo->prepare("INSERT INTO detail_resep (id_resep, id_obat, jumlah, dosis, aturan_pakai, harga_satuan) VALUES (:ir, :io, :jml, :dosis, :aturan, (SELECT harga_jual FROM obat WHERE id_obat = :io2))");
                
                foreach ($_POST['obat'] as $o) {
                    if (!empty($o['id_obat']) && (int)$o['jumlah'] > 0) {
                        $stmt_detail->execute([
                            ':ir'     => $id_resep,
                            ':io'     => (int)$o['id_obat'],
                            ':jml'    => (int)$o['jumlah'],
                            ':dosis'  => trim($o['dosis'] ?? ''),
                            ':aturan' => trim($o['aturan_pakai'] ?? ''),
                            ':io2'    => (int)$o['id_obat'],
                        ]);
                    }
                }
            }
            
            $pdo->commit();
            set_flash('success', 'Pemeriksaan selesai. Data rekam medis ' . (!empty($_POST['buat_resep']) ? '& resep ' : '') . 'berhasil disimpan.');
            redirect('index.php');
            
        } catch (Exception $e) {
            $pdo->rollBack();
            set_flash('error', 'Gagal menyimpan data: ' . $e->getMessage());
            redirect("periksa.php?id_antrian=$id_antrian");
        }
    }
}

// GET action - hapus rekam medis (admin only)
if (isset($_GET['action']) && $_GET['action'] === 'hapus_rm') {
    check_role(['admin']);
    $id_rm = (int)($_GET['id'] ?? 0);
    $id_pasien = (int)($_GET['id_pasien'] ?? 0);
    if ($id_rm) {
        try {
            $pdo->beginTransaction();
            // Delete detail_resep -> resep linked to this rekam_medis
            $pdo->prepare("DELETE dr FROM detail_resep dr JOIN resep r ON dr.id_resep = r.id_resep WHERE r.id_rekam_medis = :id")->execute([':id' => $id_rm]);
            $pdo->prepare("DELETE FROM resep WHERE id_rekam_medis = :id")->execute([':id' => $id_rm]);
            // Delete the rekam_medis itself
            $pdo->prepare("DELETE FROM rekam_medis WHERE id_rekam_medis = :id")->execute([':id' => $id_rm]);
            $pdo->commit();
            set_flash('success', 'Data rekam medis berhasil dihapus.');
        } catch (Exception $e) {
            $pdo->rollBack();
            set_flash('error', 'Gagal menghapus data: ' . $e->getMessage());
        }
    }
    if ($id_pasien) {
        redirect('riwayat.php?id_pasien=' . $id_pasien);
    }
    redirect('index.php');
}

// GET action - hapus semua rekam medis by antrian (admin only, from index page)
if (isset($_GET['action']) && $_GET['action'] === 'hapus_rm_antrian') {
    check_role(['admin']);
    $id_antrian = (int)($_GET['id_antrian'] ?? 0);
    if ($id_antrian) {
        try {
            $pdo->beginTransaction();
            // Delete detail_resep -> resep -> rekam_medis
            $pdo->prepare("DELETE dr FROM detail_resep dr JOIN resep r ON dr.id_resep = r.id_resep JOIN rekam_medis rm ON r.id_rekam_medis = rm.id_rekam_medis WHERE rm.id_antrian = :id")->execute([':id' => $id_antrian]);
            $pdo->prepare("DELETE r FROM resep r JOIN rekam_medis rm ON r.id_rekam_medis = rm.id_rekam_medis WHERE rm.id_antrian = :id")->execute([':id' => $id_antrian]);
            $pdo->prepare("DELETE FROM rekam_medis WHERE id_antrian = :id")->execute([':id' => $id_antrian]);
            // Delete transaksi_pembayaran & antrian
            $pdo->prepare("DELETE FROM transaksi_pembayaran WHERE id_antrian = :id")->execute([':id' => $id_antrian]);
            $pdo->prepare("DELETE FROM antrian WHERE id_antrian = :id")->execute([':id' => $id_antrian]);
            $pdo->commit();
            set_flash('success', 'Data antrian beserta rekam medis berhasil dihapus.');
        } catch (Exception $e) {
            $pdo->rollBack();
            set_flash('error', 'Gagal menghapus data: ' . $e->getMessage());
        }
    }
    redirect('index.php');
}

redirect('index.php');
