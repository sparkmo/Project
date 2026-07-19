<?php
require_once __DIR__ . '/common.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/error.php';

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    alert_redirect('잘못된 요청입니다.', 'list.php');
}

// 영상 DB는 이 서버에서 직접 접속할 수 없으므로, 인트라넷 API로 조회를 위임한다.
// 조회수 증가도 인트라넷 서버(API)가 자체적으로 처리한다 (view=1).
$data = intranet_api_get(INTRANET_API_VIDEOS, ['id' => $id, 'view' => 1]);

if (!($data['ok'] ?? false)) {
    alert_redirect('존재하지 않는 영상입니다.', 'list.php');
}

$video = $data['video'];

$page_title = $video['title'];

include __DIR__ . '/includes/header.php';
?>

<div class="video-player">
    <?php if ($video['video_type'] === 'youtube'): ?>
        <iframe src="https://www.youtube.com/embed/<?= htmlspecialchars($video['video_source']) ?>"
                title="<?= htmlspecialchars($video['title']) ?>"
                frameborder="0" allowfullscreen></iframe>
    <?php elseif ($video['video_type'] === 'file'): ?>
        <video controls src="/uploads/<?= htmlspecialchars($video['video_source']) ?>"></video>
    <?php else: ?>
        <video controls src="<?= htmlspecialchars($video['video_source']) ?>"></video>
    <?php endif; ?>
</div>

<span class="badge-category"><?= CATEGORIES[$video['category']] ?? $video['category'] ?></span>
<h1 class="view-title"><?= htmlspecialchars($video['title']) ?></h1>
<div class="view-meta">
    <span>업로더 <?= htmlspecialchars($video['nickname']) ?></span>
    <span><?= htmlspecialchars($video['reg_date']) ?></span>
    <span>조회 <?= (int)$video['view_count'] ?></span>
</div>

<div class="view-desc"><?= nl2br(htmlspecialchars($video['description'] ?? '설명이 없습니다.')) ?></div>

<?php // 영상 수정/삭제는 이 서버에서 더 이상 제공하지 않습니다 (인트라넷 관리자 전용). ?>

<?php include __DIR__ . '/includes/footer.php'; ?>
