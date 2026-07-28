<?php
require_once __DIR__ . '/common.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/mail/_lib.php';
require_login();

$me = current_user($pdo);

// 최근 공지 5건
$notices = $pdo->query(
    'SELECT n.notice_id AS id, n.title, n.created_at, e.name AS author_name
     FROM notice n JOIN employee e ON e.employee_id = n.employee_id
     ORDER BY n.created_at DESC LIMIT 5'
)->fetchAll();

// 씨네나잇 회원/영상 수 (member_db 접속 실패해도 대시보드 전체가 죽지 않도록 별도 try)
$member_count = null;
$video_count  = null;
try {
    $pdo_member  = get_member_pdo();
    $member_count = (int)$pdo_member->query('SELECT COUNT(*) FROM members')->fetchColumn();
    $video_count  = (int)$pdo_member->query('SELECT COUNT(*) FROM videos')->fetchColumn();
} catch (PDOException $e) {
    error_log('index.php member_db 연결 실패: ' . $e->getMessage());
}

// 안읽은 메일 수 (메일함 로그인이 안 되어 있으면 null - 카드에서 안내만 표시)
$unseen_mail_count = mail_unseen_count();

$page_title  = '대시보드';
$active_menu = 'dashboard';
require __DIR__ . '/includes/header.php';
?>

<div class="topbar">
    <h1>대시보드</h1>
</div>

<div class="stat-grid">
    <div class="stat-card">
        <div class="stat-label">씨네나잇 회원 수</div>
        <div class="stat-value"><?= $member_count !== null ? $member_count : '-' ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">씨네나잇 등록 영상 수</div>
        <div class="stat-value"><?= $video_count !== null ? $video_count : '-' ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">공지사항 수</div>
        <div class="stat-value"><?= count($notices) ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">안읽은 메일</div>
        <?php if ($unseen_mail_count !== null): ?>
            <div class="stat-value"><a href="/mail/inbox.php"><?= $unseen_mail_count ?>건</a></div>
        <?php else: ?>
            <div class="stat-value" style="font-size:14px;"><a href="/mail/login.php">로그인 필요</a></div>
        <?php endif; ?>
    </div>
    <div class="stat-card">
        <div class="stat-label">내 부서</div>
        <div class="stat-value" style="font-size:16px;"><?= htmlspecialchars($me['dept']) ?></div>
    </div>
</div>

<div class="card">
    <div class="card-title">
        공지사항
        <a href="/notice/list.php" style="font-size:12px; font-weight:400;">전체보기 &rsaquo;</a>
    </div>
    <?php if (empty($notices)): ?>
        <div class="empty-state">등록된 공지사항이 없습니다.</div>
    <?php else: ?>
        <table class="data-table">
            <thead><tr><th>제목</th><th>작성자</th><th>작성일</th></tr></thead>
            <tbody>
            <?php foreach ($notices as $n): ?>
                <tr>
                    <td>
                        <a href="/notice/view.php?id=<?= (int)$n['id'] ?>"><?= htmlspecialchars($n['title']) ?></a>
                    </td>
                    <td><?= htmlspecialchars($n['author_name']) ?></td>
                    <td><?= htmlspecialchars(format_datetime($n['created_at'])) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
