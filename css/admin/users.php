<?php
require_once __DIR__ . '/../common.php';
require_once __DIR__ . '/../auth.php';
require_admin();

$me = current_user($pdo);
$errors = [];
$success = '';

// 등급 변경 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'change_grade') {
    $target_id = (int)($_POST['user_id'] ?? 0);
    $new_grade = ($_POST['grade'] ?? '') === 'admin' ? 'admin' : 'user';

    if ($target_id === (int)$me['id']) {
        $errors[] = '본인의 등급은 변경할 수 없습니다.';
    } else {
        $stmt = $pdo->prepare('UPDATE users SET grade = ? WHERE id = ?');
        $stmt->execute([$new_grade, $target_id]);
        log_action($pdo, $me['id'], 'admin_change_grade', 'user_id=' . $target_id . ' -> ' . $new_grade);
        $success = '등급이 변경되었습니다.';
    }
}

$users = $pdo->query('SELECT id, username, name, dept, grade, email, created_at FROM users ORDER BY id')->fetchAll();

$page_title  = '관리자 - 계정 관리';
$active_menu = 'admin';
require __DIR__ . '/../includes/header.php';
?>

<div class="topbar"><h1>계정 관리</h1></div>

<div class="card">
    <?php if ($success): ?>
        <div style="color:var(--accent); font-size:13px; margin-bottom:12px;"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    <?php foreach ($errors as $e): ?>
        <div style="color:var(--danger); font-size:13px; margin-bottom:12px;"><?= htmlspecialchars($e) ?></div>
    <?php endforeach; ?>

    <table class="data-table">
        <thead>
            <tr><th style="width:50px;">ID</th><th>아이디</th><th>이름</th><th>부서</th><th>이메일</th><th style="width:90px;">등급</th><th style="width:160px;">가입일</th><th style="width:140px;"></th></tr>
        </thead>
        <tbody>
        <?php foreach ($users as $u): ?>
            <tr>
                <td><?= (int)$u['id'] ?></td>
                <td><?= htmlspecialchars($u['username']) ?></td>
                <td><?= htmlspecialchars($u['name']) ?></td>
                <td><?= htmlspecialchars($u['dept']) ?></td>
                <td><?= htmlspecialchars($u['email']) ?></td>
                <td><span class="badge badge-<?= $u['grade'] ?>"><?= $u['grade'] === 'admin' ? '관리자' : '일반' ?></span></td>
                <td><?= htmlspecialchars(format_datetime($u['created_at'])) ?></td>
                <td>
                    <?php if ((int)$u['id'] !== (int)$me['id']): ?>
                        <form method="post" style="display:inline;">
                            <input type="hidden" name="action" value="change_grade">
                            <input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
                            <input type="hidden" name="grade" value="<?= $u['grade'] === 'admin' ? 'user' : 'admin' ?>">
                            <button type="submit" class="btn btn-ghost" style="padding:4px 10px; font-size:12px;">
                                <?= $u['grade'] === 'admin' ? '일반으로 변경' : '관리자로 지정' ?>
                            </button>
                        </form>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
