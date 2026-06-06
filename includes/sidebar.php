<?php
$role = $_SESSION['user_role'] ?? '';
$menus = [];

// Menu berdasarkan role
if (in_array($role, ['admin', 'pendaftaran'])) {
    $menus[] = ['label' => 'Pasien', 'icon' => 'bi-people', 'url' => BASE_URL . '/modules/pasien/', 'dir' => 'pasien'];
}
if (in_array($role, ['admin', 'pendaftaran'])) {
    $menus[] = ['label' => 'Antrian', 'icon' => 'bi-card-list', 'url' => BASE_URL . '/modules/antrian/', 'dir' => 'antrian'];
}
if (in_array($role, ['admin', 'dokter'])) {
    $menus[] = ['label' => 'Rekam Medis', 'icon' => 'bi-journal-medical', 'url' => BASE_URL . '/modules/rekam_medis/', 'dir' => 'rekam_medis'];
}
if (in_array($role, ['admin', 'apoteker'])) {
    $menus[] = ['label' => 'Apotek', 'icon' => 'bi-capsule', 'url' => BASE_URL . '/modules/apotek/', 'dir' => 'apotek'];
}
if (in_array($role, ['admin', 'kasir'])) {
    $menus[] = ['label' => 'Pembayaran', 'icon' => 'bi-cash-stack', 'url' => BASE_URL . '/modules/pembayaran/', 'dir' => 'pembayaran'];
}
if (in_array($role, ['admin', 'apoteker'])) {
    $menus[] = ['label' => 'Stok Obat', 'icon' => 'bi-box-seam', 'url' => BASE_URL . '/modules/stok/', 'dir' => 'stok'];
}
if (in_array($role, ['admin','apoteker', 'manajer'])){
    $menus[] = ['label' => 'Pengadaan', 'icon' => 'bi-truck', 'url' => BASE_URL . '/modules/pengadaan/', 'dir' => 'pengadaan'];
}
if (in_array($role, ['admin', 'manajer'])) {
    $menus[] = ['label' => 'Laporan', 'icon' => 'bi-graph-up', 'url' => BASE_URL . '/modules/laporan/', 'dir' => 'laporan'];
}
if ($role === 'admin') {
    $menus[] = ['label' => 'Manajemen User', 'icon' => 'bi-person-gear', 'url' => BASE_URL . '/modules/user/', 'dir' => 'user'];
}
?>
<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <i class="bi bi-heart-pulse-fill fs-3"></i>
        <span class="sidebar-brand">KSB</span>
    </div>
    <ul class="sidebar-nav">
        <li class="sidebar-item">
            <a href="<?= BASE_URL ?>/dashboard/" class="sidebar-link <?= $current_dir === 'dashboard' ? 'active' : '' ?>">
                <i class="bi bi-speedometer2"></i><span>Dashboard</span>
            </a>
        </li>
        <?php foreach ($menus as $m): ?>
        <li class="sidebar-item">
            <a href="<?= $m['url'] ?>" class="sidebar-link <?= $current_dir === $m['dir'] ? 'active' : '' ?>">
                <i class="bi <?= $m['icon'] ?>"></i><span><?= $m['label'] ?></span>
            </a>
        </li>
        <?php endforeach; ?>
    </ul>
    <div class="sidebar-footer">
        <small class="text-muted">&copy; 2026 KSB</small>
    </div>
</aside>
