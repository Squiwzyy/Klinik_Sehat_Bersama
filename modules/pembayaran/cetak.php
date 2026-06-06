<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

$id_transaksi = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT t.*, p.nama_lengkap, p.no_rekam_medis, d.nama_dokter, u.nama as nama_kasir FROM transaksi_pembayaran t JOIN pasien p ON t.id_pasien = p.id_pasien JOIN antrian a ON t.id_antrian = a.id_antrian JOIN dokter d ON a.id_dokter = d.id_dokter JOIN users u ON t.id_kasir = u.id_user WHERE t.id_transaksi = :id");
$stmt->execute([':id' => $id_transaksi]);
$t = $stmt->fetch();
if (!$t) { echo "Transaksi tidak ditemukan."; exit; }

// Obat
$obat_items = [];
$stmt_resep = $pdo->prepare("SELECT id_resep FROM rekam_medis rm JOIN resep r ON rm.id_rekam_medis = r.id_rekam_medis WHERE rm.id_antrian = :id");
$stmt_resep->execute([':id' => $t['id_antrian']]);
$resep = $stmt_resep->fetch();
if ($resep) {
    $stmt_detail = $pdo->prepare("SELECT dr.*, o.nama_obat as nama_asli, os.nama_obat as nama_sub FROM detail_resep dr JOIN obat o ON dr.id_obat = o.id_obat LEFT JOIN obat os ON dr.id_obat_substitusi = os.id_obat WHERE dr.id_resep = :id");
    $stmt_detail->execute([':id' => $resep['id_resep']]);
    $obat_items = $stmt_detail->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Nota Pembayaran - <?= $t['no_transaksi'] ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Courier+Prime:wght@400;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Courier Prime', monospace; font-size: 14px; background: #f1f5f9; display: flex; justify-content: center; padding: 20px; color: #000; }
        .receipt { width: 380px; background: #fff; padding: 24px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .font-bold { font-weight: bold; }
        .border-b { border-bottom: 1px dashed #000; padding-bottom: 8px; margin-bottom: 8px; }
        table { width: 100%; border-collapse: collapse; }
        td { vertical-align: top; padding: 2px 0; }
        .my-2 { margin: 12px 0; }
        .btn-print { display: block; width: 100%; padding: 12px; background: #0d9488; color: #fff; text-align: center; border: none; border-radius: 6px; cursor: pointer; font-family: inherit; font-size: 16px; margin-top: 20px; font-weight: bold;}
        @media print {
            body { background: #fff; padding: 0; }
            .receipt { width: 100%; box-shadow: none; padding: 0; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    <div>
        <div class="receipt">
            <div class="text-center border-b">
                <h3 style="margin:0 0 5px 0;">KLINIK SEHAT BERSAMA</h3>
                <div>Jl. Merdeka No. 123, Jakarta</div>
                <div>Telp: (021) 555-1234</div>
            </div>
            
            <div class="border-b" style="font-size: 12px;">
                <table>
                    <tr><td width="100">No. TRX</td><td>: <?= $t['no_transaksi'] ?></td></tr>
                    <tr><td>Tanggal</td><td>: <?= date('d/m/Y H:i', strtotime($t['created_at'])) ?></td></tr>
                    <tr><td>Kasir</td><td>: <?= e($t['nama_kasir']) ?></td></tr>
                    <tr><td>Pasien</td><td>: <?= e($t['nama_lengkap']) ?></td></tr>
                    <tr><td>No. RM</td><td>: <?= e($t['no_rekam_medis']) ?></td></tr>
                    <tr><td>Dokter</td><td>: <?= e($t['nama_dokter']) ?></td></tr>
                </table>
            </div>
            
            <div class="border-b my-2">
                <div class="font-bold mb-1">RINCIAN TAGIHAN</div>
                <table>
                    <tr>
                        <td>Jasa Konsultasi</td>
                        <td class="text-right"><?= number_format($t['biaya_konsultasi'], 0, ',', '.') ?></td>
                    </tr>
                    <?php if (!empty($obat_items)): ?>
                        <tr><td colspan="2" class="font-bold" style="padding-top:6px;">Obat & Farmasi:</td></tr>
                        <?php foreach ($obat_items as $o): ?>
                        <tr>
                            <td><?= e($o['nama_sub'] ?: $o['nama_asli']) ?><br><small><?= $o['jumlah'] ?>x @ <?= number_format($o['harga_satuan'],0,',','.') ?></small></td>
                            <td class="text-right"><?= number_format($o['jumlah'] * $o['harga_satuan'], 0, ',', '.') ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </table>
            </div>
            
            <div class="border-b my-2">
                <table>
                    <tr class="font-bold">
                        <td>TOTAL TAGIHAN</td>
                        <td class="text-right"><?= number_format($t['total_tagihan'], 0, ',', '.') ?></td>
                    </tr>
                    <tr>
                        <td>TUNAI / BAYAR</td>
                        <td class="text-right"><?= number_format($t['jumlah_bayar'], 0, ',', '.') ?></td>
                    </tr>
                    <tr>
                        <td>KEMBALIAN</td>
                        <td class="text-right"><?= number_format($t['kembalian'], 0, ',', '.') ?></td>
                    </tr>
                </table>
            </div>
            
            <div class="text-center" style="font-size: 12px; margin-top:16px;">
                Terima kasih atas kunjungan Anda.<br>Semoga lekas sembuh!
            </div>
        </div>
        <div class="no-print" style="display:flex; gap:10px; margin-top:20px;">
            <button onclick="window.location.href='index.php'" class="btn-print" style="background:#64748b;">⬅ Kembali</button>
            <button onclick="window.print()" class="btn-print">🖨 Cetak Struk</button>
        </div>
    </div>
</body>
</html>
