<?php
require_once __DIR__ . '/../../includes/header.php';
check_role(['admin', 'pendaftaran']);

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM pasien WHERE id_pasien = :id");
$stmt->execute([':id' => $id]);
$p = $stmt->fetch();
if (!$p) { set_flash('error', 'Pasien tidak ditemukan.'); redirect('index.php'); }
?>

<div class="page-header">
    <h1><i class="bi bi-pencil me-2"></i>Edit Pasien</h1>
</div>

<div class="card" style="max-width:800px;">
    <div class="card-body p-4">
        <form method="POST" action="aksi.php">
            <?php csrf_field(); ?>
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id_pasien" value="<?= $p['id_pasien'] ?>">
            
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">No. Rekam Medis</label>
                    <input type="text" class="form-control" value="<?= e($p['no_rekam_medis']) ?>" readonly style="background:#f1f5f9;">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                    <input type="text" name="nama_lengkap" class="form-control" value="<?= e($p['nama_lengkap']) ?>" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Tanggal Lahir <span class="text-danger">*</span></label>
                    <input type="date" name="tanggal_lahir" class="form-control" value="<?= $p['tanggal_lahir'] ?>" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Jenis Kelamin <span class="text-danger">*</span></label>
                    <select name="jenis_kelamin" class="form-select" required>
                        <option value="L" <?= $p['jenis_kelamin']==='L'?'selected':'' ?>>Laki-laki</option>
                        <option value="P" <?= $p['jenis_kelamin']==='P'?'selected':'' ?>>Perempuan</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Golongan Darah</label>
                    <select name="golongan_darah" class="form-select">
                        <option value="">-- Opsional --</option>
                        <?php foreach (['A','B','AB','O'] as $gd): ?>
                            <option value="<?= $gd ?>" <?= $p['golongan_darah']===$gd?'selected':'' ?>><?= $gd ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label">Alamat</label>
                    <textarea name="alamat" class="form-control" rows="2"><?= e($p['alamat']) ?></textarea>
                </div>
                <div class="col-md-6">
                    <label class="form-label">No. Telepon</label>
                    <input type="number" name="no_telepon" class="form-control" value="<?= e($p['no_telepon']) ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control" value="<?= e($p['email']) ?>">
                </div>
                <div class="col-12">
                    <label class="form-label">Alergi</label>
                    <textarea name="alergi" class="form-control" rows="2"><?= e($p['alergi']) ?></textarea>
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
