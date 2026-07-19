<?php
require_once __DIR__ . '/../common.php';
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/_lib.php';
require_login();
require_mail_login();

$me = current_user($pdo);

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    header('Location: /mail/inbox.php');
    exit;
}

$inbox = mail_connect('INBOX');
if ($inbox === false) {
    mail_session_clear();
    header('Location: /mail/login.php');
    exit;
}

$total = imap_num_msg($inbox);
if ($id > $total) {
    imap_close($inbox);
    header('Location: /mail/inbox.php');
    exit;
}

$header  = imap_headerinfo($inbox, $id);
$subject = isset($header->subject) ? imap_utf8($header->subject) : '(제목 없음)';
$from    = isset($header->from[0]) ? imap_utf8($header->from[0]->mailbox . '@' . $header->from[0]->host) : '(알 수 없음)';
$date    = isset($header->date) ? date('Y-m-d H:i', strtotime($header->date)) : '-';

// 메일 본문 추출: 멀티파트면 text/plain 파트를 우선 사용, 없으면 text/html을 태그만 제거해서 사용.
// (수신 메일의 HTML을 그대로 렌더링하면 저장형 XSS 통로가 될 수 있어, 항상 텍스트로만 표시합니다.)
$structure = imap_fetchstructure($inbox, $id);
$body = '';

function mail_find_part($structure, $mime_type, $prefix = '') {
    if (!isset($structure->parts) || !is_array($structure->parts)) {
        return null;
    }
    foreach ($structure->parts as $i => $part) {
        $part_no = $prefix . ($i + 1);
        $type = ($part->type ?? 0) === 0 ? 'text/plain' : null;
        $subtype = strtolower($part->subtype ?? '');
        $full_type = ($part->type ?? 0) === 0 ? 'text/' . $subtype : $subtype;

        if ($full_type === $mime_type) {
            return $part_no;
        }
        if (!empty($part->parts)) {
            $found = mail_find_part($part, $mime_type, $part_no . '.');
            if ($found) return $found;
        }
    }
    return null;
}

if (!empty($structure->parts)) {
    $plain_part = mail_find_part($structure, 'text/plain');
    $html_part  = $plain_part ? null : mail_find_part($structure, 'text/html');

    if ($plain_part) {
        $body = imap_fetchbody($inbox, $id, $plain_part);
    } elseif ($html_part) {
        $body = strip_tags(imap_fetchbody($inbox, $id, $html_part));
    }
} else {
    // 단일 파트 메일
    $raw = imap_body($inbox, $id);
    $body = (($structure->subtype ?? '') === 'HTML') ? strip_tags($raw) : $raw;
}

imap_close($inbox);

$page_title  = $subject;
$active_menu = 'mail';
require __DIR__ . '/../includes/header.php';
?>

<div class="topbar">
    <div>
        <div class="breadcrumb"><a href="/mail/inbox.php">사내 메일</a> &rsaquo; 상세보기</div>
        <h1><?= htmlspecialchars($subject) ?></h1>
    </div>
</div>

<div class="card">
    <div style="display:flex; justify-content:space-between; color:var(--text-muted); font-size:12px; margin-bottom:16px; border-bottom:1px solid var(--border); padding-bottom:12px;">
        <span><?= htmlspecialchars($from) ?></span>
        <span><?= htmlspecialchars($date) ?></span>
    </div>
    <div style="white-space: pre-wrap; line-height:1.8;"><?= htmlspecialchars($body) ?></div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
