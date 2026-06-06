<?php
require_once __DIR__ . '/../../includes/header.php';
check_role(['admin', 'pendaftaran']);
$no_rm = generate_no_rm($pdo);
?>

<div class="page-header">
    <h1><i class="bi bi-person-plus me-2"></i>Tambah Pasien Baru</h1>
</div>

<div class="card" style="max-width:800px;">
    <div class="card-body p-4">
        <form method="POST" action="aksi.php">
            <?php csrf_field(); ?>
            <input type="hidden" name="action" value="tambah">
            
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">No. Rekam Medis</label>
                    <input type="text" class="form-control" value="<?= e($no_rm) ?>" readonly style="background:#f1f5f9;">
                    <input type="hidden" name="no_rekam_medis" value="<?= e($no_rm) ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                    <input type="text" name="nama_lengkap" class="form-control" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Tanggal Lahir <span class="text-danger">*</span></label>
                    <input type="date" name="tanggal_lahir" class="form-control" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Jenis Kelamin <span class="text-danger">*</span></label>
                    <select name="jenis_kelamin" class="form-select" required>
                        <option value="">-- Pilih --</option>
                        <option value="L">Laki-laki</option>
                        <option value="P">Perempuan</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Golongan Darah</label>
                    <select name="golongan_darah" class="form-select">
                        <option value="">-- Opsional --</option>
                        <option value="A">A</option><option value="B">B</option>
                        <option value="AB">AB</option><option value="O">O</option>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label">Alamat</label>
                    <textarea name="alamat" class="form-control" rows="2"></textarea>
                </div>
                <div class="col-md-6">
                    <label class="form-label">No. Telepon</label>
                    <input type="number" name="no_telepon" class="form-control">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control">
                </div>
                <div class="col-12">
                    <label class="form-label">Alergi</label>
                    <textarea name="alergi" class="form-control" rows="2" placeholder="Catatan alergi pasien..."></textarea>
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
