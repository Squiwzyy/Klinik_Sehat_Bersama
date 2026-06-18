<?php
require_once __DIR__ . '/../../includes/header.php';
check_role(['admin', 'pendaftaran', 'manajer']);

$search = $_GET['search'] ?? '';
$page = (int)($_GET['page'] ?? 1);

$query = "SELECT * FROM pasien";
$params = [];
if ($search) {
    $query .= " WHERE nama_lengkap LIKE :s OR no_rekam_medis LIKE :s2";
    $params = [':s' => "%$search%", ':s2' => "%$search%"];
}
$query .= " ORDER BY id_pasien DESC";
$result = paginate($pdo, $query, $params, $page);
?>

<div class="page-header">
    <h1><i class="bi bi-people me-2"></i>Data Pasien</h1>
    <a href="tambah.php" class="btn btn-primary"><i class="bi bi-person-plus me-1"></i>Tambah Pasien</a>
</div>

<div class="card">
    <div class="card-header bg-white py-3">
        <form method="GET" class="search-box" style="max-width:350px;">
            <i class="bi bi-search search-icon"></i>
            <input type="text" name="search" class="form-control" placeholder="Cari nama / no. RM..." value="<?= e($search) ?>">
        </form>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table mb-0">
                <thead><tr><th>No. RM</th><th>Nama</th><th>L/P</th><th>Tgl Lahir</th><th>Telepon</th><th>Gol. Darah</th><th>Aksi</th></tr></thead>
                <tbody>
                <?php if (empty($result['data'])): ?>
                    <tr><td colspan="7" class="text-center py-4 text-muted">Tidak ada data pasien.</td></tr>
                <?php else: ?>
                    <?php foreach ($result['data'] as $p): ?>
                    <tr>
                        <td><code><?= e($p['no_rekam_medis']) ?></code></td>
                        <td class="fw-semibold"><?= e($p['nama_lengkap']) ?></td>
                        <td><?= $p['jenis_kelamin'] ?></td>
                        <td><?= date('d/m/Y', strtotime($p['tanggal_lahir'])) ?></td>
                        <td><?= e($p['no_telepon']) ?></td>
                        <td><?= e($p['golongan_darah'] ?: '-') ?></td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <a href="detail.php?id=<?= $p['id_pasien'] ?>" class="btn btn-outline-primary" title="Detail"><i class="bi bi-eye"></i></a>
                                <a href="edit.php?id=<?= $p['id_pasien'] ?>" class="btn btn-outline-warning" title="Edit"><i class="bi bi-pencil"></i></a>
                                <?php if (in_array($_SESSION['user_role'], ['admin', 'pendaftaran'])): ?>
                                <button type="button" class="btn btn-outline-danger" title="Hapus" onclick="confirmDelete(<?= $p['id_pasien'] ?>, '<?= e($p['nama_lengkap']) ?>')"><i class="bi bi-trash"></i></button>
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
    <div class="card-footer bg-white">
        <div class="d-flex justify-content-between align-items-center">
            <small class="text-muted">Total: <?= $result['total'] ?> pasien</small>
            <?php render_pagination($result, 'index.php'); ?>
        </div>
    </div>
</div>

<!-- Modal Konfirmasi Hapus -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h5 class="modal-title text-danger"><i class="bi bi-exclamation-triangle me-2"></i>Konfirmasi Hapus</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Apakah Anda yakin ingin menghapus data pasien <strong id="deleteNama"></strong>?</p>
                <p class="text-muted small">Data yang sudah dihapus tidak dapat dikembalikan.</p>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <form method="POST" action="aksi.php" id="deleteForm">
                    <input type="hidden" name="action" value="hapus">
                    <input type="hidden" name="id_pasien" id="deleteId">
                    <?php csrf_field(); ?>
                    <button type="submit" class="btn btn-danger"><i class="bi bi-trash me-1"></i>Hapus</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function confirmDelete(id, nama) {
    document.getElementById('deleteId').value = id;
    document.getElementById('deleteNama').textContent = nama;
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
}
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
