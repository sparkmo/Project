<?php
require_once __DIR__ . '/../common.php';
require_once __DIR__ . '/../auth.php';
require_login();

$me = current_user($pdo);
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title   = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $is_pinned = ($_POST['is_pinned'] ?? '') === '1' && $me['grade'] === 'admin' ? 1 : 0;

    if ($title === '' || $content === '') {
        $error = '제목과 내용을 모두 입력해주세요.';
    } else {
        $stmt = $pdo->prepare(
            'INSERT INTO notices (title, content, author_id, is_pinned) VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([$title, $content, $me['id'], $is_pinned]);
        $new_id = $pdo->lastInsertId();
        log_action($pdo, $me['id'], 'create_notice', 'notice_id=' . $new_id);
        header('Location: /notice/view.php?id=' . $new_id);
        exit;
    }
}

$page_title  = '공지사항 작성';
$active_menu = 'notice';
require __DIR__ . '/../includes/header.php';
?>

<div class="topbar">
    <div>
        <div class="breadcrumb"><a href="/notice/list.php">공지사항</a> &rsaquo; 글쓰기</div>
        <h1>공지사항 작성</h1>
    </div>
</div>

<div class="card">
    <?php if ($error): ?>
        <p style="color:var(--danger); font-size:13px; margin-bottom:16px;"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <form method="post" action="/notice/write.php">
        <div class="form-group">
            <label for="title">제목</label>
            <input type="text" id="title" name="title" required
                   value="<?= htmlspecialchars($_POST['title'] ?? '') ?>">
        </div>
        <div class="form-group">
            <label for="content">내용</label>
            <textarea id="content" name="content" required><?= htmlspecialchars($_POST['content'] ?? '') ?></textarea>
        </div>
        <?php if ($me['grade'] === 'admin'): ?>
        <div class="form-group">
            <label>
                <input type="checkbox" name="is_pinned" value="1" style="width:auto;">
                상단 고정
            </label>
        </div>
        <?php endif; ?>
        <button type="submit" class="btn btn-primary">등록</button>
        <a href="/notice/list.php" class="btn btn-ghost">취소</a>
    </form>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
