<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_role(['admin', 'kasir']);
    $action = $_POST['action'] ?? '';
    
    if ($action === 'bayar') {
        verify_csrf();
        
        try {
            $pdo->beginTransaction();
            
            // Insert transaksi
            $stmt = $pdo->prepare("INSERT INTO transaksi_pembayaran (no_transaksi, id_antrian, id_pasien, biaya_konsultasi, biaya_obat, total_tagihan, metode_bayar, jumlah_bayar, kembalian, status, id_kasir) VALUES (:no, :ida, :idp, :bk, :bo, :tot, :metode, :bayar, :kembali, 'lunas', :idk)");
            $stmt->execute([
                ':no'      => $_POST['no_transaksi'],
                ':ida'     => $_POST['id_antrian'],
                ':idp'     => $_POST['id_pasien'],
                ':bk'      => $_POST['biaya_konsultasi'],
                ':bo'      => $_POST['biaya_obat'],
                ':tot'     => $_POST['total_tagihan'],
                ':metode'  => $_POST['metode_bayar'],
                ':bayar'   => $_POST['jumlah_bayar'],
                ':kembali' => $_POST['kembalian'],
                ':idk'     => $_SESSION['user_id']
            ]);
            
            $id_transaksi = $pdo->lastInsertId();
            
            // Jika ada resep, ubah status resep menjadi diambil
            if (!empty($_POST['id_resep'])) {
                $pdo->prepare("UPDATE resep SET status = 'diambil' WHERE id_resep = :idr")->execute([':idr' => $_POST['id_resep']]);
            }
            
            $pdo->commit();
            set_flash('success', 'Pembayaran berhasil diproses.');
            redirect('cetak.php?id=' . $id_transaksi);
            
        } catch (Exception $e) {
            $pdo->rollBack();
            set_flash('error', 'Gagal memproses pembayaran: ' . $e->getMessage());
            redirect('bayar.php?id_antrian=' . (int)$_POST['id_antrian']);
        }
    }
}

redirect('index.php');
