<?php
require_once __DIR__ . '/../../includes/header.php';
check_role(['admin', 'dokter']);

$today = date('Y-m-d');

// Jika dokter, hanya tampilkan pasien dokter tersebut
$where_dokter = '';
$params = [':t' => $today];
if ($_SESSION['user_role'] === 'dokter') {
    $stmt = $pdo->prepare("SELECT id_dokter FROM dokter WHERE id_user = :uid");
    $stmt->execute([':uid' => $_SESSION['user_id']]);
    $dk = $stmt->fetch();
    $where_dokter = " AND a.id_dokter = :did";
    $params[':did'] = $dk['id_dokter'] ?? 0;
}

$stmt = $pdo->prepare("SELECT a.*, p.nama_lengkap, p.no_rekam_medis, d.nama_dokter,
    (SELECT COUNT(*) FROM rekam_medis rm WHERE rm.id_antrian = a.id_antrian) as sudah_periksa
    FROM antrian a JOIN pasien p ON a.id_pasien = p.id_pasien JOIN dokter d ON a.id_dokter = d.id_dokter
    WHERE a.tanggal_kunjungan = :t AND a.status IN ('dipanggil','selesai') $where_dokter
    ORDER BY a.no_antrian ASC");
$stmt->execute($params);
$list = $stmt->fetchAll();
?>

<div class="page-header">
    <h1><i class="bi bi-journal-medical me-2"></i>Rekam Medis</h1>
</div>

<div class="card">
    <div class="card-header bg-white py-3">
        <h6 class="mb-0 fw-bold">Pasien Hari Ini — <?= date('d/m/Y') ?></h6>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table mb-0">
                <thead><tr><th>No.</th><th>Pasien</th><th>No. RM</th><th>Dokter</th><th>Status Antrian</th><th>Rekam Medis</th><th>Aksi</th></tr></thead>
                <tbody>
                <?php if (empty($list)): ?>
                    <tr><td colspan="7" class="text-center py-4 text-muted">Tidak ada pasien untuk diperiksa hari ini.</td></tr>
                <?php else: ?>
                    <?php foreach ($list as $a): ?>
                    <tr>
                        <td><span class="badge bg-primary"><?= $a['no_antrian'] ?></span></td>
                        <td class="fw-semibold"><?= e($a['nama_lengkap']) ?></td>
                        <td><code><?= e($a['no_rekam_medis']) ?></code></td>
                        <td><?= e($a['nama_dokter']) ?></td>
                        <td><span class="badge <?= status_badge($a['status']) ?>"><?= ucfirst($a['status']) ?></span></td>
                        <td>
                            <?php if ($a['sudah_periksa'] > 0): ?>
                                <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Sudah</span>
                            <?php else: ?>
                                <span class="badge bg-warning text-dark"><i class="bi bi-clock me-1"></i>Belum</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="btn-group btn-group-sm">
                            <?php if ($a['sudah_periksa'] == 0 && $a['status'] === 'dipanggil'): ?>
                                <a href="periksa.php?id_antrian=<?= $a['id_antrian'] ?>" class="btn btn-sm btn-primary"><i class="bi bi-journal-medical me-1"></i>Periksa</a>
                            <?php else: ?>
                                <a href="riwayat.php?id_pasien=<?= $a['id_pasien'] ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye me-1"></i>Riwayat</a>
                            <?php endif; ?>
                            <?php if ($_SESSION['user_role'] === 'admin'): ?>
                                <a href="aksi.php?action=hapus_rm_antrian&id_antrian=<?= $a['id_antrian'] ?>" class="btn btn-sm btn-outline-danger" title="Hapus Rekam Medis" data-confirm="Hapus data rekam medis untuk antrian ini?"><i class="bi bi-trash"></i></a>
                            <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
