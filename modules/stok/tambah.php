<?php
require_once __DIR__ . '/../../includes/header.php';
check_role(['admin', 'apoteker']);
?>

<div class="page-header">
    <h1><i class="bi bi-plus-circle me-2"></i>Tambah Data Obat</h1>
</div>

<div class="card" style="max-width:800px;">
    <div class="card-body p-4">
        <form method="POST" action="aksi.php">
            <?php csrf_field(); ?>
            <input type="hidden" name="action" value="tambah">
            
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Nama Obat <span class="text-danger">*</span></label>
                    <input type="text" name="nama_obat" class="form-control" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Kategori</label>
                    <input type="text" name="kategori" class="form-control" placeholder="Contoh: Antibiotik, Analgesik">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Satuan <span class="text-danger">*</span></label>
                    <select name="satuan" class="form-select" required>
                        <option value="">-- Pilih --</option>
                        <option value="tablet">Tablet</option>
                        <option value="kapsul">Kapsul</option>
                        <option value="botol">Botol</option>
                        <option value="ml">ml</option>
                        <option value="ampul">Ampul</option>
                        <option value="tube">Tube</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Stok Awal</label>
                    <input type="number" name="stok" class="form-control" value="0" min="0">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Batas Stok Minimum <span class="text-danger">*</span></label>
                    <input type="number" name="stok_minimum" class="form-control" value="10" min="1" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Harga Beli (Rp)</label>
                    <input type="number" name="harga_beli" class="form-control" value="0" min="0">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Harga Jual (Rp) <span class="text-danger">*</span></label>
                    <input type="number" name="harga_jual" class="form-control" value="0" min="0" required>
                </div>
            </div>
            
            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i>Simpan</button>
                <a href="index.php" class="btn btn-outline-secondary">Batal</a>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
