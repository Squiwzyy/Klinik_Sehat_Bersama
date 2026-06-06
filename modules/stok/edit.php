<?php
require_once __DIR__ . '/../../includes/header.php';
check_role(['admin', 'apoteker']);

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM obat WHERE id_obat = :id");
$stmt->execute([':id' => $id]);
$o = $stmt->fetch();
if (!$o) { set_flash('error', 'Obat tidak ditemukan.'); redirect('index.php'); }
?>

<div class="page-header">
    <h1><i class="bi bi-pencil me-2"></i>Edit Data Obat</h1>
</div>

<div class="card" style="max-width:800px;">
    <div class="card-body p-4">
        <form method="POST" action="aksi.php">
            <?php csrf_field(); ?>
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id_obat" value="<?= $o['id_obat'] ?>">
            
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Nama Obat <span class="text-danger">*</span></label>
                    <input type="text" name="nama_obat" class="form-control" value="<?= e($o['nama_obat']) ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Kategori</label>
                    <input type="text" name="kategori" class="form-control" value="<?= e($o['kategori']) ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Satuan <span class="text-danger">*</span></label>
                    <select name="satuan" class="form-select" required>
                        <?php foreach (['tablet','kapsul','botol','ml','ampul','tube'] as $s): ?>
                            <option value="<?= $s ?>" <?= $o['satuan']===$s?'selected':'' ?>><?= ucfirst($s) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Stok Saat Ini (Hanya Baca)</label>
                    <input type="number" class="form-control" value="<?= $o['stok'] ?>" readonly style="background:#f1f5f9;">
                    <div class="form-text">Gunakan pengadaan untuk tambah stok.</div>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Batas Stok Minimum <span class="text-danger">*</span></label>
                    <input type="number" name="stok_minimum" class="form-control" value="<?= $o['stok_minimum'] ?>" min="1" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Harga Beli (Rp)</label>
                    <input type="number" name="harga_beli" class="form-control" value="<?= (int)$o['harga_beli'] ?>" min="0">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Harga Jual (Rp) <span class="text-danger">*</span></label>
                    <input type="number" name="harga_jual" class="form-control" value="<?= (int)$o['harga_jual'] ?>" min="0" required>
                </div>
            </div>
            
            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i>Simpan Perubahan</button>
                <a href="index.php" class="btn btn-outline-secondary">Batal</a>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
