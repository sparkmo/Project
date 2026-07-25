<!DOCTYPE html>
<html lang="ko">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= isset($page_title) ? htmlspecialchars($page_title) . ' - ' . SITE_NAME : SITE_NAME ?></title>
<link rel="stylesheet" href="/css/style.css">
</head>
<body>
<header class="site-header">
    <div class="header-inner">
        <a href="/index.php" class="logo"><?= SITE_NAME ?></a>
        <nav class="main-nav">
            <a href="/index.php">홈</a>
            <a href="/list.php">전체보기</a>
            <?php foreach (CATEGORIES as $key => $label): if ($key === 'general') continue; ?>
                <a href="/list.php?category=<?= urlencode($key) ?>"><?= $label ?></a>
            <?php endforeach; ?>
            <?php if (is_login()): ?>
                <a href="/inquiry.php">문의하기</a>
            <?php endif; ?>
        </nav>
        <div class="user-nav">
            <?php if (is_login()): ?>
                <?php if (is_admin()): ?>
                    <a href="/write.php" class="btn-write">+ 업로드</a>
                <?php endif; ?>
                <a href="/mypage.php"><?= htmlspecialchars($_SESSION['nickname']) ?>님</a>
                <a href="/logout.php">로그아웃</a>
            <?php else: ?>
                <a href="/login.php">로그인</a>
                <a href="/join.php">회원가입</a>
            <?php endif; ?>
        </div>
    </div>
</header>
<main class="site-main">
