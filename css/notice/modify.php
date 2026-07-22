<?php
require_once __DIR__ . '/../common.php';
require_once __DIR__ . '/../auth.php';
require_login();

$me = current_user($pdo);
$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);

$stmt = $pdo->prepare('SELECT * FROM notices WHERE id = ?');
$stmt->execute([$id]);
$notice = $stmt->fetch();

if (!$notice) {
    header('Location: /error.php?msg=' . urlencode('존재하지 않는 공지사항입니다.'));
    exit;
}

$can_edit = $me['grade'] === 'admin' || $me['id'] == $notice['author_id'];
if (!$can_edit) {
    header('Location: /error.php?msg=' . urlencode('수정 권한이 없습니다.'));
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title   = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $is_pinned = ($_POST['is_pinned'] ?? '') === '1' && $me['grade'] === 'admin' ? 1 : $notice['is_pinned'];

    if ($title === '' || $content === '') {
        $error = '제목과 내용을 모두 입력해주세요.';
    } else {
        $stmt = $pdo->prepare(
            'UPDATE notices SET title = ?, content = ?, is_pinned = ? WHERE id = ?'
        );
        $stmt->execute([$title, $content, $is_pinned, $id]);
        log_action($pdo, $me['id'], 'modify_notice', 'notice_id=' . $id);
        header('Location: /notice/view.php?id=' . $id);
        exit;
    }
    $notice['title'] = $title;
    $notice['content'] = $content;
}

$page_title  = '공지사항 수정';
$active_menu = 'notice';
require __DIR__ . '/../includes/header.php';
?>

<div class="topbar">
    <div>
        <div class="breadcrumb"><a href="/notice/list.php">공지사항</a> &rsaquo; 수정</div>
        <h1>공지사항 수정</h1>
    </div>
</div>

<div class="card">
    <?php if ($error): ?>
        <p style="color:var(--danger); font-size:13px; margin-bottom:16px;"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <form method="post" action="/notice/modify.php?id=<?= (int)$id ?>">
        <input type="hidden" name="id" value="<?= (int)$id ?>">
        <div class="form-group">
            <label for="title">제목</label>
            <input type="text" id="title" name="title" required value="<?= htmlspecialchars($notice['title']) ?>">
        </div>
        <div class="form-group">
            <label for="content">내용</label>
            <textarea id="content" name="content" required><?= htmlspecialchars($notice['content']) ?></textarea>
        </div>
        <?php if ($me['grade'] === 'admin'): ?>
        <div class="form-group">
            <label>
                <input type="checkbox" name="is_pinned" value="1" style="width:auto;" <?= $notice['is_pinned'] ? 'checked' : '' ?>>
                상단 고정
            </label>
        </div>
        <?php endif; ?>
        <button type="submit" class="btn btn-primary">저장</button>
        <a href="/notice/view.php?id=<?= (int)$id ?>" class="btn btn-ghost">취소</a>
    </form>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
