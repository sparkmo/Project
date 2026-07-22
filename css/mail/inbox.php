<?php
require_once __DIR__ . '/../common.php';
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/_lib.php';
require_login();
require_mail_login();

$me = current_user($pdo);

$page = max(1, (int)($_GET['page'] ?? 1));
$per_page = 20;

$messages = [];
$total = 0;
$error = '';

$inbox = mail_connect('INBOX');
if ($inbox === false) {
    // 세션에 저장된 비밀번호가 더 이상 유효하지 않을 수 있으므로 다시 로그인하도록 유도
    mail_session_clear();
    header('Location: /mail/login.php');
    exit;
}

$total = imap_num_msg($inbox);

if ($total > 0) {
    // 최신 메일이 위로 오도록 번호를 거꾸로 계산 (imap 메시지 번호는 1부터 시작, 오래된 순)
    $start = $total - ($page - 1) * $per_page;
    $end   = max(1, $start - $per_page + 1);

    if ($start >= 1) {
        $range = $end . ':' . $start;
        $overview = imap_fetch_overview($inbox, $range, 0);
        // 최신순 정렬
        usort($overview, function ($a, $b) { return $b->msgno <=> $a->msgno; });
        $messages = $overview;
    }
}

imap_close($inbox);

$total_pages = max(1, (int)ceil($total / $per_page));

$page_title  = '사내 메일함';
$active_menu = 'mail';
require __DIR__ . '/../includes/header.php';
?>

<div class="topbar">
    <h1>받은 메일함 <span style="font-size:13px; color:var(--muted, #888);">(<?= htmlspecialchars($me['email']) ?>)</span></h1>
    <a href="/mail/logout.php" class="btn btn-ghost">메일함 로그아웃</a>
</div>

<div class="card">
    <?php if (empty($messages)): ?>
        <div class="empty-state">받은 메일이 없습니다.</div>
    <?php else: ?>
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width:60px;"></th>
                    <th>제목</th>
                    <th style="width:220px;">보낸사람</th>
                    <th style="width:160px;">받은날짜</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($messages as $m): ?>
                <?php
                    $subject = isset($m->subject) ? imap_utf8($m->subject) : '(제목 없음)';
                    $from    = isset($m->from) ? imap_utf8($m->from) : '(알 수 없음)';
                    $date    = isset($m->date) ? date('Y-m-d H:i', strtotime($m->date)) : '-';
                    $unseen  = empty($m->seen);
                ?>
                <tr>
                    <td><?php if ($unseen): ?><span class="badge badge-pinned">안읽음</span><?php endif; ?></td>
                    <td><a href="/mail/view.php?id=<?= (int)$m->msgno ?>"><?= htmlspecialchars($subject) ?></a></td>
                    <td><?= htmlspecialchars($from) ?></td>
                    <td><?= htmlspecialchars($date) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

        <div class="pagination">
            <?php for ($p = 1; $p <= $total_pages; $p++): ?>
                <?php if ($p === $page): ?>
                    <span class="current"><?= $p ?></span>
                <?php else: ?>
                    <a href="?page=<?= $p ?>"><?= $p ?></a>
                <?php endif; ?>
            <?php endfor; ?>
        </div>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
