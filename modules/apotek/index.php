<?php
require_once __DIR__ . '/../../includes/header.php';
check_role(['admin', 'apoteker']);

$status_filter = $_GET['status'] ?? 'pending';

$query = "SELECT r.*, p.nama_lengkap, p.no_rekam_medis, d.nama_dokter, 
    (SELECT COUNT(*) FROM detail_resep dr WHERE dr.id_resep = r.id_resep) as jumlah_obat 
    FROM resep r 
    JOIN pasien p ON r.id_pasien = p.id_pasien 
    JOIN dokter d ON r.id_dokter = d.id_dokter 
    WHERE r.status = :s 
    ORDER BY r.tanggal_resep " . ($status_filter === 'pending' ? 'ASC' : 'DESC');
$stmt = $pdo->prepare($query);
$stmt->execute([':s' => $status_filter]);
$list = $stmt->fetchAll();
?>

<div class="page-header">
    <h1><i class="bi bi-capsule me-2"></i>Apotek & Resep Masuk</h1>
</div>

<div class="card mb-4">
    <div class="card-body p-2 d-flex gap-2 border-bottom">
        <a href="?status=pending" class="btn <?= $status_filter === 'pending' ? 'btn-primary' : 'btn-outline-primary' ?>">Menunggu Diproses</a>
        <a href="?status=diproses" class="btn <?= $status_filter === 'diproses' ? 'btn-primary' : 'btn-outline-primary' ?>">Sedang Diproses</a>
        <a href="?status=siap" class="btn <?= $status_filter === 'siap' ? 'btn-primary' : 'btn-outline-primary' ?>">Siap Diambil</a>
        <a href="?status=diambil" class="btn <?= $status_filter === 'diambil' ? 'btn-primary' : 'btn-outline-primary' ?>">Selesai / Diambil</a>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table mb-0">
                <thead><tr><th>No.</th><th>Tanggal & Waktu</th><th>Pasien</th><th>Dokter</th><th>Jumlah Obat</th><th>Status</th><th>Aksi</th></tr></thead>
                <tbody>
                <?php if (empty($list)): ?>
                    <tr><td colspan="7" class="text-center py-4 text-muted">Tidak ada resep dengan status ini.</td></tr>
                <?php else: ?>
                    <?php $no = 1; foreach ($list as $r): ?>
                    <tr>
                        <td><?= $no++ ?></td>
                        <td><?= date('d/m/Y H:i', strtotime($r['tanggal_resep'])) ?></td>
                        <td><strong><?= e($r['nama_lengkap']) ?></strong><br><small><code><?= e($r['no_rekam_medis']) ?></code></small></td>
                        <td><?= e($r['nama_dokter']) ?></td>
                        <td><span class="badge bg-secondary"><?= $r['jumlah_obat'] ?> jenis</span></td>
                        <td><span class="badge <?= status_badge($r['status']) ?>"><?= ucfirst($r['status']) ?></span></td>
                        <td>
                            <?php if ($r['status'] === 'pending' || $r['status'] === 'diproses'): ?>
                                <a href="proses.php?id=<?= $r['id_resep'] ?>" class="btn btn-sm btn-primary"><i class="bi bi-box-arrow-in-right me-1"></i>Proses</a>
                            <?php else: ?>
                                <a href="proses.php?id=<?= $r['id_resep'] ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-eye"></i> Detail</a>
                            <?php endif; ?>
                            
                            <?php if ($r['status'] === 'siap'): ?>
                                <a href="aksi.php?action=diambil&id=<?= $r['id_resep'] ?>" class="btn btn-sm btn-success mt-1 d-block"><i class="bi bi-check-all me-1"></i>Diambil</a>
                            <?php endif; ?>
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
