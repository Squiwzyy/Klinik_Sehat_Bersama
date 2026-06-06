<?php
require_once __DIR__ . '/../includes/header.php';
$role = $_SESSION['user_role'];
$today = date('Y-m-d');

// Common queries
$totalPasienHariIni = $pdo->prepare("SELECT COUNT(*) FROM antrian WHERE tanggal_kunjungan = :t");
$totalPasienHariIni->execute([':t' => $today]);
$totalPasienHariIni = $totalPasienHariIni->fetchColumn();

$antrianAktif = $pdo->prepare("SELECT COUNT(*) FROM antrian WHERE tanggal_kunjungan = :t AND status IN ('menunggu','dipanggil')");
$antrianAktif->execute([':t' => $today]);
$antrianAktif = $antrianAktif->fetchColumn();

$totalPasien = $pdo->query("SELECT COUNT(*) FROM pasien")->fetchColumn();

// Role-specific data
if ($role === 'dokter') {
    $stmt = $pdo->prepare("SELECT d.id_dokter FROM dokter d WHERE d.id_user = :uid");
    $stmt->execute([':uid' => $_SESSION['user_id']]);
    $dokter = $stmt->fetch();
    $id_dokter = $dokter['id_dokter'] ?? 0;
    
    $pasienMenunggu = $pdo->prepare("SELECT COUNT(*) FROM antrian WHERE id_dokter = :d AND tanggal_kunjungan = :t AND status IN ('menunggu','dipanggil')");
    $pasienMenunggu->execute([':d' => $id_dokter, ':t' => $today]);
    $pasienMenunggu = $pasienMenunggu->fetchColumn();
    
    $antriDokter = $pdo->prepare("SELECT a.*, p.nama_lengkap, p.no_rekam_medis FROM antrian a JOIN pasien p ON a.id_pasien = p.id_pasien WHERE a.id_dokter = :d AND a.tanggal_kunjungan = :t AND a.status IN ('menunggu','dipanggil') ORDER BY a.no_antrian");
    $antriDokter->execute([':d' => $id_dokter, ':t' => $today]);
    $antriDokter = $antriDokter->fetchAll();
}

if ($role === 'apoteker') {
    $resepPending = $pdo->prepare("SELECT COUNT(*) FROM resep WHERE status = 'pending'");
    $resepPending->execute();
    $resepPending = $resepPending->fetchColumn();
    
    $stokKritis = $pdo->query("SELECT COUNT(*) FROM obat WHERE stok <= stok_minimum")->fetchColumn();
}

if (in_array($role, ['kasir', 'manajer', 'admin'])) {
    $pendapatanHariIni = $pdo->prepare("SELECT COALESCE(SUM(total_tagihan), 0) FROM transaksi_pembayaran WHERE DATE(created_at) = :t AND status = 'lunas'");
    $pendapatanHariIni->execute([':t' => $today]);
    $pendapatanHariIni = $pendapatanHariIni->fetchColumn();
    
    $transaksiHariIni = $pdo->prepare("SELECT COUNT(*) FROM transaksi_pembayaran WHERE DATE(created_at) = :t");
    $transaksiHariIni->execute([':t' => $today]);
    $transaksiHariIni = $transaksiHariIni->fetchColumn();
}

if (in_array($role, ['kasir'])) {
    $siapBayar = $pdo->prepare("SELECT COUNT(*) FROM antrian a WHERE a.tanggal_kunjungan = :t AND a.status = 'selesai' AND a.id_antrian NOT IN (SELECT id_antrian FROM transaksi_pembayaran WHERE status = 'lunas')");
    $siapBayar->execute([':t' => $today]);
    $siapBayar = $siapBayar->fetchColumn();
}

if (in_array($role, ['manajer', 'admin'])) {
    $pengadaanPending = $pdo->query("SELECT COUNT(*) FROM pengadaan_obat WHERE status = 'draft'")->fetchColumn();
    $totalObat = $pdo->query("SELECT COUNT(*) FROM obat")->fetchColumn();
    $stokKritisM = $pdo->query("SELECT COUNT(*) FROM obat WHERE stok <= stok_minimum")->fetchColumn();
    
    // Chart data - kunjungan 7 hari terakhir
    $chartData = $pdo->query("SELECT DATE(tanggal_kunjungan) as tgl, COUNT(*) as total FROM antrian WHERE tanggal_kunjungan >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) GROUP BY DATE(tanggal_kunjungan) ORDER BY tgl")->fetchAll();
    
    // Chart data - pendapatan 7 hari terakhir
    $chartPendapatan = $pdo->query("SELECT DATE(created_at) as tgl, COALESCE(SUM(total_tagihan),0) as total FROM transaksi_pembayaran WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) AND status = 'lunas' GROUP BY DATE(created_at) ORDER BY tgl")->fetchAll();
}
?>

<div class="page-header">
    <div>
        <h1><i class="bi bi-speedometer2 me-2"></i>Dashboard</h1>
        <p class="text-muted mb-0">Selamat datang, <?= e($_SESSION['user_nama']) ?> — <?= date('l, d F Y') ?></p>
    </div>
</div>

<!-- ============ PENDAFTARAN DASHBOARD ============ -->
<?php if ($role === 'pendaftaran'): ?>
<div class="row g-4 mb-4">
    <div class="col-md-4">
        <div class="stat-card teal p-4">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="stat-label">Pasien Hari Ini</div>
                    <div class="stat-value mt-1"><?= $totalPasienHariIni ?></div>
                </div>
                <div class="stat-icon" style="background: rgba(13,148,136,0.1); color: var(--primary);">
                    <i class="bi bi-people"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card blue p-4">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="stat-label">Antrian Aktif</div>
                    <div class="stat-value mt-1"><?= $antrianAktif ?></div>
                </div>
                <div class="stat-icon" style="background: rgba(14,165,233,0.1); color: var(--accent);">
                    <i class="bi bi-card-list"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card emerald p-4">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="stat-label">Total Pasien</div>
                    <div class="stat-value mt-1"><?= $totalPasien ?></div>
                </div>
                <div class="stat-icon" style="background: rgba(16,185,129,0.1); color: #10b981;">
                    <i class="bi bi-person-check"></i>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="row g-4">
    <div class="col-md-6">
        <a href="<?= BASE_URL ?>/modules/pasien/tambah.php" class="card text-decoration-none p-4">
            <div class="d-flex align-items-center gap-3">
                <div class="stat-icon" style="background: rgba(13,148,136,0.1); color: var(--primary);"><i class="bi bi-person-plus"></i></div>
                <div><h6 class="mb-0">Daftarkan Pasien Baru</h6><small class="text-muted">Tambah data pasien ke sistem</small></div>
            </div>
        </a>
    </div>
    <div class="col-md-6">
        <a href="<?= BASE_URL ?>/modules/antrian/tambah.php" class="card text-decoration-none p-4">
            <div class="d-flex align-items-center gap-3">
                <div class="stat-icon" style="background: rgba(14,165,233,0.1); color: var(--accent);"><i class="bi bi-plus-circle"></i></div>
                <div><h6 class="mb-0">Buat Antrian</h6><small class="text-muted">Daftarkan kunjungan pasien</small></div>
            </div>
        </a>
    </div>
</div>

<!-- ============ DOKTER DASHBOARD ============ -->
<?php elseif ($role === 'dokter'): ?>
<div class="row g-4 mb-4">
    <div class="col-md-6">
        <div class="stat-card teal p-4">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="stat-label">Pasien Menunggu</div>
                    <div class="stat-value mt-1"><?= $pasienMenunggu ?></div>
                </div>
                <div class="stat-icon" style="background: rgba(13,148,136,0.1); color: var(--primary);"><i class="bi bi-hourglass-split"></i></div>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="stat-card blue p-4">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="stat-label">Total Kunjungan Hari Ini</div>
                    <div class="stat-value mt-1"><?= $totalPasienHariIni ?></div>
                </div>
                <div class="stat-icon" style="background: rgba(14,165,233,0.1); color: var(--accent);"><i class="bi bi-calendar-check"></i></div>
            </div>
        </div>
    </div>
</div>
<div class="card">
    <div class="card-header bg-white py-3"><h6 class="mb-0 fw-bold"><i class="bi bi-list-check me-2"></i>Pasien Menunggu Diperiksa</h6></div>
    <div class="card-body p-0">
        <?php if (empty($antriDokter)): ?>
            <div class="empty-state"><i class="bi bi-emoji-smile d-block"></i><p>Tidak ada pasien menunggu saat ini.</p></div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table mb-0">
                <thead><tr><th>No.</th><th>Nama Pasien</th><th>No. RM</th><th>Jam Datang</th><th>Status</th><th>Aksi</th></tr></thead>
                <tbody>
                <?php foreach ($antriDokter as $a): ?>
                <tr>
                    <td><span class="badge bg-primary"><?= $a['no_antrian'] ?></span></td>
                    <td class="fw-semibold"><?= e($a['nama_lengkap']) ?></td>
                    <td><code><?= e($a['no_rekam_medis']) ?></code></td>
                    <td><?= $a['jam_kedatangan'] ?></td>
                    <td><span class="badge <?= status_badge($a['status']) ?>"><?= ucfirst($a['status']) ?></span></td>
                    <td>
                        <?php if ($a['status'] === 'menunggu'): ?>
                            <a href="<?= BASE_URL ?>/modules/antrian/aksi.php?action=panggil&id=<?= $a['id_antrian'] ?>&redirect=dashboard" class="btn btn-sm btn-accent"><i class="bi bi-megaphone me-1"></i>Panggil</a>
                        <?php elseif ($a['status'] === 'dipanggil'): ?>
                            <a href="<?= BASE_URL ?>/modules/rekam_medis/periksa.php?id_antrian=<?= $a['id_antrian'] ?>" class="btn btn-sm btn-primary"><i class="bi bi-journal-medical me-1"></i>Periksa</a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- ============ APOTEKER DASHBOARD ============ -->
<?php elseif ($role === 'apoteker'): ?>
<div class="row g-4 mb-4">
    <div class="col-md-4">
        <div class="stat-card amber p-4">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="stat-label">Resep Menunggu</div>
                    <div class="stat-value mt-1"><?= $resepPending ?></div>
                </div>
                <div class="stat-icon" style="background: rgba(245,158,11,0.1); color: #f59e0b;"><i class="bi bi-file-earmark-medical"></i></div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card rose p-4">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="stat-label">Stok Kritis</div>
                    <div class="stat-value mt-1"><?= $stokKritis ?></div>
                </div>
                <div class="stat-icon" style="background: rgba(244,63,94,0.1); color: #f43f5e;"><i class="bi bi-exclamation-triangle"></i></div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card teal p-4">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="stat-label">Antrian Hari Ini</div>
                    <div class="stat-value mt-1"><?= $totalPasienHariIni ?></div>
                </div>
                <div class="stat-icon" style="background: rgba(13,148,136,0.1); color: var(--primary);"><i class="bi bi-people"></i></div>
            </div>
        </div>
    </div>
</div>
<div class="row g-4">
    <div class="col-md-6">
        <a href="<?= BASE_URL ?>/modules/apotek/" class="card text-decoration-none p-4">
            <div class="d-flex align-items-center gap-3">
                <div class="stat-icon" style="background: rgba(245,158,11,0.1); color: #f59e0b;"><i class="bi bi-capsule"></i></div>
                <div><h6 class="mb-0">Proses Resep</h6><small class="text-muted">Lihat resep masuk</small></div>
            </div>
        </a>
    </div>
    <div class="col-md-6">
        <a href="<?= BASE_URL ?>/modules/stok/" class="card text-decoration-none p-4">
            <div class="d-flex align-items-center gap-3">
                <div class="stat-icon" style="background: rgba(244,63,94,0.1); color: #f43f5e;"><i class="bi bi-box-seam"></i></div>
                <div><h6 class="mb-0">Kelola Stok</h6><small class="text-muted">Cek ketersediaan obat</small></div>
            </div>
        </a>
    </div>
</div>

<!-- ============ KASIR DASHBOARD ============ -->
<?php elseif ($role === 'kasir'): ?>
<div class="row g-4 mb-4">
    <div class="col-md-4">
        <div class="stat-card amber p-4">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="stat-label">Siap Bayar</div>
                    <div class="stat-value mt-1"><?= $siapBayar ?></div>
                </div>
                <div class="stat-icon" style="background: rgba(245,158,11,0.1); color: #f59e0b;"><i class="bi bi-receipt"></i></div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card emerald p-4">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="stat-label">Pendapatan Hari Ini</div>
                    <div class="stat-value mt-1" style="font-size:1.3rem;"><?= format_rupiah($pendapatanHariIni) ?></div>
                </div>
                <div class="stat-icon" style="background: rgba(16,185,129,0.1); color: #10b981;"><i class="bi bi-cash-stack"></i></div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card blue p-4">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="stat-label">Transaksi Hari Ini</div>
                    <div class="stat-value mt-1"><?= $transaksiHariIni ?></div>
                </div>
                <div class="stat-icon" style="background: rgba(14,165,233,0.1); color: var(--accent);"><i class="bi bi-credit-card"></i></div>
            </div>
        </div>
    </div>
</div>
<a href="<?= BASE_URL ?>/modules/pembayaran/" class="card text-decoration-none p-4">
    <div class="d-flex align-items-center gap-3">
        <div class="stat-icon" style="background: rgba(245,158,11,0.1); color: #f59e0b;"><i class="bi bi-cash-coin"></i></div>
        <div><h6 class="mb-0">Proses Pembayaran</h6><small class="text-muted">Lihat pasien siap bayar</small></div>
    </div>
</a>

<!-- ============ MANAJER / ADMIN DASHBOARD ============ -->
<?php else: ?>
<div class="row g-4 mb-4">
    <div class="col-md-3">
        <div class="stat-card teal p-4">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="stat-label">Kunjungan Hari Ini</div>
                    <div class="stat-value mt-1"><?= $totalPasienHariIni ?></div>
                </div>
                <div class="stat-icon" style="background: rgba(13,148,136,0.1); color: var(--primary);"><i class="bi bi-people"></i></div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card emerald p-4">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="stat-label">Pendapatan Hari Ini</div>
                    <div class="stat-value mt-1" style="font-size:1.2rem;"><?= format_rupiah($pendapatanHariIni) ?></div>
                </div>
                <div class="stat-icon" style="background: rgba(16,185,129,0.1); color: #10b981;"><i class="bi bi-wallet2"></i></div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card blue p-4">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="stat-label">Total Pasien</div>
                    <div class="stat-value mt-1"><?= $totalPasien ?></div>
                </div>
                <div class="stat-icon" style="background: rgba(14,165,233,0.1); color: var(--accent);"><i class="bi bi-person-check"></i></div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card rose p-4">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="stat-label">Stok Kritis</div>
                    <div class="stat-value mt-1"><?= $stokKritisM ?? 0 ?></div>
                </div>
                <div class="stat-icon" style="background: rgba(244,63,94,0.1); color: #f43f5e;"><i class="bi bi-exclamation-triangle"></i></div>
            </div>
        </div>
    </div>
</div>

<?php if (($pengadaanPending ?? 0) > 0): ?>
<div class="alert alert-warning d-flex align-items-center gap-2 mb-4">
    <i class="bi bi-truck fs-5"></i>
    <div><strong><?= $pengadaanPending ?></strong> pengajuan pengadaan obat menunggu persetujuan. <a href="<?= BASE_URL ?>/modules/pengadaan/" class="alert-link">Lihat &raquo;</a></div>
</div>
<?php endif; ?>

<div class="row g-4">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header bg-white py-3"><h6 class="mb-0 fw-bold"><i class="bi bi-bar-chart me-2"></i>Kunjungan 7 Hari Terakhir</h6></div>
            <div class="card-body"><canvas id="chartKunjungan" height="200"></canvas></div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header bg-white py-3"><h6 class="mb-0 fw-bold"><i class="bi bi-graph-up-arrow me-2"></i>Pendapatan 7 Hari Terakhir</h6></div>
            <div class="card-body"><canvas id="chartPendapatan" height="200"></canvas></div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const labels = <?= json_encode(array_column($chartData ?? [], 'tgl')) ?>;
    const dataKunjungan = <?= json_encode(array_map('intval', array_column($chartData ?? [], 'total'))) ?>;
    new Chart(document.getElementById('chartKunjungan'), {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Kunjungan',
                data: dataKunjungan,
                backgroundColor: 'rgba(13,148,136,0.7)',
                borderRadius: 8,
                borderSkipped: false,
            }]
        },
        options: { responsive: true, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } } }
    });
    
    const labels2 = <?= json_encode(array_column($chartPendapatan ?? [], 'tgl')) ?>;
    const dataPendapatan = <?= json_encode(array_map('floatval', array_column($chartPendapatan ?? [], 'total'))) ?>;
    new Chart(document.getElementById('chartPendapatan'), {
        type: 'line',
        data: {
            labels: labels2,
            datasets: [{
                label: 'Pendapatan (Rp)',
                data: dataPendapatan,
                borderColor: '#10b981',
                backgroundColor: 'rgba(16,185,129,0.1)',
                fill: true,
                tension: 0.4,
                pointRadius: 5,
                pointBackgroundColor: '#10b981',
            }]
        },
        options: { responsive: true, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
    });
});
</script>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
