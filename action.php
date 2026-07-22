<?php
require_once __DIR__ . '/common.php';
require_once __DIR__ . '/auth.php';
require_login();

$me = current_user($pdo);
$type = $_GET['type'] ?? '';
$id   = (int)($_GET['id'] ?? 0);

switch ($type) {

    case 'notice_delete':
        $stmt = $pdo->prepare('SELECT employee_id AS author_id FROM notice WHERE notice_id = ?');
        $stmt->execute([$id]);
        $notice = $stmt->fetch();

        if (!$notice) {
            header('Location: /error.php?msg=' . urlencode('존재하지 않는 공지사항입니다.'));
            exit;
        }
        if ($me['grade'] !== 'admin' && $me['id'] != $notice['author_id']) {
            header('Location: /error.php?msg=' . urlencode('삭제 권한이 없습니다.'));
            exit;
        }

        $pdo->prepare('DELETE FROM notice WHERE notice_id = ?')->execute([$id]);
        log_action($pdo, $me['id'], 'delete_notice', 'notice_id=' . $id);
        header('Location: /notice/list.php');
        exit;

    default:
        header('Location: /error.php?msg=' . urlencode('알 수 없는 요청입니다.'));
        exit;
}
