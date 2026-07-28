<?php
require_once __DIR__ . '/../common.php';
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/_lib.php';
require_login();
require_mail_login();

$me = current_user($pdo);

$page = max(1, (int)($_GET['page'] ?? 1));
$per_page = 20;

$result = mail_list_messages(MAIL_SENT_FOLDER, $page, $per_page);
// 보낸편지함 폴더 자체가 서버에 없을 수도 있음(아직 한 번도 발송한 적 없는 경우 등) -
// 이때는 "메일 서버 로그인 만료" 오류로 오인되지 않도록 별도 안내만 보여줌.
$folder_missing = !$result['ok'];
$messages = $result['ok'] ? $result['messages'] : [];
$total    = $result['ok'] ? $result['total'] : 0;

$total_pages = max(1, (int)ceil($total / $per_page));

$page_title  = '보낸 메일함';
$active_menu = 'mail';
require __DIR__ . '/../includes/header.php';
?>

<div class="topbar">
    <h1>보낸 메일함 <span style="font-size:13px; color:var(--muted, #888);">(<?= htmlspecialchars($me['email']) ?>)</span></h1>
    <div>
        <a href="/mail/send.php" class="btn btn-primary">메일 쓰기</a>
        <a href="/mail/logout.php" class="btn btn-ghost">메일함 로그아웃</a>
    </div>
</div>

<div class="card" style="margin-bottom:16px; padding:8px 16px;">
    <a href="/mail/inbox.php" style="color:var(--text-muted, #888);">받은 메일함</a>
    &nbsp;|&nbsp;
    <a href="/mail/sent.php" style="font-weight:600;">보낸 메일함</a>
</div>

<div class="card">
    <?php if ($folder_missing): ?>
        <div class="empty-state">
            보낸 메일함 폴더(<?= htmlspecialchars(MAIL_SENT_FOLDER) ?>)를 찾을 수 없습니다.
            아직 보낸 메일이 없거나, 메일 서버에 해당 폴더가 없을 수 있습니다.
        </div>
    <?php elseif (empty($messages)): ?>
        <div class="empty-state">보낸 메일이 없습니다.</div>
    <?php else: ?>
        <table class="data-table">
            <thead>
                <tr>
                    <th>제목</th>
                    <th style="width:220px;">받는사람</th>
                    <th style="width:150px;">보낸날짜</th>
                    <th style="width:70px;"></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($messages as $m): ?>
                <?php
                    $subject = isset($m->subject) ? imap_utf8($m->subject) : '(제목 없음)';
                    // Sent 폴더에서는 "받는사람"이 더 의미 있는 정보라 to 필드를 우선 사용
                    $to   = isset($m->to) ? imap_utf8($m->to) : (isset($m->from) ? imap_utf8($m->from) : '(알 수 없음)');
                    $date = isset($m->date) ? date('Y-m-d H:i', strtotime($m->date)) : '-';
                ?>
                <tr>
                    <td><a href="/mail/view.php?folder=SENT&id=<?= (int)$m->msgno ?>"><?= htmlspecialchars($subject) ?></a></td>
                    <td><?= htmlspecialchars($to) ?></td>
                    <td><?= htmlspecialchars($date) ?></td>
                    <td>
                        <form method="post" action="/mail/delete.php" onsubmit="return confirm('이 메일을 삭제할까요?');">
                            <input type="hidden" name="folder" value="SENT">
                            <input type="hidden" name="id" value="<?= (int)$m->msgno ?>">
                            <input type="hidden" name="page" value="<?= (int)$page ?>">
                            <button type="submit" class="btn btn-danger" style="padding:4px 10px; font-size:12px;">삭제</button>
                        </form>
                    </td>
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
