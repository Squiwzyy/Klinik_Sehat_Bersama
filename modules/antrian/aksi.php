<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

// POST action - tambah antrian
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_role(['admin', 'pendaftaran']);
    $action = $_POST['action'] ?? '';
    
    if ($action === 'tambah') {
        verify_csrf();
        $id_pasien = (int)$_POST['id_pasien'];
        $id_dokter = (int)$_POST['id_dokter'];
        $tanggal = $_POST['tanggal_kunjungan'];
        $no_antrian = generate_no_antrian($pdo, $id_dokter, $tanggal);
        
        $stmt = $pdo->prepare("INSERT INTO antrian (id_pasien, id_dokter, tanggal_kunjungan, no_antrian, jam_kedatangan, status, id_petugas) VALUES (:ip, :id, :tgl, :no, :jam, 'menunggu', :pet)");
        $stmt->execute([
            ':ip'  => $id_pasien,
            ':id'  => $id_dokter,
            ':tgl' => $tanggal,
            ':no'  => $no_antrian,
            ':jam' => date('H:i:s'),
            ':pet' => $_SESSION['user_id'],
        ]);
        $id_antrian = $pdo->lastInsertId();
        set_flash('success', "Antrian berhasil dibuat. Nomor antrian: $no_antrian");
        redirect('cetak.php?id=' . $id_antrian);
    }
}

// GET actions - update status
$action = $_GET['action'] ?? '';
$id = (int)($_GET['id'] ?? 0);

if ($action && $id) {
    // Handle delete - admin only
    if ($action === 'hapus') {
        check_role(['admin']);
        try {
            $pdo->beginTransaction();
            // Delete detail_resep -> resep -> rekam_medis linked to this antrian
            $pdo->prepare("DELETE dr FROM detail_resep dr JOIN resep r ON dr.id_resep = r.id_resep JOIN rekam_medis rm ON r.id_rekam_medis = rm.id_rekam_medis WHERE rm.id_antrian = :id")->execute([':id' => $id]);
            $pdo->prepare("DELETE r FROM resep r JOIN rekam_medis rm ON r.id_rekam_medis = rm.id_rekam_medis WHERE rm.id_antrian = :id")->execute([':id' => $id]);
            $pdo->prepare("DELETE FROM rekam_medis WHERE id_antrian = :id")->execute([':id' => $id]);
            // Delete transaksi_pembayaran linked to this antrian
            $pdo->prepare("DELETE FROM transaksi_pembayaran WHERE id_antrian = :id")->execute([':id' => $id]);
            // Delete the antrian itself
            $pdo->prepare("DELETE FROM antrian WHERE id_antrian = :id")->execute([':id' => $id]);
            $pdo->commit();
            set_flash('success', 'Data antrian beserta riwayat terkait berhasil dihapus.');
        } catch (Exception $e) {
            $pdo->rollBack();
            set_flash('error', 'Gagal menghapus data: ' . $e->getMessage());
        }
        redirect('index.php');
    }

    // Handle status changes
    $valid_actions = ['panggil' => 'dipanggil', 'selesai' => 'selesai', 'batal' => 'batal'];
    if (isset($valid_actions[$action])) {
        // Enforce role-based access per PRD point 4:
        // - Panggil & Selesai: only Dokter (calls patients based on queue)
        // - Batal: only Petugas Pendaftaran (manages registration)
        if (in_array($action, ['panggil', 'selesai'])) {
            check_role(['admin', 'dokter']);
        } elseif ($action === 'batal') {
            check_role(['admin', 'pendaftaran']);
        }
        $stmt = $pdo->prepare("UPDATE antrian SET status = :s WHERE id_antrian = :id");
        $stmt->execute([':s' => $valid_actions[$action], ':id' => $id]);
        set_flash('success', 'Status antrian berhasil diubah menjadi ' . $valid_actions[$action] . '.');
    }
    // Redirect back to origin page
    $redir = $_GET['redirect'] ?? '';
    if ($redir === 'dashboard') {
        redirect(BASE_URL . '/dashboard/');
    }
    redirect('index.php');
}

redirect('index.php');
