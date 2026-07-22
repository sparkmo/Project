<?php
require_once __DIR__ . '/common.php';

$msg = $_GET['msg'] ?? '오류가 발생했습니다.';
?>
<!DOCTYPE html>
<html lang="ko">
<head>
<meta charset="UTF-8">
<title><?= htmlspecialchars(SITE_NAME) ?> - 알림</title>
<link rel="stylesheet" href="/css/style.css">
</head>
<body class="error-page">
    <div class="error-box">
        <p class="error-message"><?= htmlspecialchars($msg) ?></p>
        <div class="error-actions">
            <a href="javascript:history.back()" class="btn btn-ghost">뒤로 가기</a>
            <a href="/index.php" class="btn btn-primary">대시보드로</a>
        </div>
    </div>
</body>
</html>
