<?php
require_once __DIR__ . '/../common.php';
require_once __DIR__ . '/../auth.php';
require_login();

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    header('Location: /error.php?msg=' . urlencode('잘못된 접근입니다.'));
    exit;
}

// 조회수 증가
$pdo->prepare('UPDATE notices SET view_count = view_count + 1 WHERE id = ?')->execute([$id]);

$stmt = $pdo->prepare(
    'SELECT n.*, u.name AS author_name
     FROM notices n JOIN users u ON u.id = n.author_id
     WHERE n.id = ?'
);
$stmt->execute([$id]);
$notice = $stmt->fetch();

if (!$notice) {
    header('Location: /error.php?msg=' . urlencode('존재하지 않는 공지사항입니다.'));
    exit;
}

$me = current_user($pdo);
$can_edit = $me['grade'] === 'admin' || $me['id'] == $notice['author_id'];

$page_title  = $notice['title'];
$active_menu = 'notice';
require __DIR__ . '/../includes/header.php';
?>

<div class="topbar">
    <div>
        <div class="breadcrumb"><a href="/notice/list.php">공지사항</a> &rsaquo; 상세보기</div>
        <h1><?= htmlspecialchars($notice['title']) ?></h1>
    </div>
    <?php if ($can_edit): ?>
    <div>
        <a href="/notice/modify.php?id=<?= (int)$notice['id'] ?>" class="btn btn-ghost">수정</a>
        <a href="/action.php?type=notice_delete&id=<?= (int)$notice['id'] ?>"
           class="btn btn-danger"
           onclick="return confirm('정말 삭제하시겠습니까?')">삭제</a>
    </div>
    <?php endif; ?>
</div>

<div class="card">
    <div style="display:flex; justify-content:space-between; color:var(--text-muted); font-size:12px; margin-bottom:16px; border-bottom:1px solid var(--border); padding-bottom:12px;">
        <span><?= htmlspecialchars($notice['author_name']) ?></span>
        <span><?= htmlspecialchars(format_datetime($notice['created_at'])) ?> · 조회 <?= (int)$notice['view_count'] ?></span>
    </div>
    <div style="white-space: pre-wrap; line-height:1.8;"><?= htmlspecialchars($notice['content']) ?></div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
