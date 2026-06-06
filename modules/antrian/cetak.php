<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT a.*, p.nama_lengkap, p.no_rekam_medis, d.nama_dokter, d.spesialisasi FROM antrian a JOIN pasien p ON a.id_pasien = p.id_pasien JOIN dokter d ON a.id_dokter = d.id_dokter WHERE a.id_antrian = :id");
$stmt->execute([':id' => $id]);
$a = $stmt->fetch();
if (!$a) { echo "Antrian tidak ditemukan."; exit; }
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Nomor Antrian - <?= $a['no_antrian'] ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;900&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; display: flex; justify-content: center; align-items: center; min-height: 100vh; background: #f1f5f9; }
        .ticket { width: 400px; background: #fff; border-radius: 20px; box-shadow: 0 10px 40px rgba(0,0,0,0.1); overflow: hidden; text-align: center; }
        .ticket-header { background: linear-gradient(135deg, #0f766e, #0d9488, #0ea5e9); color: #fff; padding: 24px; }
        .ticket-header h2 { font-size: 1.1rem; font-weight: 600; }
        .ticket-number { font-size: 5rem; font-weight: 900; color: #0d9488; padding: 24px; line-height: 1; }
        .ticket-body { padding: 0 24px 24px; }
        .ticket-body table { width: 100%; text-align: left; font-size: 0.9rem; }
        .ticket-body td { padding: 6px 0; }
        .ticket-body td:first-child { color: #64748b; width: 40%; }
        .ticket-footer { background: #f8fafc; padding: 16px; font-size: 0.8rem; color: #94a3b8; border-top: 2px dashed #e2e8f0; }
        @media print {
            body { background: #fff; }
            .ticket { box-shadow: none; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>
    <div>
        <div class="ticket">
            <div class="ticket-header">
                <h2>🏥 KLINIK SEHAT BERSAMA</h2>
                <small>Nomor Antrian</small>
            </div>
            <div class="ticket-number"><?= str_pad($a['no_antrian'], 3, '0', STR_PAD_LEFT) ?></div>
            <div class="ticket-body">
                <table>
                    <tr><td>Pasien</td><td><strong><?= e($a['nama_lengkap']) ?></strong></td></tr>
                    <tr><td>No. RM</td><td><?= e($a['no_rekam_medis']) ?></td></tr>
                    <tr><td>Dokter</td><td><?= e($a['nama_dokter']) ?></td></tr>
                    <tr><td>Spesialisasi</td><td><?= e($a['spesialisasi']) ?></td></tr>
                    <tr><td>Tanggal</td><td><?= date('d/m/Y', strtotime($a['tanggal_kunjungan'])) ?></td></tr>
                    <tr><td>Jam Datang</td><td><?= $a['jam_kedatangan'] ?></td></tr>
                </table>
            </div>
            <div class="ticket-footer">
                Harap menunggu nomor antrian Anda dipanggil.<br>Terima kasih atas kunjungan Anda.
            </div>
        </div>
        <div class="text-center mt-3 no-print" style="text-align: center; margin-top: 1rem;">
            <button onclick="window.history.back()" style="padding:10px 24px; background:#64748b; color:#fff; border:none; border-radius:8px; cursor:pointer; font-family:Inter; font-weight:600; margin-right: 10px;">
                ⬅ Kembali
            </button>
            <button onclick="window.print()" style="padding:10px 24px; background:#0d9488; color:#fff; border:none; border-radius:8px; cursor:pointer; font-family:Inter; font-weight:600;">
                🖨 Cetak
            </button>
        </div>
    </div>
</body>
</html>
