<?php
require_once __DIR__ . '/../../includes/header.php';
check_role(['admin', 'pendaftaran', 'manajer']);

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM pasien WHERE id_pasien = :id");
$stmt->execute([':id' => $id]);
$p = $stmt->fetch();
if (!$p) { set_flash('error', 'Pasien tidak ditemukan.'); redirect('index.php'); }

// Riwayat kunjungan
$riwayat = $pdo->prepare("SELECT a.*, d.nama_dokter, rm.diagnosis, rm.kode_icd FROM antrian a LEFT JOIN dokter d ON a.id_dokter = d.id_dokter LEFT JOIN rekam_medis rm ON a.id_antrian = rm.id_antrian WHERE a.id_pasien = :id ORDER BY a.tanggal_kunjungan DESC, a.no_antrian DESC");
$riwayat->execute([':id' => $id]);
$riwayat = $riwayat->fetchAll();
?>

<div class="page-header">
    <h1><i class="bi bi-person me-2"></i>Detail Pasien</h1>
    <a href="index.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Kembali</a>
</div>

<div class="row g-4">
    <div class="col-lg-5">
        <div class="card">
            <div class="card-header bg-white py-3"><h6 class="mb-0 fw-bold">Informasi Pasien</h6></div>
            <div class="card-body">
                <table class="table table-borderless mb-0">
                    <tr><td class="text-muted" style="width:40%">No. RM</td><td><code><?= e($p['no_rekam_medis']) ?></code></td></tr>
                    <tr><td class="text-muted">Nama</td><td class="fw-semibold"><?= e($p['nama_lengkap']) ?></td></tr>
                    <tr><td class="text-muted">Tgl Lahir</td><td><?= date('d/m/Y', strtotime($p['tanggal_lahir'])) ?></td></tr>
                    <tr><td class="text-muted">Jenis Kelamin</td><td><?= $p['jenis_kelamin'] === 'L' ? 'Laki-laki' : 'Perempuan' ?></td></tr>
                    <tr><td class="text-muted">Alamat</td><td><?= e($p['alamat'] ?: '-') ?></td></tr>
                    <tr><td class="text-muted">Telepon</td><td><?= e($p['no_telepon'] ?: '-') ?></td></tr>
                    <tr><td class="text-muted">Email</td><td><?= e($p['email'] ?: '-') ?></td></tr>
                    <tr><td class="text-muted">Gol. Darah</td><td><?= e($p['golongan_darah'] ?: '-') ?></td></tr>
                    <tr><td class="text-muted">Alergi</td><td><?= e($p['alergi'] ?: 'Tidak ada') ?></td></tr>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header bg-white py-3"><h6 class="mb-0 fw-bold"><i class="bi bi-clock-history me-2"></i>Riwayat Kunjungan (<?= count($riwayat) ?>)</h6></div>
            <div class="card-body p-0">
                <?php if (empty($riwayat)): ?>
                    <div class="empty-state"><i class="bi bi-calendar-x d-block"></i><p>Belum ada riwayat kunjungan.</p></div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table mb-0">
                        <thead><tr><th>Tanggal</th><th>Dokter</th><th>Diagnosis</th><th>Status</th></tr></thead>
                        <tbody>
                        <?php foreach ($riwayat as $r): ?>
                        <tr>
                            <td><?= date('d/m/Y', strtotime($r['tanggal_kunjungan'])) ?></td>
                            <td><?= e($r['nama_dokter']) ?></td>
                            <td><?= e($r['diagnosis'] ?: '-') ?> <?= $r['kode_icd'] ? '<code>('.e($r['kode_icd']).')</code>' : '' ?></td>
                            <td><span class="badge <?= status_badge($r['status']) ?>"><?= ucfirst($r['status']) ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
