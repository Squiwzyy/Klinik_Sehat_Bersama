<?php
require_once __DIR__ . '/../../includes/header.php';
check_role(['admin', 'apoteker']);

$id_resep = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT r.*, p.nama_lengkap, p.no_rekam_medis, d.nama_dokter FROM resep r JOIN pasien p ON r.id_pasien = p.id_pasien JOIN dokter d ON r.id_dokter = d.id_dokter WHERE r.id_resep = :id");
$stmt->execute([':id' => $id_resep]);
$resep = $stmt->fetch();
if (!$resep) { set_flash('error', 'Resep tidak ditemukan.'); redirect('index.php'); }

// Update status to diproses if still pending
if ($resep['status'] === 'pending') {
    $pdo->prepare("UPDATE resep SET status = 'diproses', id_apoteker = :uid WHERE id_resep = :id")->execute([':uid' => $_SESSION['user_id'], ':id' => $id_resep]);
    $resep['status'] = 'diproses';
}

$stmt_detail = $pdo->prepare("SELECT dr.*, o.nama_obat, o.stok, o.harga_jual, os.nama_obat as nama_substitusi FROM detail_resep dr JOIN obat o ON dr.id_obat = o.id_obat LEFT JOIN obat os ON dr.id_obat_substitusi = os.id_obat WHERE dr.id_resep = :id");
$stmt_detail->execute([':id' => $id_resep]);
$detail = $stmt_detail->fetchAll();

$obatList = $pdo->query("SELECT * FROM obat WHERE stok > 0 ORDER BY nama_obat")->fetchAll();

$is_readonly = in_array($resep['status'], ['siap', 'diambil']);
?>

<div class="page-header">
    <h1><i class="bi bi-ui-checks me-2"></i>Proses Resep</h1>
    <a href="index.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Kembali</a>
</div>

<div class="row g-4">
    <div class="col-lg-4">
        <div class="card mb-4">
            <div class="card-header bg-white py-3"><h6 class="mb-0 fw-bold">Informasi Resep</h6></div>
            <div class="card-body">
                <p class="mb-1 text-muted">Tanggal: <?= date('d/m/Y H:i', strtotime($resep['tanggal_resep'])) ?></p>
                <p class="mb-1"><strong>Pasien:</strong> <?= e($resep['nama_lengkap']) ?> (<?= e($resep['no_rekam_medis']) ?>)</p>
                <p class="mb-1"><strong>Dokter:</strong> <?= e($resep['nama_dokter']) ?></p>
                <p class="mb-0"><strong>Status:</strong> <span class="badge <?= status_badge($resep['status']) ?>"><?= ucfirst($resep['status']) ?></span></p>
                <?php if ($resep['catatan']): ?>
                    <div class="alert alert-warning mt-3 mb-0"><strong>Catatan Dokter:</strong><br><?= nl2br(e($resep['catatan'])) ?></div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-lg-8">
        <form method="POST" action="aksi.php">
            <?php csrf_field(); ?>
            <input type="hidden" name="action" value="selesai_proses">
            <input type="hidden" name="id_resep" value="<?= $id_resep ?>">
            
            <div class="card">
                <div class="card-header bg-white py-3"><h6 class="mb-0 fw-bold">Detail Obat</h6></div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table mb-0">
                            <thead><tr><th>Obat Dipesan</th><th>Aturan & Dosis</th><th>Jumlah</th><th>Stok Saat Ini</th><th>Substitusi (Jika Habis)</th></tr></thead>
                            <tbody>
                                <?php $stok_aman = true; foreach ($detail as $d): ?>
                                <tr>
                                    <td class="fw-semibold">
                                        <?= e($d['nama_obat']) ?>
                                        <input type="hidden" name="detail[<?= $d['id_detail_resep'] ?>][id_obat_asli]" value="<?= $d['id_obat'] ?>">
                                    </td>
                                    <td><?= e($d['dosis']) ?><br><small class="text-muted"><?= e($d['aturan_pakai']) ?></small></td>
                                    <td>
                                        <?php if ($is_readonly): ?>
                                            <?= $d['jumlah'] ?>
                                        <?php else: ?>
                                            <input type="number" name="detail[<?= $d['id_detail_resep'] ?>][jumlah]" class="form-control form-control-sm" value="<?= $d['jumlah'] ?>" min="1" style="width:70px;">
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($d['stok'] >= $d['jumlah']): ?>
                                            <span class="badge bg-success"><?= $d['stok'] ?></span>
                                        <?php else: $stok_aman = false; ?>
                                            <span class="badge bg-danger"><?= $d['stok'] ?> (Kurang)</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($is_readonly): ?>
                                            <?= $d['nama_substitusi'] ? e($d['nama_substitusi']) : '-' ?>
                                        <?php else: ?>
                                            <select name="detail[<?= $d['id_detail_resep'] ?>][id_obat_substitusi]" class="form-select form-select-sm" <?= $d['stok'] >= $d['jumlah'] ? '' : 'required' ?>>
                                                <option value="">-- Pilih Jika Perlu --</option>
                                                <?php foreach ($obatList as $o): if ($o['id_obat'] == $d['id_obat']) continue; ?>
                                                    <option value="<?= $o['id_obat'] ?>" <?= $d['id_obat_substitusi']==$o['id_obat']?'selected':'' ?>><?= e($o['nama_obat']) ?> (<?= $o['stok'] ?>)</option>
                                                <?php endforeach; ?>
                                            </select>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php if (!$is_readonly): ?>
                <div class="card-footer bg-white">
                    <?php if (!$stok_aman): ?>
                        <div class="alert alert-danger py-2 mb-3"><i class="bi bi-exclamation-circle me-1"></i>Ada obat yang stoknya tidak mencukupi. Silakan pilih obat substitusi atau kurangi jumlah.</div>
                    <?php endif; ?>
                    <button type="submit" class="btn btn-success" data-confirm="Pastikan semua obat sudah disiapkan. Lanjutkan?"><i class="bi bi-check-circle me-1"></i>Resep Siap Diambil</button>
                </div>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
