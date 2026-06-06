<?php
require_once __DIR__ . '/../../includes/header.php';
check_role(['admin', 'apoteker']);

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT p.*, o.nama_obat, o.satuan FROM pengadaan_obat p JOIN obat o ON p.id_obat = o.id_obat WHERE p.id_pengadaan = :id AND p.status = 'disetujui'");
$stmt->execute([':id' => $id]);
$p = $stmt->fetch();
if (!$p) { set_flash('error', 'Pengadaan tidak ditemukan atau belum disetujui.'); redirect('index.php'); }
?>

<div class="page-header">
    <h1><i class="bi bi-box-arrow-in-down me-2"></i>Terima Barang Pengadaan</h1>
</div>

<div class="card" style="max-width:800px;">
    <div class="card-body p-4">
        <form method="POST" action="aksi.php">
            <?php csrf_field(); ?>
            <input type="hidden" name="action" value="terima">
            <input type="hidden" name="id_pengadaan" value="<?= $id ?>">
            
            <div class="alert alert-info d-flex gap-3 align-items-center mb-4">
                <i class="bi bi-truck fs-1"></i>
                <div>
                    <h5 class="mb-1"><strong><?= e($p['nama_obat']) ?></strong></h5>
                    <p class="mb-0">Pesanan: <?= $p['jumlah_pesan'] ?> <?= e($p['satuan']) ?> | Supplier: <?= e($p['supplier'] ?: '-') ?></p>
                </div>
            </div>
            
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Tanggal Diterima <span class="text-danger">*</span></label>
                    <input type="date" name="tgl_diterima" class="form-control" value="<?= date('Y-m-d') ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Tanggal Kadaluarsa <span class="text-danger">*</span></label>
                    <input type="date" name="tgl_kadaluarsa" class="form-control" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Harga Beli Aktual (Satuan/Rp) <span class="text-danger">*</span></label>
                    <input type="number" name="harga_beli_aktual" class="form-control" value="<?= (int)$p['harga_beli'] ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Jumlah Diterima <span class="text-danger">*</span></label>
                    <input type="number" name="jumlah_diterima" class="form-control" value="<?= $p['jumlah_pesan'] ?>" required>
                    <div class="form-text">Sesuaikan jika barang yang datang kurang/lebih.</div>
                </div>
            </div>
            
            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn btn-success"><i class="bi bi-check-circle me-1"></i>Konfirmasi Penerimaan</button>
                <a href="index.php" class="btn btn-outline-secondary">Batal</a>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
