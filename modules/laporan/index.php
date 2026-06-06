<?php
require_once __DIR__ . '/../../includes/header.php';
check_role(['admin', 'manajer']);

$bulan = $_GET['bulan'] ?? date('Y-m');
$year = date('Y', strtotime($bulan));
$month = date('m', strtotime($bulan));

// Data Kunjungan Bulanan
$kunjungan = $pdo->prepare("SELECT DATE(tanggal_kunjungan) as tgl, COUNT(*) as total FROM antrian WHERE YEAR(tanggal_kunjungan) = :y AND MONTH(tanggal_kunjungan) = :m GROUP BY DATE(tanggal_kunjungan) ORDER BY tgl");
$kunjungan->execute([':y' => $year, ':m' => $month]);
$kunjunganData = $kunjungan->fetchAll();

// Data Pendapatan Bulanan
$pendapatan = $pdo->prepare("SELECT DATE(created_at) as tgl, SUM(total_tagihan) as total FROM transaksi_pembayaran WHERE YEAR(created_at) = :y AND MONTH(created_at) = :m AND status = 'lunas' GROUP BY DATE(created_at) ORDER BY tgl");
$pendapatan->execute([':y' => $year, ':m' => $month]);
$pendapatanData = $pendapatan->fetchAll();

// Obat Terlaris
$obatTop = $pdo->prepare("SELECT o.nama_obat, SUM(dr.jumlah) as total_terjual FROM detail_resep dr JOIN resep r ON dr.id_resep = r.id_resep JOIN obat o ON dr.id_obat = o.id_obat WHERE YEAR(r.tanggal_resep) = :y AND MONTH(r.tanggal_resep) = :m AND r.status IN ('siap','diambil') GROUP BY dr.id_obat ORDER BY total_terjual DESC LIMIT 10");
$obatTop->execute([':y' => $year, ':m' => $month]);
$obatData = $obatTop->fetchAll();

// Summary stats
$totalKunjungan = array_sum(array_column($kunjunganData, 'total'));
$totalPendapatan = array_sum(array_column($pendapatanData, 'total'));
?>

<div class="page-header no-print">
    <h1><i class="bi bi-graph-up me-2"></i>Laporan Bulanan</h1>
    <button onclick="window.print()" class="btn btn-outline-secondary"><i class="bi bi-printer me-1"></i>Cetak</button>
</div>

<div class="card mb-4 no-print">
    <div class="card-body">
        <form method="GET" class="d-flex gap-2 align-items-center" style="max-width:300px;">
            <label class="form-label mb-0 fw-bold">Bulan:</label>
            <input type="month" name="bulan" class="form-control" value="<?= e($bulan) ?>" onchange="this.form.submit()">
        </form>
    </div>
</div>

<div class="print-page">
    <div class="text-center d-none d-print-block mb-4 border-bottom pb-3">
        <h2>Laporan Klinik Sehat Bersama</h2>
        <p class="mb-0">Periode: <?= date('F Y', strtotime($bulan)) ?></p>
    </div>
    
    <div class="row g-4 mb-4">
        <div class="col-md-6">
            <div class="stat-card teal p-4">
                <div class="stat-label">Total Kunjungan Pasien</div>
                <div class="stat-value mt-2"><?= $totalKunjungan ?> Pasien</div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="stat-card emerald p-4">
                <div class="stat-label">Total Pendapatan</div>
                <div class="stat-value mt-2"><?= format_rupiah($totalPendapatan) ?></div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header bg-white py-3"><h6 class="mb-0 fw-bold">Tren Kunjungan</h6></div>
                <div class="card-body"><canvas id="chartKunjungan" height="250"></canvas></div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header bg-white py-3"><h6 class="mb-0 fw-bold">Tren Pendapatan</h6></div>
                <div class="card-body"><canvas id="chartPendapatan" height="250"></canvas></div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header bg-white py-3"><h6 class="mb-0 fw-bold">Top 10 Obat Terlaris</h6></div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead><tr><th>No.</th><th>Nama Obat</th><th>Total Terjual (Satuan)</th></tr></thead>
                    <tbody>
                    <?php if (empty($obatData)): ?>
                        <tr><td colspan="3" class="text-center py-3 text-muted">Belum ada data penjualan obat.</td></tr>
                    <?php else: ?>
                        <?php $no=1; foreach ($obatData as $o): ?>
                        <tr>
                            <td><?= $no++ ?></td>
                            <td><?= e($o['nama_obat']) ?></td>
                            <td class="fw-bold text-success"><?= $o['total_terjual'] ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const kLabels = <?= json_encode(array_map(function($d){ return date('d/m', strtotime($d['tgl'])); }, $kunjunganData)) ?>;
    const kData = <?= json_encode(array_map('intval', array_column($kunjunganData, 'total'))) ?>;
    
    new Chart(document.getElementById('chartKunjungan'), {
        type: 'line',
        data: { labels: kLabels, datasets: [{ label: 'Kunjungan', data: kData, borderColor: '#0ea5e9', backgroundColor: 'rgba(14,165,233,0.1)', fill: true, tension: 0.3 }] },
        options: { responsive: true, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } } }
    });
    
    const pLabels = <?= json_encode(array_map(function($d){ return date('d/m', strtotime($d['tgl'])); }, $pendapatanData)) ?>;
    const pData = <?= json_encode(array_map('floatval', array_column($pendapatanData, 'total'))) ?>;
    
    new Chart(document.getElementById('chartPendapatan'), {
        type: 'bar',
        data: { labels: pLabels, datasets: [{ label: 'Pendapatan (Rp)', data: pData, backgroundColor: '#10b981', borderRadius: 4 }] },
        options: { responsive: true, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
    });
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
