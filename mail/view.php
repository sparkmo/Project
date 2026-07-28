<?php
require_once __DIR__ . '/../common.php';
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/_lib.php';
require_login();
require_mail_login();

$me = current_user($pdo);

$folder = mail_allowed_folder($_GET['folder'] ?? 'INBOX');
$folder_key = mail_folder_key($folder);

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    header('Location: /mail/inbox.php');
    exit;
}

$inbox = mail_connect($folder);
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
$to      = isset($header->to[0]) ? imap_utf8($header->to[0]->mailbox . '@' . $header->to[0]->host) : '(알 수 없음)';
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

$attachments = mail_collect_attachments($structure);

imap_close($inbox);

$page_title  = $subject;
$active_menu = 'mail';
require __DIR__ . '/../includes/header.php';
?>

<div class="topbar">
    <div>
        <div class="breadcrumb">
            <a href="/mail/inbox.php">사내 메일</a> &rsaquo;
            <?= $folder_key === 'SENT' ? '보낸 메일함' : '받은 메일함' ?> &rsaquo; 상세보기
        </div>
        <h1><?= htmlspecialchars($subject) ?></h1>
    </div>
    <div>
        <?php if ($folder_key === 'INBOX'): ?>
            <a href="/mail/send.php?reply_to=<?= (int)$id ?>&folder=INBOX" class="btn btn-primary">답장</a>
        <?php endif; ?>
        <form method="post" action="/mail/delete.php" style="display:inline;" onsubmit="return confirm('이 메일을 삭제할까요?');">
            <input type="hidden" name="folder" value="<?= htmlspecialchars($folder_key) ?>">
            <input type="hidden" name="id" value="<?= (int)$id ?>">
            <button type="submit" class="btn btn-danger">삭제</button>
        </form>
    </div>
</div>

<div class="card">
    <div style="display:flex; justify-content:space-between; color:var(--text-muted); font-size:12px; margin-bottom:16px; border-bottom:1px solid var(--border); padding-bottom:12px;">
        <span>
            <?php if ($folder_key === 'SENT'): ?>
                받는사람: <?= htmlspecialchars($to) ?>
            <?php else: ?>
                보낸사람: <?= htmlspecialchars($from) ?>
            <?php endif; ?>
        </span>
        <span><?= htmlspecialchars($date) ?></span>
    </div>
    <div style="white-space: pre-wrap; line-height:1.8;"><?= htmlspecialchars($body) ?></div>

    <?php if (!empty($attachments)): ?>
        <div style="margin-top:20px; padding-top:16px; border-top:1px solid var(--border);">
            <div style="font-size:12px; color:var(--text-muted); margin-bottom:8px;">첨부파일 (<?= count($attachments) ?>)</div>
            <ul style="list-style:none; padding:0; margin:0;">
                <?php foreach ($attachments as $att): ?>
                    <li style="margin-bottom:6px;">
                        <a href="/mail/attachment.php?folder=<?= htmlspecialchars($folder_key) ?>&id=<?= (int)$id ?>&part=<?= htmlspecialchars($att['part_no']) ?>">
                            📎 <?= htmlspecialchars($att['filename']) ?>
                        </a>
                        <?php if ($att['size'] > 0): ?>
                            <span style="color:var(--text-muted); font-size:12px;"> (<?= number_format($att['size'] / 1024, 1) ?> KB)</span>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
