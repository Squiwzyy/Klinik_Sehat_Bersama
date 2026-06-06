<?php
require_once __DIR__ . '/../../includes/header.php';
check_role('admin');
?>

<div class="page-header">
    <h1><i class="bi bi-person-plus me-2"></i>Tambah User</h1>
</div>

<div class="card" style="max-width:800px;">
    <div class="card-body p-4">
        <form method="POST" action="aksi.php" id="formUser">
            <?php csrf_field(); ?>
            <input type="hidden" name="action" value="tambah">
            
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                    <input type="text" name="nama" class="form-control" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Username <span class="text-danger">*</span></label>
                    <input type="text" name="username" class="form-control" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Password <span class="text-danger">*</span></label>
                    <input type="password" name="password" class="form-control" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Role <span class="text-danger">*</span></label>
                    <select name="role" id="roleSelect" class="form-select" required>
                        <option value="">-- Pilih Role --</option>
                        <option value="pendaftaran">Pendaftaran</option>
                        <option value="dokter">Dokter</option>
                        <option value="apoteker">Apoteker</option>
                        <option value="kasir">Kasir</option>
                        <option value="manajer">Manajer / Kepala Klinik</option>
                        <option value="admin">Admin Sistem</option>
                    </select>
                </div>
            </div>
            
            <!-- Detail Dokter (Muncul Jika Role Dokter) -->
            <div class="mt-4" id="detailDokter" style="display:none; border-top:1px dashed #ccc; padding-top:20px;">
                <h6 class="fw-bold mb-3">Detail Dokter</h6>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Spesialisasi <span class="text-danger">*</span></label>
                        <input type="text" name="spesialisasi" class="form-control" placeholder="Contoh: Umum, Gigi">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">No. SIP (Surat Izin Praktek) <span class="text-danger">*</span></label>
                        <input type="text" name="no_sip" class="form-control">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Jadwal Praktek</label>
                        <textarea name="jadwal_praktek" class="form-control" rows="2" placeholder="Contoh: Senin - Jumat, 08:00 - 15:00"></textarea>
                    </div>
                </div>
            </div>
            
            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i>Simpan</button>
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
