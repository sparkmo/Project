<?php
// 이 파일을 include 하기 전에 각 페이지에서 아래 변수를 정의해야 합니다.
// $page_title  (string) - 상단에 표시될 페이지 제목
// $active_menu (string) - SIDEBAR_MENU 의 key 값과 매칭되어 활성 메뉴 표시

$page_title  = $page_title  ?? SITE_NAME;
$active_menu = $active_menu ?? '';
$me = current_user($pdo);
?>
<!DOCTYPE html>
<html lang="ko">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= htmlspecialchars($page_title) ?> - <?= htmlspecialchars(SITE_NAME) ?></title>
<link rel="stylesheet" href="/css/style.css">
</head>
<body>
<div class="app-shell">
    <aside class="sidebar">
        <div class="sidebar-brand">
            <?= htmlspecialchars(SITE_NAME) ?>
            <span class="tag">Company Internal Only</span>
        </div>
        <nav>
            <?php foreach (SIDEBAR_MENU as $item): ?>
                <a href="<?= htmlspecialchars($item['path']) ?>"
                   class="<?= $active_menu === $item['key'] ? 'active' : '' ?>">
                    <?= htmlspecialchars($item['label']) ?>
                </a>
            <?php endforeach; ?>
        </nav>
        <?php if ($me): ?>
        <div class="sidebar-user">
            <div class="name"><?= htmlspecialchars($me['name']) ?></div>
            <div><?= htmlspecialchars($me['dept']) ?> · <?= htmlspecialchars($me['grade'] === 'admin' ? '관리자' : '일반') ?></div>
            <div style="margin-top:8px;"><a href="/change_password.php" style="color:var(--sidebar-text);">비밀번호 변경</a></div>
            <div style="margin-top:4px;"><a href="/logout.php" style="color:var(--sidebar-text);">로그아웃</a></div>
        </div>
        <?php endif; ?>
    </aside>
    <main class="main">
