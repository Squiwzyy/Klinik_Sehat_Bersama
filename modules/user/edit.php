<?php
require_once __DIR__ . '/../../includes/header.php';
check_role('admin');

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM users WHERE id_user = :id");
$stmt->execute([':id' => $id]);
$u = $stmt->fetch();
if (!$u) { set_flash('error', 'User tidak ditemukan.'); redirect('index.php'); }

$dokter = null;
if ($u['role'] === 'dokter') {
    $stmt_dok = $pdo->prepare("SELECT * FROM dokter WHERE id_user = :id");
    $stmt_dok->execute([':id' => $id]);
    $dokter = $stmt_dok->fetch();
}
?>

<div class="page-header">
    <h1><i class="bi bi-pencil me-2"></i>Edit User</h1>
</div>

<div class="card" style="max-width:800px;">
    <div class="card-body p-4">
        <form method="POST" action="aksi.php" id="formUser">
            <?php csrf_field(); ?>
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id_user" value="<?= $id ?>">
            <input type="hidden" name="old_role" value="<?= $u['role'] ?>">
            
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                    <input type="text" name="nama" class="form-control" value="<?= e($u['nama']) ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Username <span class="text-danger">*</span></label>
                    <input type="text" name="username" class="form-control" value="<?= e($u['username']) ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Password Baru (Opsional)</label>
                    <input type="password" name="password" class="form-control" placeholder="Kosongkan jika tidak ingin mengubah">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Role <span class="text-danger">*</span></label>
                    <select name="role" id="roleSelect" class="form-select" required>
                        <?php foreach (['pendaftaran','dokter','apoteker','kasir','manajer','admin'] as $r): ?>
                            <option value="<?= $r ?>" <?= $u['role']===$r?'selected':'' ?>><?= ucfirst($r) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <!-- Detail Dokter (Muncul Jika Role Dokter) -->
            <div class="mt-4" id="detailDokter" style="display:<?= $u['role']==='dokter'?'block':'none' ?>; border-top:1px dashed #ccc; padding-top:20px;">
                <h6 class="fw-bold mb-3">Detail Dokter</h6>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Spesialisasi <span class="text-danger">*</span></label>
                        <input type="text" name="spesialisasi" class="form-control" value="<?= e($dokter['spesialisasi'] ?? '') ?>" <?= $u['role']==='dokter'?'required':'' ?>>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">No. SIP <span class="text-danger">*</span></label>
                        <input type="text" name="no_sip" class="form-control" value="<?= e($dokter['no_sip'] ?? '') ?>" <?= $u['role']==='dokter'?'required':'' ?>>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Jadwal Praktek</label>
                        <textarea name="jadwal_praktek" class="form-control" rows="2"><?= e($dokter['jadwal_praktek'] ?? '') ?></textarea>
                    </div>
                </div>
            </div>
            
            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i>Simpan Perubahan</button>
                <a href="index.php" class="btn btn-outline-secondary">Batal</a>
            </div>
        </form>
    </div>
</div>

<script>
document.getElementById('roleSelect')?.addEventListener('change', function() {
    const detailDokter = document.getElementById('detailDokter');
    if (this.value === 'dokter') {
        detailDokter.style.display = 'block';
        detailDokter.querySelectorAll('input').forEach(el => el.setAttribute('required', 'required'));
    } else {
        detailDokter.style.display = 'none';
        detailDokter.querySelectorAll('input').forEach(el => el.removeAttribute('required'));
    }
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
