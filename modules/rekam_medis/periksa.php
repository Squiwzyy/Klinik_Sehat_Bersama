<?php
require_once __DIR__ . '/../../includes/header.php';
check_role(['admin', 'dokter']);

$id_antrian = (int)($_GET['id_antrian'] ?? 0);
$stmt = $pdo->prepare("SELECT a.*, p.*, d.nama_dokter, d.id_dokter FROM antrian a JOIN pasien p ON a.id_pasien = p.id_pasien JOIN dokter d ON a.id_dokter = d.id_dokter WHERE a.id_antrian = :id");
$stmt->execute([':id' => $id_antrian]);
$data = $stmt->fetch();
if (!$data) { set_flash('error', 'Data tidak ditemukan.'); redirect('index.php'); }

// Riwayat sebelumnya
$riwayat = $pdo->prepare("SELECT rm.*, d.nama_dokter FROM rekam_medis rm JOIN dokter d ON rm.id_dokter = d.id_dokter WHERE rm.id_pasien = :pid ORDER BY rm.tanggal_periksa DESC LIMIT 5");
$riwayat->execute([':pid' => $data['id_pasien']]);
$riwayat = $riwayat->fetchAll();

// Obat list untuk resep
$obatList = $pdo->query("SELECT * FROM obat WHERE stok > 0 ORDER BY nama_obat")->fetchAll();
?>

<div class="page-header">
    <h1><i class="bi bi-journal-medical me-2"></i>Pemeriksaan Pasien</h1>
</div>

<div class="row g-4">
    <!-- Info Pasien -->
    <div class="col-lg-4">
        <div class="card mb-4">
            <div class="card-header bg-white py-3"><h6 class="mb-0 fw-bold"><i class="bi bi-person me-2"></i>Info Pasien</h6></div>
            <div class="card-body">
                <p class="mb-1"><strong><?= e($data['nama_lengkap']) ?></strong></p>
                <p class="mb-1 text-muted"><code><?= e($data['no_rekam_medis']) ?></code></p>
                <p class="mb-1"><?= $data['jenis_kelamin']==='L'?'Laki-laki':'Perempuan' ?> — <?= date('d/m/Y', strtotime($data['tanggal_lahir'])) ?></p>
                <?php if ($data['alergi']): ?>
                    <div class="alert alert-danger mt-2 mb-0 py-2"><i class="bi bi-exclamation-triangle me-1"></i><strong>Alergi:</strong> <?= e($data['alergi']) ?></div>
                <?php endif; ?>
            </div>
        </div>
        
        <?php if (!empty($riwayat)): ?>
        <div class="card">
            <div class="card-header bg-white py-3"><h6 class="mb-0 fw-bold"><i class="bi bi-clock-history me-2"></i>Riwayat Terakhir</h6></div>
            <div class="card-body p-0">
                <?php foreach ($riwayat as $r): ?>
                <div class="p-3 border-bottom">
                    <small class="text-muted"><?= date('d/m/Y', strtotime($r['tanggal_periksa'])) ?> — <?= e($r['nama_dokter']) ?></small>
                    <p class="mb-0 mt-1"><strong><?= e($r['diagnosis']) ?></strong> <?= $r['kode_icd'] ? '<code>('.e($r['kode_icd']).')</code>' : '' ?></p>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Form Pemeriksaan -->
    <div class="col-lg-8">
        <form method="POST" action="aksi.php" id="formPeriksa">
            <?php csrf_field(); ?>
            <input type="hidden" name="action" value="simpan_rekam_medis">
            <input type="hidden" name="id_antrian" value="<?= $id_antrian ?>">
            <input type="hidden" name="id_pasien" value="<?= $data['id_pasien'] ?>">
            <input type="hidden" name="id_dokter" value="<?= $data['id_dokter'] ?>">
            
            <div class="card mb-4">
                <div class="card-header bg-white py-3"><h6 class="mb-0 fw-bold">Rekam Medis</h6></div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Anamnesis (Keluhan) <span class="text-danger">*</span></label>
                            <textarea name="anamnesis" class="form-control" rows="3" required placeholder="Keluhan pasien..."></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Pemeriksaan Fisik</label>
                            <textarea name="pemeriksaan_fisik" class="form-control" rows="3" placeholder="Hasil pemeriksaan fisik..."></textarea>
                        </div>
                        <div class="col-md-4" style="position:relative;">
                            <label class="form-label">Kode ICD-10</label>
                            <input type="text" name="kode_icd" id="kode_icd" class="form-control" placeholder="Ketik kode..." autocomplete="off">
                            <div id="icd_dropdown" class="dropdown-menu p-0" style="display:none; position:absolute; width:100%; max-height:200px; overflow-y:auto; z-index:1000;"></div>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label">Diagnosis <span class="text-danger">*</span></label>
                            <input type="text" name="diagnosis" id="diagnosis" class="form-control" required placeholder="Nama diagnosis...">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Tindakan</label>
                            <textarea name="tindakan" class="form-control" rows="2" placeholder="Tindakan yang dilakukan..."></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Catatan Dokter</label>
                            <textarea name="catatan_dokter" class="form-control" rows="2" placeholder="Catatan tambahan..."></textarea>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Resep (opsional) -->
            <div class="card mb-4">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-bold"><i class="bi bi-capsule me-2"></i>Resep Obat (Opsional)</h6>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="buatResep" name="buat_resep" value="1">
                        <label class="form-check-label" for="buatResep">Buat Resep</label>
                    </div>
                </div>
                <div class="card-body" id="resepSection" style="display:none;">
                    <div id="resepItems">
                        <div class="resep-item border rounded p-3 mb-3">
                            <div class="row g-2">
                                <div class="col-md-4">
                                    <label class="form-label">Obat</label>
                                    <select name="obat[0][id_obat]" class="form-select obat-select">
                                        <option value="">-- Pilih Obat --</option>
                                        <?php foreach ($obatList as $o): ?>
                                            <option value="<?= $o['id_obat'] ?>" data-harga="<?= $o['harga_jual'] ?>" data-stok="<?= $o['stok'] ?>"><?= e($o['nama_obat']) ?> (stok: <?= $o['stok'] ?>)</option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Jumlah</label>
                                    <input type="number" name="obat[0][jumlah]" class="form-control" min="1" value="1">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Dosis</label>
                                    <input type="text" name="obat[0][dosis]" class="form-control" placeholder="3x1 tablet">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Aturan Pakai</label>
                                    <input type="text" name="obat[0][aturan_pakai]" class="form-control" placeholder="Sesudah makan">
                                </div>
                            </div>
                        </div>
                    </div>
                    <button type="button" class="btn btn-outline-primary btn-sm" id="addObat"><i class="bi bi-plus me-1"></i>Tambah Obat</button>
                    <div class="mt-3">
                        <label class="form-label">Catatan Resep</label>
                        <textarea name="catatan_resep" class="form-control" rows="2"></textarea>
                    </div>
                </div>
            </div>
            
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary btn-lg"><i class="bi bi-save me-1"></i>Simpan Pemeriksaan</button>
                <a href="index.php" class="btn btn-outline-secondary btn-lg">Batal</a>
            </div>
        </form>
    </div>
</div>

<script>
document.getElementById('buatResep')?.addEventListener('change', function() {
    document.getElementById('resepSection').style.display = this.checked ? 'block' : 'none';
});

let obatIdx = 1;
document.getElementById('addObat')?.addEventListener('click', function() {
    const tpl = document.querySelector('.resep-item').cloneNode(true);
    tpl.querySelectorAll('[name]').forEach(el => { el.name = el.name.replace('[0]', '['+obatIdx+']'); el.value = el.tagName === 'SELECT' ? '' : (el.type === 'number' ? '1' : ''); });
    document.getElementById('resepItems').appendChild(tpl);
    obatIdx++;
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
