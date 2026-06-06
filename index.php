<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

if (isset($_SESSION['user_id'])) {
    redirect(BASE_URL . '/dashboard/');
} else {
    redirect(BASE_URL . '/auth/login.php');
}
