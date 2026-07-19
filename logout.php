<?php
require_once __DIR__ . '/common.php';
require_once __DIR__ . '/auth.php';

if (is_logged_in()) {
    log_action($pdo, $_SESSION['user_id'], 'logout');
}
logout();
header('Location: /login.php');
exit;
