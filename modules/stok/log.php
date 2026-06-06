<?php
require_once __DIR__ . '/../../includes/header.php';
check_role(['admin', 'apoteker', 'manajer']);

$id_obat = (int)($_GET['id_obat'] ?? 0);
$page = (int)($_GET['page'] ?? 1);

$query = "SELECT l.*, o.nama_obat, u.nama as nama_user FROM stok_obat_log l JOIN obat o ON l.id_obat = o.id_obat JOIN users u ON l.id_user = u.id_user";
$params = [];
if ($id_obat) {
    $query .= " WHERE l.id_obat = :id";
    $params[':id'] = $id_obat;
}
$query .= " ORDER BY l.created_at DESC";

$result = paginate($pdo, $query, $params, $page, 30);
$obatList = $pdo->query("SELECT id_obat, nama_obat FROM obat ORDER BY nama_obat")->fetchAll();
?>

<div class="page-header">
    <h1><i class="bi bi-clock-history me-2"></i>Log Pergerakan Stok Obat</h1>
    <a href="index.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Kembali</a>
</div>

<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="d-flex gap-2" style="max-width:400px;">
            <select name="id_obat" class="form-select" onchange="this.form.submit()">
                <option value="">-- Semua Obat --</option>
                <?php foreach ($obatList as $o): ?>
                    <option value="<?= $o['id_obat'] ?>" <?= $id_obat==$o['id_obat']?'selected':'' ?>><?= e($o['nama_obat']) ?></option>
                <?php endforeach; ?>
            </select>
        </form>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table mb-0">
                <thead><tr><th>Tanggal & Waktu</th><th>Obat</th><th>Tipe</th><th>Jumlah</th><th>Sisa Stok</th><th>Keterangan</th><th>User</th></tr></thead>
                <tbody>
                <?php if (empty($result['data'])): ?>
                    <tr><td colspan="7" class="text-center py-4 text-muted">Tidak ada log pergerakan stok.</td></tr>
                <?php else: ?>
                    <?php foreach ($result['data'] as $l): ?>
                    <tr>
                        <td><?= date('d/m/Y H:i', strtotime($l['created_at'])) ?></td>
                        <td><strong><?= e($l['nama_obat']) ?></strong></td>
                        <td>
                            <?php if ($l['tipe'] === 'masuk'): ?>
                                <span class="badge bg-success"><i class="bi bi-arrow-down-left me-1"></i>Masuk</span>
                            <?php else: ?>
                                <span class="badge bg-danger"><i class="bi bi-arrow-up-right me-1"></i>Keluar</span>
                            <?php endif; ?>
                        </td>
                        <td class="fw-bold <?= $l['tipe'] === 'masuk' ? 'text-success' : 'text-danger' ?>">
                            <?= $l['tipe'] === 'masuk' ? '+' : '-' ?><?= $l['jumlah'] ?>
                        </td>
                        <td><span class="badge bg-secondary"><?= $l['stok_sesudah'] ?></span></td>
                        <td>
                            <?= e($l['keterangan']) ?>
                            <?php if ($l['referensi_tipe'] && $l['referensi_id']): ?>
                                <br><small class="text-muted">Ref: <?= ucfirst($l['referensi_tipe']) ?> #<?= $l['referensi_id'] ?></small>
                            <?php endif; ?>
                        </td>
                        <td><?= e($l['nama_user']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer bg-white text-center">
        <?php render_pagination($result, 'log.php' . ($id_obat ? '?id_obat='.$id_obat.'&' : '?')); ?>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
