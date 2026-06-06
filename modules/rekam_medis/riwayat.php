<?php
require_once __DIR__ . '/../../includes/header.php';
check_role(['admin', 'dokter']);

$id_pasien = (int)($_GET['id_pasien'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM pasien WHERE id_pasien = :id");
$stmt->execute([':id' => $id_pasien]);
$pasien = $stmt->fetch();
if (!$pasien) { set_flash('error', 'Pasien tidak ditemukan.'); redirect('index.php'); }

$riwayat = $pdo->prepare("SELECT rm.*, d.nama_dokter FROM rekam_medis rm JOIN dokter d ON rm.id_dokter = d.id_dokter WHERE rm.id_pasien = :pid ORDER BY rm.tanggal_periksa DESC");
$riwayat->execute([':pid' => $id_pasien]);
$riwayat = $riwayat->fetchAll();
?>

<div class="page-header">
    <h1><i class="bi bi-clock-history me-2"></i>Riwayat Rekam Medis</h1>
    <a href="index.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Kembali</a>
</div>

<div class="card mb-4">
    <div class="card-body">
        <h6 class="fw-bold"><?= e($pasien['nama_lengkap']) ?></h6>
        <span class="text-muted"><code><?= e($pasien['no_rekam_medis']) ?></code> — <?= $pasien['jenis_kelamin']==='L'?'Laki-laki':'Perempuan' ?></span>
    </div>
</div>

<?php if (empty($riwayat)): ?>
    <div class="card"><div class="card-body empty-state"><i class="bi bi-journal-x d-block"></i><p>Belum ada riwayat rekam medis.</p></div></div>
<?php else: ?>
    <?php foreach ($riwayat as $i => $r): ?>
    <div class="card mb-3 fade-in" style="animation-delay: <?= $i*0.05 ?>s">
        <div class="card-header bg-white py-3 d-flex justify-content-between">
            <h6 class="mb-0 fw-bold"><?= date('d/m/Y H:i', strtotime($r['tanggal_periksa'])) ?></h6>
            <div class="d-flex align-items-center gap-2">
                <span class="text-muted"><?= e($r['nama_dokter']) ?></span>
                <?php if ($_SESSION['user_role'] === 'admin'): ?>
                <a href="aksi.php?action=hapus_rm&id=<?= $r['id_rekam_medis'] ?>&id_pasien=<?= $id_pasien ?>" class="btn btn-sm btn-outline-danger" title="Hapus Rekam Medis" data-confirm="Hapus rekam medis ini beserta resep terkait?"><i class="bi bi-trash"></i></a>
                <?php endif; ?>
            </div>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label text-muted">Anamnesis</label>
                    <p><?= e($r['anamnesis']) ?></p>
                </div>
                <div class="col-md-6">
                    <label class="form-label text-muted">Pemeriksaan Fisik</label>
                    <p><?= e($r['pemeriksaan_fisik'] ?: '-') ?></p>
                </div>
                <div class="col-md-6">
                    <label class="form-label text-muted">Diagnosis</label>
                    <p><strong><?= e($r['diagnosis']) ?></strong> <?= $r['kode_icd'] ? '<code>('.e($r['kode_icd']).')</code>' : '' ?></p>
                </div>
                <div class="col-md-6">
                    <label class="form-label text-muted">Tindakan</label>
                    <p><?= e($r['tindakan'] ?: '-') ?></p>
                </div>
                <?php if ($r['catatan_dokter']): ?>
                <div class="col-12">
                    <label class="form-label text-muted">Catatan Dokter</label>
                    <p><?= e($r['catatan_dokter']) ?></p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
<?php endif; ?>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
