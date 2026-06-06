<?php
require_once __DIR__ . '/../../includes/header.php';
check_role(['admin', 'apoteker']);

$obatList = $pdo->query("SELECT id_obat, nama_obat, satuan, stok, stok_minimum FROM obat ORDER BY nama_obat")->fetchAll();

// Jika ada obat yang diklik dari dashboard (stok menipis)
$id_obat = (int)($_GET['id_obat'] ?? 0);
?>

<div class="page-header">
    <h1><i class="bi bi-plus-circle me-2"></i>Buat Pengajuan Pengadaan</h1>
</div>

<div class="card" style="max-width:800px;">
    <div class="card-body p-4">
        <form method="POST" action="aksi.php">
            <?php csrf_field(); ?>
            <input type="hidden" name="action" value="tambah">
            
            <div class="row g-3">
                <div class="col-md-8">
                    <label class="form-label">Pilih Obat <span class="text-danger">*</span></label>
                    <select name="id_obat" id="id_obat" class="form-select" required>
                        <option value="">-- Pilih Obat --</option>
                        <?php foreach ($obatList as $o): ?>
                            <option value="<?= $o['id_obat'] ?>" data-satuan="<?= e($o['satuan']) ?>" <?= $id_obat==$o['id_obat']?'selected':'' ?>>
                                <?= e($o['nama_obat']) ?> (Stok: <?= $o['stok'] ?> | Min: <?= $o['stok_minimum'] ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Jumlah Pesan <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <input type="number" name="jumlah_pesan" class="form-control" min="1" required>
                        <span class="input-group-text" id="satuan_label">...</span>
                    </div>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Harga Beli Satuan (Estimasi/Rp)</label>
                    <input type="number" name="harga_beli" class="form-control" min="0" value="0">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Supplier</label>
                    <input type="text" name="supplier" class="form-control" placeholder="Nama supplier (opsional)">
                </div>
            </div>
            
            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="bi bi-send me-1"></i>Kirim Pengajuan</button>
                <a href="index.php" class="btn btn-outline-secondary">Batal</a>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const selectObat = document.getElementById('id_obat');
    const satuanLabel = document.getElementById('satuan_label');
    
    function updateSatuan() {
        const option = selectObat.options[selectObat.selectedIndex];
        if (option && option.value) {
            satuanLabel.textContent = option.getAttribute('data-satuan');
        } else {
            satuanLabel.textContent = '...';
        }
    }
    
    selectObat.addEventListener('change', updateSatuan);
    updateSatuan();
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
