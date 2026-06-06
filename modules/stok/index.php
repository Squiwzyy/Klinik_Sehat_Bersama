<?php
require_once __DIR__ . '/../../includes/header.php';
check_role(['admin', 'apoteker', 'manajer']);

$search = $_GET['search'] ?? '';
$page = (int)($_GET['page'] ?? 1);

$query = "SELECT * FROM obat";
$params = [];
if ($search) {
    $query .= " WHERE nama_obat LIKE :s OR kategori LIKE :s2";
    $params = [':s' => "%$search%", ':s2' => "%$search%"];
}
$query .= " ORDER BY nama_obat ASC";
$result = paginate($pdo, $query, $params, $page, 20);
?>

<div class="page-header">
    <h1><i class="bi bi-box-seam me-2"></i>Master Stok Obat</h1>
    <div class="d-flex gap-2">
        <a href="log.php" class="btn btn-outline-primary"><i class="bi bi-clock-history me-1"></i>Log Stok</a>
        <?php if (in_array($_SESSION['user_role'], ['admin', 'apoteker'])): ?>
        <a href="tambah.php" class="btn btn-primary"><i class="bi bi-plus-circle me-1"></i>Tambah Obat</a>
        <?php endif; ?>
    </div>
</div>

<div class="card mb-4">
    <div class="card-body py-3">
        <form method="GET" class="search-box" style="max-width:350px;">
            <i class="bi bi-search search-icon"></i>
            <input type="text" name="search" class="form-control" placeholder="Cari nama obat / kategori..." value="<?= e($search) ?>">
        </form>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table mb-0">
                <thead><tr><th>Nama Obat</th><th>Kategori</th><th>Satuan</th><th>Harga Beli</th><th>Harga Jual</th><th>Stok</th><th>Aksi</th></tr></thead>
                <tbody>
                <?php if (empty($result['data'])): ?>
                    <tr><td colspan="7" class="text-center py-4 text-muted">Tidak ada data obat.</td></tr>
                <?php else: ?>
                    <?php foreach ($result['data'] as $o): ?>
                    <tr>
                        <td class="fw-semibold"><?= e($o['nama_obat']) ?></td>
                        <td><?= e($o['kategori'] ?: '-') ?></td>
                        <td><?= e($o['satuan']) ?></td>
                        <td><?= format_rupiah($o['harga_beli']) ?></td>
                        <td><?= format_rupiah($o['harga_jual']) ?></td>
                        <td>
                            <?php if ($o['stok'] <= $o['stok_minimum']): ?>
                                <span class="badge bg-danger" title="Stok Minimum: <?= $o['stok_minimum'] ?>"><?= $o['stok'] ?> (Kritis)</span>
                            <?php else: ?>
                                <span class="badge bg-success"><?= $o['stok'] ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (in_array($_SESSION['user_role'], ['admin', 'apoteker'])): ?>
                            <div class="btn-group btn-group-sm">
                                <a href="edit.php?id=<?= $o['id_obat'] ?>" class="btn btn-outline-warning" title="Edit"><i class="bi bi-pencil"></i></a>
                                <a href="log.php?id_obat=<?= $o['id_obat'] ?>" class="btn btn-outline-info" title="Lihat Log"><i class="bi bi-list-check"></i></a>
                            </div>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer bg-white text-center">
        <?php render_pagination($result, 'index.php'); ?>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
