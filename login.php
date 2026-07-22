<?php
require_once __DIR__ . '/common.php';
require_once __DIR__ . '/auth.php';

if (is_logged_in()) {
    header('Location: /index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = (string)($_POST['password'] ?? '');

    if ($pdo === null) {
        $error = 'DB 서버에 연결할 수 없습니다. 잠시 후 다시 시도해주세요.';
    } elseif ($username === '' || $password === '') {
        $error = '아이디와 비밀번호를 모두 입력해주세요.';
    } elseif (attempt_login($pdo, $username, $password)) {
        log_action($pdo, $_SESSION['user_id'], 'login');
        header('Location: /index.php');
        exit;
    } else {
        $error = '아이디 또는 비밀번호가 올바르지 않습니다.';
    }
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>로그인 - <?= htmlspecialchars(SITE_NAME) ?></title>
<link rel="stylesheet" href="/css/style.css">
</head>
<body class="login-page">
    <div class="login-box">
        <h1><?= htmlspecialchars(SITE_NAME) ?></h1>
        <p class="sub">사내 구성원만 이용할 수 있습니다.</p>

        <?php if ($error): ?>
            <p style="color:var(--danger); font-size:13px; margin-bottom:16px;"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>

        <form method="post" action="/login.php">
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
