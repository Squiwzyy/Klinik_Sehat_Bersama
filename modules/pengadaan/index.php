<?php
require_once __DIR__ . '/../../includes/header.php';
check_role(['admin', 'apoteker', 'manajer']);

$status_filter = $_GET['status'] ?? 'draft';

$query = "SELECT p.*, o.nama_obat, o.satuan, u1.nama as nama_pengaju, u2.nama as nama_penyetuju 
    FROM pengadaan_obat p 
    JOIN obat o ON p.id_obat = o.id_obat 
    JOIN users u1 ON p.id_pengaju = u1.id_user 
    LEFT JOIN users u2 ON p.id_penyetuju = u2.id_user 
    WHERE p.status = :s 
    ORDER BY p.tgl_pengajuan DESC";
$stmt = $pdo->prepare($query);
$stmt->execute([':s' => $status_filter]);
$list = $stmt->fetchAll();
?>

<div class="page-header">
    <h1><i class="bi bi-truck me-2"></i>Pengadaan Obat</h1>
    <?php if (in_array($_SESSION['user_role'], ['admin', 'apoteker'])): ?>
    <a href="tambah.php" class="btn btn-primary"><i class="bi bi-plus-circle me-1"></i>Buat Pengajuan</a>
    <?php endif; ?>
</div>

<div class="card mb-4">
    <div class="card-body p-2 d-flex gap-2 border-bottom">
        <a href="?status=draft" class="btn <?= $status_filter === 'draft' ? 'btn-primary' : 'btn-outline-primary' ?>">Menunggu Persetujuan</a>
        <a href="?status=disetujui" class="btn <?= $status_filter === 'disetujui' ? 'btn-primary' : 'btn-outline-primary' ?>">Disetujui (Menunggu Kedatangan)</a>
        <a href="?status=diterima" class="btn <?= $status_filter === 'diterima' ? 'btn-primary' : 'btn-outline-primary' ?>">Selesai (Diterima)</a>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table mb-0">
                <thead><tr><th>Tanggal</th><th>Obat</th><th>Jumlah Pesan</th><th>Harga Total</th><th>Supplier</th><th>Status</th><th>Aksi</th></tr></thead>
                <tbody>
                <?php if (empty($list)): ?>
                    <tr><td colspan="7" class="text-center py-4 text-muted">Tidak ada data pengadaan dengan status ini.</td></tr>
                <?php else: ?>
                    <?php foreach ($list as $p): ?>
                    <tr>
                        <td><?= date('d/m/Y', strtotime($p['tgl_pengajuan'])) ?></td>
                        <td><strong><?= e($p['nama_obat']) ?></strong></td>
                        <td><?= $p['jumlah_pesan'] ?> <?= e($p['satuan']) ?></td>
                        <td><?= format_rupiah($p['harga_beli'] * $p['jumlah_pesan']) ?></td>
                        <td><?= e($p['supplier']) ?></td>
                        <td><span class="badge <?= status_badge($p['status']) ?>"><?= ucfirst($p['status']) ?></span></td>
                        <td>
                            <?php if ($p['status'] === 'draft' && in_array($_SESSION['user_role'], ['admin', 'manajer'])): ?>
                                <a href="aksi.php?action=setujui&id=<?= $p['id_pengadaan'] ?>" class="btn btn-sm btn-primary" data-confirm="Setujui pengadaan ini?"><i class="bi bi-check-lg me-1"></i>Setujui</a>
                            <?php elseif ($p['status'] === 'disetujui' && in_array($_SESSION['user_role'], ['admin', 'apoteker'])): ?>
                                <a href="terima.php?id=<?= $p['id_pengadaan'] ?>" class="btn btn-sm btn-success"><i class="bi bi-box-arrow-in-down me-1"></i>Terima Barang</a>
                            <?php endif; ?>
                            
                            <?php if ($p['status'] === 'draft' && in_array($_SESSION['user_role'], ['admin', 'apoteker'])): ?>
                                <a href="aksi.php?action=hapus&id=<?= $p['id_pengadaan'] ?>" class="btn btn-sm btn-outline-danger" data-confirm="Hapus pengajuan ini?"><i class="bi bi-trash"></i></a>
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
