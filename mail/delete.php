<?php
require_once __DIR__ . '/../common.php';
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/_lib.php';
require_login();
require_mail_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /mail/inbox.php');
    exit;
}

$folder = mail_allowed_folder($_POST['folder'] ?? 'INBOX');
$folder_key = mail_folder_key($folder);
$id = (int)($_POST['id'] ?? 0);
$page = max(1, (int)($_POST['page'] ?? 1));

if ($id > 0) {
    mail_delete_message($folder, $id);
}

$back = ($folder_key === 'SENT') ? '/mail/sent.php' : '/mail/inbox.php';
header('Location: ' . $back . '?page=' . $page);
exit;
