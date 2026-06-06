<?php
require_once __DIR__ . '/../../includes/header.php';
check_role(['admin', 'pendaftaran', 'dokter', 'manajer']);

$tanggal = $_GET['tanggal'] ?? date('Y-m-d');
$id_dokter_filter = $_GET['dokter'] ?? '';

$query = "SELECT a.*, p.nama_lengkap, p.no_rekam_medis, d.nama_dokter FROM antrian a JOIN pasien p ON a.id_pasien = p.id_pasien JOIN dokter d ON a.id_dokter = d.id_dokter WHERE a.tanggal_kunjungan = :t";
$params = [':t' => $tanggal];
if ($id_dokter_filter) { $query .= " AND a.id_dokter = :d"; $params[':d'] = $id_dokter_filter; }
$query .= " ORDER BY a.no_antrian ASC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$antrian = $stmt->fetchAll();

$dokterList = $pdo->query("SELECT * FROM dokter ORDER BY nama_dokter")->fetchAll();
?>

<div class="page-header">
    <h1><i class="bi bi-card-list me-2"></i>Antrian Pasien</h1>
    <?php if (in_array($_SESSION['user_role'], ['admin', 'pendaftaran'])): ?>
    <a href="tambah.php" class="btn btn-primary"><i class="bi bi-plus-circle me-1"></i>Buat Antrian</a>
    <?php endif; ?>
</div>

<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label">Tanggal</label>
                <input type="date" name="tanggal" class="form-control" value="<?= e($tanggal) ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label">Dokter</label>
                <select name="dokter" class="form-select">
                    <option value="">Semua Dokter</option>
                    <?php foreach ($dokterList as $d): ?>
                        <option value="<?= $d['id_dokter'] ?>" <?= $id_dokter_filter==$d['id_dokter']?'selected':'' ?>><?= e($d['nama_dokter']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <button type="submit" class="btn btn-primary"><i class="bi bi-funnel me-1"></i>Filter</button>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table mb-0">
                <thead><tr><th>No.</th><th>Pasien</th><th>No. RM</th><th>Dokter</th><th>Jam Datang</th><th>Status</th><th>Aksi</th></tr></thead>
                <tbody>
                <?php if (empty($antrian)): ?>
                    <tr><td colspan="7" class="text-center py-4 text-muted">Tidak ada antrian untuk tanggal ini.</td></tr>
                <?php else: ?>
                    <?php foreach ($antrian as $a): ?>
                    <tr>
                        <td><span class="badge bg-primary rounded-pill fs-6"><?= $a['no_antrian'] ?></span></td>
                        <td class="fw-semibold"><?= e($a['nama_lengkap']) ?></td>
                        <td><code><?= e($a['no_rekam_medis']) ?></code></td>
                        <td><?= e($a['nama_dokter']) ?></td>
                        <td><?= $a['jam_kedatangan'] ?></td>
                        <td><span class="badge <?= status_badge($a['status']) ?>"><?= ucfirst($a['status']) ?></span></td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <a href="cetak.php?id=<?= $a['id_antrian'] ?>" class="btn btn-outline-secondary" title="Cetak" target="_blank"><i class="bi bi-printer"></i></a>
                                <?php if ($a['status'] === 'menunggu'): ?>
                                    <?php if (in_array($_SESSION['user_role'], ['admin', 'dokter'])): ?>
                                    <a href="aksi.php?action=panggil&id=<?= $a['id_antrian'] ?>" class="btn btn-outline-info" title="Panggil"><i class="bi bi-megaphone"></i></a>
                                    <?php endif; ?>
                                    <?php if (in_array($_SESSION['user_role'], ['admin', 'pendaftaran'])): ?>
                                    <a href="aksi.php?action=batal&id=<?= $a['id_antrian'] ?>" class="btn btn-outline-danger" title="Batalkan" data-confirm="Batalkan antrian ini?"><i class="bi bi-x-circle"></i></a>
                                    <?php endif; ?>
                                <?php elseif ($a['status'] === 'dipanggil'): ?>
                                    <?php if (in_array($_SESSION['user_role'], ['admin', 'dokter'])): ?>
                                    <a href="aksi.php?action=selesai&id=<?= $a['id_antrian'] ?>" class="btn btn-outline-success" title="Selesai"><i class="bi bi-check-circle"></i></a>
                                    <?php endif; ?>
                                <?php endif; ?>
                                <?php if ($_SESSION['user_role'] === 'admin'): ?>
                                <a href="aksi.php?action=hapus&id=<?= $a['id_antrian'] ?>" class="btn btn-outline-danger" title="Hapus" data-confirm="Hapus antrian ini beserta data terkait?"><i class="bi bi-trash"></i></a>
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
