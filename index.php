<?php
require_once __DIR__ . '/common.php';
require_once __DIR__ . '/auth.php';
require_login();

$me = current_user($pdo);

// 최근 공지 5건 (고정글 우선)
$notices = $pdo->query(
    'SELECT n.id, n.title, n.is_pinned, n.created_at, u.name AS author_name
     FROM notices n JOIN users u ON u.id = n.author_id
     ORDER BY n.is_pinned DESC, n.created_at DESC LIMIT 5'
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
                        <?php if ($n['is_pinned']): ?><span class="badge badge-pinned">고정</span> <?php endif; ?>
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
