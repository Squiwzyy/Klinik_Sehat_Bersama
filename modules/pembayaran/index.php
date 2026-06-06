<?php
require_once __DIR__ . '/../../includes/header.php';
check_role(['admin', 'kasir', 'manajer']);

$today = date('Y-m-d');
$tab = $_GET['tab'] ?? 'menunggu';

if ($tab === 'menunggu') {
    // Cari antrian yang sudah diperiksa (status selesai) tapi belum lunas
    // Harus dipastikan resepnya (jika ada) sudah 'diambil' atau 'siap' jika kita mau allow bayar sebelum ambil obat,
    // Di PRD: "List pasien siap bayar (resep siap / tidak ada resep)"
    // Mari kita cek antrian yang rekam medisnya ada, dan jika ada resep statusnya 'siap' atau 'diambil' ATAU tidak ada resep.
    $query = "SELECT a.*, p.nama_lengkap, p.no_rekam_medis, d.nama_dokter, rm.id_rekam_medis,
        (SELECT r.status FROM resep r WHERE r.id_rekam_medis = rm.id_rekam_medis LIMIT 1) as status_resep
        FROM antrian a 
        JOIN pasien p ON a.id_pasien = p.id_pasien 
        JOIN dokter d ON a.id_dokter = d.id_dokter 
        JOIN rekam_medis rm ON a.id_antrian = rm.id_antrian
        WHERE a.tanggal_kunjungan = :t 
        AND a.id_antrian NOT IN (SELECT id_antrian FROM transaksi_pembayaran WHERE status = 'lunas')
        HAVING (status_resep IS NULL OR status_resep IN ('siap', 'diambil'))
        ORDER BY a.jam_kedatangan ASC";
    $stmt = $pdo->prepare($query);
    $stmt->execute([':t' => $today]);
    $list = $stmt->fetchAll();
} else {
    // Riwayat transaksi
    $page = (int)($_GET['page'] ?? 1);
    $query = "SELECT t.*, p.nama_lengkap, p.no_rekam_medis FROM transaksi_pembayaran t JOIN pasien p ON t.id_pasien = p.id_pasien WHERE t.status = 'lunas' ORDER BY t.created_at DESC";
    $result = paginate($pdo, $query, [], $page, 20);
    $list = $result['data'];
}
?>

<div class="page-header">
    <h1><i class="bi bi-cash-stack me-2"></i>Pembayaran & Kasir</h1>
</div>

<div class="card mb-4">
    <div class="card-body p-2 d-flex gap-2 border-bottom">
        <a href="?tab=menunggu" class="btn <?= $tab === 'menunggu' ? 'btn-primary' : 'btn-outline-primary' ?>">Menunggu Pembayaran</a>
        <a href="?tab=selesai" class="btn <?= $tab === 'selesai' ? 'btn-primary' : 'btn-outline-primary' ?>">Riwayat Transaksi</a>
    </div>
    
    <div class="card-body p-0">
        <?php if ($tab === 'menunggu'): ?>
        <div class="table-responsive">
            <table class="table mb-0">
                <thead><tr><th>Jam Datang</th><th>Pasien</th><th>Dokter</th><th>Status Resep</th><th>Aksi</th></tr></thead>
                <tbody>
                <?php if (empty($list)): ?>
                    <tr><td colspan="5" class="text-center py-4 text-muted">Tidak ada pasien menunggu pembayaran hari ini.</td></tr>
                <?php else: ?>
                    <?php foreach ($list as $a): ?>
                    <tr>
                        <td><?= $a['jam_kedatangan'] ?></td>
                        <td><strong><?= e($a['nama_lengkap']) ?></strong><br><code><?= e($a['no_rekam_medis']) ?></code></td>
                        <td><?= e($a['nama_dokter']) ?></td>
                        <td>
                            <?php if ($a['status_resep']): ?>
                                <span class="badge bg-success">Resep <?= ucfirst($a['status_resep']) ?></span>
                            <?php else: ?>
                                <span class="badge bg-secondary">Tanpa Resep</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="bayar.php?id_antrian=<?= $a['id_antrian'] ?>" class="btn btn-sm btn-primary"><i class="bi bi-wallet2 me-1"></i>Proses Bayar</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <?php else: ?>
        <div class="table-responsive">
            <table class="table mb-0">
                <thead><tr><th>No. Transaksi</th><th>Tanggal</th><th>Pasien</th><th>Total Tagihan</th><th>Metode</th><th>Aksi</th></tr></thead>
                <tbody>
                <?php if (empty($list)): ?>
                    <tr><td colspan="6" class="text-center py-4 text-muted">Belum ada riwayat transaksi.</td></tr>
                <?php else: ?>
                    <?php foreach ($list as $t): ?>
                    <tr>
                        <td><strong><?= e($t['no_transaksi']) ?></strong></td>
                        <td><?= date('d/m/Y H:i', strtotime($t['created_at'])) ?></td>
                        <td><?= e($t['nama_lengkap']) ?><br><code><?= e($t['no_rekam_medis']) ?></code></td>
                        <td class="fw-bold text-success"><?= format_rupiah($t['total_tagihan']) ?></td>
                        <td><span class="badge bg-light text-dark border"><?= ucfirst($t['metode_bayar']) ?></span></td>
                        <td>
                            <a href="cetak.php?id=<?= $t['id_transaksi'] ?>" class="btn btn-sm btn-outline-secondary" target="_blank" title="Cetak Bukti"><i class="bi bi-printer"></i></a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
        <div class="card-footer bg-white">
            <div class="d-flex justify-content-center">
                <?php if (isset($result)) render_pagination($result, 'index.php?tab=selesai'); ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
