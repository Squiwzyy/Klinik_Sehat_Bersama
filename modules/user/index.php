<?php
require_once __DIR__ . '/../../includes/header.php';
check_role('admin');

$search = $_GET['search'] ?? '';
$page = (int)($_GET['page'] ?? 1);

$query = "SELECT * FROM users";
$params = [];
if ($search) {
    $query .= " WHERE nama LIKE :s OR username LIKE :s2 OR role LIKE :s3";
    $params = [':s' => "%$search%", ':s2' => "%$search%", ':s3' => "%$search%"];
}
$query .= " ORDER BY role, nama";
$result = paginate($pdo, $query, $params, $page, 20);
?>

<div class="page-header">
    <h1><i class="bi bi-people-fill me-2"></i>Manajemen User</h1>
    <a href="tambah.php" class="btn btn-primary"><i class="bi bi-person-plus me-1"></i>Tambah User</a>
</div>

<div class="card">
    <div class="card-header bg-white py-3">
        <form method="GET" class="search-box" style="max-width:350px;">
            <i class="bi bi-search search-icon"></i>
            <input type="text" name="search" class="form-control" placeholder="Cari nama / username / role..." value="<?= e($search) ?>">
        </form>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table mb-0">
                <thead><tr><th>Nama Lengkap</th><th>Username</th><th>Role</th><th>Status</th><th>Login Terakhir</th><th>Aksi</th></tr></thead>
                <tbody>
                <?php if (empty($result['data'])): ?>
                    <tr><td colspan="6" class="text-center py-4 text-muted">Data user tidak ditemukan.</td></tr>
                <?php else: ?>
                    <?php foreach ($result['data'] as $u): ?>
                    <tr>
                        <td class="fw-semibold"><?= e($u['nama']) ?></td>
                        <td><?= e($u['username']) ?></td>
                        <td><span class="badge bg-secondary"><?= ucfirst($u['role']) ?></span></td>
                        <td>
                            <?php if ($u['is_active']): ?>
                                <span class="badge bg-success">Aktif</span>
                            <?php else: ?>
                                <span class="badge bg-danger">Nonaktif</span>
                            <?php endif; ?>
                        </td>
                        <td><?= $u['last_login'] ? date('d/m/Y H:i', strtotime($u['last_login'])) : '-' ?></td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <a href="edit.php?id=<?= $u['id_user'] ?>" class="btn btn-outline-warning" title="Edit"><i class="bi bi-pencil"></i></a>
                                <?php if ($u['id_user'] != $_SESSION['user_id']): ?>
                                    <?php if ($u['is_active']): ?>
                                        <a href="aksi.php?action=toggle&id=<?= $u['id_user'] ?>" class="btn btn-outline-danger" title="Nonaktifkan" data-confirm="Nonaktifkan user ini?"><i class="bi bi-lock-fill"></i></a>
                                    <?php else: ?>
                                        <a href="aksi.php?action=toggle&id=<?= $u['id_user'] ?>" class="btn btn-outline-success" title="Aktifkan" data-confirm="Aktifkan user ini?"><i class="bi bi-unlock-fill"></i></a>
                                    <?php endif; ?>
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
    <div class="card-footer bg-white text-center">
        <?php render_pagination($result, 'index.php'); ?>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
