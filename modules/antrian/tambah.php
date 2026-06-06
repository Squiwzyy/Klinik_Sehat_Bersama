<?php
require_once __DIR__ . '/../../includes/header.php';
check_role(['admin', 'pendaftaran']);

$dokterList = $pdo->query("SELECT * FROM dokter ORDER BY nama_dokter")->fetchAll();

// Search pasien
$search_pasien = $_GET['sp'] ?? '';
$pasienList = [];
if ($search_pasien) {
    $stmt = $pdo->prepare("SELECT * FROM pasien WHERE nama_lengkap LIKE :s OR no_rekam_medis LIKE :s2 ORDER BY nama_lengkap LIMIT 20");
    $stmt->execute([':s' => "%$search_pasien%", ':s2' => "%$search_pasien%"]);
    $pasienList = $stmt->fetchAll();
}
?>

<div class="page-header">
    <h1><i class="bi bi-plus-circle me-2"></i>Buat Antrian</h1>
    <a href="index.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Kembali</a>
</div>

<div class="row g-4">
    <div class="col-lg-5">
        <div class="card">
            <div class="card-header bg-white py-3"><h6 class="mb-0 fw-bold"><i class="bi bi-search me-2"></i>Cari Pasien</h6></div>
            <div class="card-body">
                <form method="GET">
                    <div class="input-group">
                        <input type="text" name="sp" class="form-control" placeholder="Nama / No. RM..." value="<?= e($search_pasien) ?>">
                        <button class="btn btn-primary"><i class="bi bi-search"></i></button>
                    </div>
                </form>
                <?php if ($search_pasien): ?>
                <div class="mt-3">
                    <?php if (empty($pasienList)): ?>
                        <p class="text-muted">Pasien tidak ditemukan. <a href="<?= BASE_URL ?>/modules/pasien/tambah.php">Daftarkan pasien baru</a></p>
                    <?php else: ?>
                        <div class="list-group">
                        <?php foreach ($pasienList as $p): ?>
                            <a href="?sp=<?= urlencode($search_pasien) ?>&pid=<?= $p['id_pasien'] ?>" class="list-group-item list-group-item-action <?= ($_GET['pid']??'')==$p['id_pasien']?'active':'' ?>">
                                <strong><?= e($p['nama_lengkap']) ?></strong><br>
                                <small><code><?= e($p['no_rekam_medis']) ?></code> — <?= $p['jenis_kelamin'] === 'L' ? 'Laki-laki' : 'Perempuan' ?></small>
                            </a>
                        <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-lg-7">
        <?php
        $pid = (int)($_GET['pid'] ?? 0);
        $pasien = null;
        if ($pid) {
            $stmt = $pdo->prepare("SELECT * FROM pasien WHERE id_pasien = :id");
            $stmt->execute([':id' => $pid]);
            $pasien = $stmt->fetch();
        }
        ?>
        <div class="card">
            <div class="card-header bg-white py-3"><h6 class="mb-0 fw-bold"><i class="bi bi-card-list me-2"></i>Form Antrian</h6></div>
            <div class="card-body">
                <?php if (!$pasien): ?>
                    <div class="empty-state"><i class="bi bi-arrow-left-circle d-block"></i><p>Pilih pasien terlebih dahulu dari panel pencarian.</p></div>
                <?php else: ?>
                <div class="alert alert-info d-flex gap-2 align-items-center mb-4">
                    <i class="bi bi-person-check fs-5"></i>
                    <div>Pasien: <strong><?= e($pasien['nama_lengkap']) ?></strong> (<?= e($pasien['no_rekam_medis']) ?>)</div>
                </div>
                <form method="POST" action="aksi.php">
                    <?php csrf_field(); ?>
                    <input type="hidden" name="action" value="tambah">
                    <input type="hidden" name="id_pasien" value="<?= $pasien['id_pasien'] ?>">
                    
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Dokter <span class="text-danger">*</span></label>
                            <select name="id_dokter" class="form-select" required>
                                <option value="">-- Pilih Dokter --</option>
                                <?php foreach ($dokterList as $d): ?>
                                    <option value="<?= $d['id_dokter'] ?>"><?= e($d['nama_dokter']) ?> — <?= e($d['spesialisasi']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Tanggal Kunjungan <span class="text-danger">*</span></label>
                            <input type="date" name="tanggal_kunjungan" class="form-control" value="<?= date('Y-m-d') ?>" required>
                        </div>
                    </div>
                    
                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i>Buat Antrian</button>
                    </div>
                </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
