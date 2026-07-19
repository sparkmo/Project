<?php
require_once __DIR__ . '/../common.php';
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/_lib.php';
require_login();

mail_session_clear();
header('Location: /mail/login.php');
exit;
