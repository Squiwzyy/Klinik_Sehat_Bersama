<?php
require_once __DIR__ . '/../../includes/header.php';
check_role(['admin', 'kasir']);

$id_antrian = (int)($_GET['id_antrian'] ?? 0);
$stmt = $pdo->prepare("SELECT a.*, p.nama_lengkap, p.no_rekam_medis, p.alamat, p.no_telepon, d.nama_dokter, rm.id_rekam_medis 
    FROM antrian a 
    JOIN pasien p ON a.id_pasien = p.id_pasien 
    JOIN dokter d ON a.id_dokter = d.id_dokter 
    JOIN rekam_medis rm ON a.id_antrian = rm.id_antrian 
    WHERE a.id_antrian = :id");
$stmt->execute([':id' => $id_antrian]);
$data = $stmt->fetch();
if (!$data) { set_flash('error', 'Data kunjungan tidak valid untuk pembayaran.'); redirect('index.php'); }

// Cek resep dan rincian obat
$stmt_resep = $pdo->prepare("SELECT r.id_resep, r.status FROM resep r WHERE r.id_rekam_medis = :id");
$stmt_resep->execute([':id' => $data['id_rekam_medis']]);
$resep = $stmt_resep->fetch();

$obat_items = [];
$biaya_obat = 0;
if ($resep) {
    $stmt_detail = $pdo->prepare("SELECT dr.*, o.nama_obat as nama_asli, os.nama_obat as nama_sub FROM detail_resep dr JOIN obat o ON dr.id_obat = o.id_obat LEFT JOIN obat os ON dr.id_obat_substitusi = os.id_obat WHERE dr.id_resep = :id");
    $stmt_detail->execute([':id' => $resep['id_resep']]);
    $obat_items = $stmt_detail->fetchAll();
    
    foreach ($obat_items as $o) {
        $biaya_obat += ($o['harga_satuan'] * $o['jumlah']);
    }
}

$biaya_konsultasi = BIAYA_KONSULTASI;
$total_tagihan = $biaya_konsultasi + $biaya_obat;
$no_transaksi = generate_no_transaksi($pdo);
?>

<div class="page-header">
    <h1><i class="bi bi-wallet2 me-2"></i>Proses Pembayaran</h1>
    <a href="index.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Kembali</a>
</div>

<div class="row g-4">
    <div class="col-lg-7">
        <div class="card mb-4">
            <div class="card-header bg-white py-3"><h6 class="mb-0 fw-bold">Rincian Tagihan</h6></div>
            <div class="card-body p-0">
                <table class="table mb-0">
                    <thead class="bg-light"><tr><th>Deskripsi</th><th class="text-end">Jumlah</th></tr></thead>
                    <tbody>
                        <tr>
                            <td>
                                <strong>Jasa Konsultasi Dokter</strong><br>
                                <small class="text-muted"><?= e($data['nama_dokter']) ?> — <?= date('d/m/Y', strtotime($data['tanggal_kunjungan'])) ?></small>
                            </td>
                            <td class="text-end fw-semibold"><?= format_rupiah($biaya_konsultasi) ?></td>
                        </tr>
                        <?php if (!empty($obat_items)): ?>
                            <tr><td colspan="2" class="bg-light fw-bold py-2">Obat & Resep</td></tr>
                            <?php foreach ($obat_items as $o): ?>
                            <tr>
                                <td>
                                    <?= e($o['nama_sub'] ?: $o['nama_asli']) ?><br>
                                    <small class="text-muted"><?= $o['jumlah'] ?> x <?= format_rupiah($o['harga_satuan']) ?></small>
                                </td>
                                <td class="text-end fw-semibold"><?= format_rupiah($o['jumlah'] * $o['harga_satuan']) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                    <tfoot class="bg-light border-top border-2">
                        <tr>
                            <td class="text-end fw-bold fs-5">TOTAL TAGIHAN</td>
                            <td class="text-end fw-bold text-success fs-4"><?= format_rupiah($total_tagihan) ?></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
    
    <div class="col-lg-5">
        <div class="card mb-4">
            <div class="card-header bg-white py-3"><h6 class="mb-0 fw-bold">Data Pasien</h6></div>
            <div class="card-body">
                <p class="mb-1 fw-bold fs-5"><?= e($data['nama_lengkap']) ?></p>
                <p class="mb-1 text-muted">No. RM: <code><?= e($data['no_rekam_medis']) ?></code></p>
                <p class="mb-1 text-muted"><i class="bi bi-telephone me-1"></i><?= e($data['no_telepon'] ?: '-') ?></p>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header bg-white py-3"><h6 class="mb-0 fw-bold">Pembayaran</h6></div>
            <div class="card-body">
                <form method="POST" action="aksi.php" id="formBayar">
                    <?php csrf_field(); ?>
                    <input type="hidden" name="action" value="bayar">
                    <input type="hidden" name="id_antrian" value="<?= $data['id_antrian'] ?>">
                    <input type="hidden" name="id_pasien" value="<?= $data['id_pasien'] ?>">
                    <input type="hidden" name="no_transaksi" value="<?= $no_transaksi ?>">
                    <input type="hidden" name="biaya_konsultasi" value="<?= $biaya_konsultasi ?>">
                    <input type="hidden" name="biaya_obat" value="<?= $biaya_obat ?>">
                    <input type="hidden" name="total_tagihan" id="total_tagihan_value" value="<?= $total_tagihan ?>">
                    <input type="hidden" name="kembalian" id="kembalian" value="0">
                    <input type="hidden" name="id_resep" value="<?= $resep['id_resep'] ?? '' ?>">
                    
                    <div class="mb-3">
                        <label class="form-label">Metode Pembayaran</label>
                        <select name="metode_bayar" class="form-select" required>
                            <option value="tunai">Tunai (Cash)</option>
                            <option value="transfer">Transfer Bank / QRIS</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Jumlah Uang Diterima (Rp)</label>
                        <input type="number" name="jumlah_bayar" id="jumlah_bayar" class="form-control form-control-lg fw-bold" min="<?= $total_tagihan ?>" required>
                        <div class="form-text">Minimal: <?= format_rupiah($total_tagihan) ?></div>
                    </div>
                    <div class="mb-4">
                        <label class="form-label text-muted">Kembalian</label>
                        <div class="fs-3 fw-bold text-primary" id="kembalian_display">Rp 0</div>
                    </div>
                    <button type="submit" class="btn btn-success btn-lg w-100 fw-bold"><i class="bi bi-check2-circle me-2"></i>Simpan & Cetak Bukti</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
