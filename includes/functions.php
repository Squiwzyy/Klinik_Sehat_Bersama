<?php
/**
 * Klinik Sehat Bersama - Helper Functions
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function check_login() {
    if (!isset($_SESSION['user_id'])) {
        redirect(BASE_URL . '/auth/login.php');
    }
}

function check_role($allowed_roles) {
    check_login();
    if (!is_array($allowed_roles)) $allowed_roles = [$allowed_roles];
    if (!in_array($_SESSION['user_role'], $allowed_roles)) {
        set_flash('error', 'Anda tidak memiliki akses ke halaman ini.');
        redirect(BASE_URL . '/dashboard/');
    }
}

function csrf_token() {
    if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf_token'];
}

function csrf_field() {
    echo '<input type="hidden" name="csrf_token" value="' . csrf_token() . '">';
}

function verify_csrf() {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        set_flash('error', 'Token keamanan tidak valid.');
        redirect($_SERVER['HTTP_REFERER'] ?? BASE_URL . '/dashboard/');
    }
}

function e($s) { return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }

function set_flash($type, $msg) { $_SESSION['flash'] = ['type' => $type, 'message' => $msg]; }

function get_flash() {
    if (isset($_SESSION['flash'])) { $f = $_SESSION['flash']; unset($_SESSION['flash']); return $f; }
    return null;
}

function display_flash() {
    $f = get_flash();
    if ($f) {
        $t = $f['type'] === 'error' ? 'danger' : $f['type'];
        echo '<div class="alert alert-'.e($t).' alert-dismissible fade show" role="alert">'.e($f['message']).'<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
    }
}

function redirect($url) { header("Location: $url"); exit; }

function format_rupiah($n) { return 'Rp ' . number_format($n, 0, ',', '.'); }

function generate_no_rm($pdo) {
    $prefix = "RM-" . date('Ymd') . "-";
    $stmt = $pdo->prepare("SELECT no_rekam_medis FROM pasien WHERE no_rekam_medis LIKE :p ORDER BY id_pasien DESC LIMIT 1");
    $stmt->execute([':p' => $prefix . '%']);
    $last = $stmt->fetchColumn();
    $num = $last ? (int)substr($last, -4) + 1 : 1;
    return $prefix . str_pad($num, 4, '0', STR_PAD_LEFT);
}

function generate_no_transaksi($pdo) {
    $prefix = "TRX-" . date('Ymd') . "-";
    $stmt = $pdo->prepare("SELECT no_transaksi FROM transaksi_pembayaran WHERE no_transaksi LIKE :p ORDER BY id_transaksi DESC LIMIT 1");
    $stmt->execute([':p' => $prefix . '%']);
    $last = $stmt->fetchColumn();
    $num = $last ? (int)substr($last, -4) + 1 : 1;
    return $prefix . str_pad($num, 4, '0', STR_PAD_LEFT);
}

function generate_no_antrian($pdo, $id_dokter, $tanggal) {
    $stmt = $pdo->prepare("SELECT MAX(no_antrian) FROM antrian WHERE id_dokter = :d AND tanggal_kunjungan = :t");
    $stmt->execute([':d' => $id_dokter, ':t' => $tanggal]);
    return ($stmt->fetchColumn() ?? 0) + 1;
}

function paginate($pdo, $query, $params, $page, $per_page = 25) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM ($query) AS c");
    $stmt->execute($params);
    $total = $stmt->fetchColumn();
    $total_pages = max(1, ceil($total / $per_page));
    $page = max(1, min($page, $total_pages));
    $offset = ($page - 1) * $per_page;
    $stmt = $pdo->prepare($query . " LIMIT $per_page OFFSET $offset");
    $stmt->execute($params);
    return ['data' => $stmt->fetchAll(), 'total' => $total, 'page' => $page, 'per_page' => $per_page, 'total_pages' => $total_pages];
}

function render_pagination($p, $url) {
    if ($p['total_pages'] <= 1) return;
    $pg = $p['page']; $tp = $p['total_pages'];
    echo '<nav><ul class="pagination justify-content-center">';
    echo "<li class='page-item ".($pg<=1?'disabled':'')."'><a class='page-link' href='{$url}?page=".($pg-1)."'>&laquo;</a></li>";
    for ($i = max(1,$pg-2); $i <= min($tp,$pg+2); $i++) {
        echo "<li class='page-item ".($i===$pg?'active':'')."'><a class='page-link' href='{$url}?page={$i}'>{$i}</a></li>";
    }
    echo "<li class='page-item ".($pg>=$tp?'disabled':'')."'><a class='page-link' href='{$url}?page=".($pg+1)."'>&raquo;</a></li>";
    echo '</ul></nav>';
}

function status_badge($s) {
    $b = ['menunggu'=>'bg-warning text-dark','dipanggil'=>'bg-info','selesai'=>'bg-success','batal'=>'bg-danger','pending'=>'bg-warning text-dark','diproses'=>'bg-info','siap'=>'bg-success','diambil'=>'bg-secondary','lunas'=>'bg-success','draft'=>'bg-secondary','disetujui'=>'bg-primary','diterima'=>'bg-success'];
    return $b[$s] ?? 'bg-secondary';
}
