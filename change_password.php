<?php
/**
 * change_password.php
 * 로그인한 직원 본인의 비밀번호 변경.
 *
 * 회원가입 페이지는 만들지 않습니다 — 인트라넷 계정은 직원 채용/입사 처리 시
 * 관리자가 직접 employee 테이블에 생성하는 것이 맞고, 사내 시스템에 외부 대상
 * 셀프 회원가입 창구를 여는 것 자체가 부적절하기 때문입니다.
 *
 * ⚠️ [VULN] auth.php와 동일하게, 현재 employee.password는 해시가 아니라
 *    평문으로 저장/비교됩니다 (hash_equals()로 평문 비교). 이 페이지도 그
 *    상태에 맞춰 "새 비밀번호를 평문 그대로 저장"하도록 구현했습니다.
 *    나중에 auth.php의 attempt_login()을 password_verify()로 되돌릴 때,
 *    이 파일의 UPDATE 부분도 반드시 password_hash()로 같이 바꿔야 합니다
 *    (아래 "TODO(해시 전환)" 표시된 줄 참고).
 */
require_once __DIR__ . '/common.php';
require_once __DIR__ . '/auth.php';
require_login();

$me = current_user($pdo);

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_password = (string)($_POST['current_password'] ?? '');
    $new_password     = (string)($_POST['new_password'] ?? '');
    $new_password_confirm = (string)($_POST['new_password_confirm'] ?? '');

    if ($pdo === null) {
        $error = 'DB 서버에 연결할 수 없습니다. 잠시 후 다시 시도해주세요.';
    } elseif ($current_password === '' || $new_password === '' || $new_password_confirm === '') {
        $error = '모든 항목을 입력해주세요.';
    } elseif (strlen($new_password) < 4) {
        $error = '새 비밀번호는 4자 이상이어야 합니다.';
    } elseif ($new_password !== $new_password_confirm) {
        $error = '새 비밀번호가 서로 일치하지 않습니다.';
    } else {
        // 현재 비밀번호 확인 (auth.php의 attempt_login()과 동일한 방식 - 평문 비교)
        $stmt = $pdo->prepare('SELECT password FROM employee WHERE employee_id = ?');
        $stmt->execute([$me['id']]);
        $row = $stmt->fetch();

        if (!$row || !hash_equals((string)$row['password'], $current_password)) {
            $error = '현재 비밀번호가 올바르지 않습니다.';
        } elseif ($new_password === $current_password) {
            $error = '새 비밀번호는 현재 비밀번호와 달라야 합니다.';
        } else {
            // TODO(해시 전환): 여기를 password_hash($new_password, PASSWORD_DEFAULT)로 바꾸고,
            // auth.php의 attempt_login()도 password_verify()로 같이 되돌릴 것.
            $update = $pdo->prepare('UPDATE employee SET password = ? WHERE employee_id = ?');
            $update->execute([$new_password, $me['id']]);

            log_action($pdo, $me['id'], 'change_password');
            $success = '비밀번호가 변경되었습니다.';
        }
    }
}

$page_title  = '비밀번호 변경';
$active_menu = '';
require __DIR__ . '/includes/header.php';
?>

<div class="topbar"><h1>비밀번호 변경</h1></div>

<div class="card" style="max-width:420px;">
    <?php if ($success): ?>
        <p style="color:var(--accent); font-size:13px; margin-bottom:16px;"><?= htmlspecialchars($success) ?></p>
    <?php endif; ?>
    <?php if ($error): ?>
        <p style="color:var(--danger); font-size:13px; margin-bottom:16px;"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <form method="post" action="/change_password.php">
        <div class="form-group">
            <label for="current_password">현재 비밀번호</label>
            <input type="password" id="current_password" name="current_password"
                   autocomplete="current-password" required>
        </div>
        <div class="form-group">
            <label for="new_password">새 비밀번호</label>
            <input type="password" id="new_password" name="new_password"
                   autocomplete="new-password" required>
        </div>
        <div class="form-group">
            <label for="new_password_confirm">새 비밀번호 확인</label>
            <input type="password" id="new_password_confirm" name="new_password_confirm"
                   autocomplete="new-password" required>
        </div>
        <button type="submit" class="btn btn-primary">비밀번호 변경</button>
    </form>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
