<?php
require_once __DIR__ . '/common.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/error.php';

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    alert_redirect('잘못된 요청입니다.', 'list.php');
}

// 영상 DB는 이 서버에서 직접 접속할 수 없으므로, 인트라넷 API로 조회를 위임한다.
$data = intranet_api_get(INTRANET_API_VIDEOS, ['id' => $id]);

if (!($data['ok'] ?? false)) {
    alert_redirect('존재하지 않는 영상입니다.', 'list.php');
}

$video = $data['video'];

$page_title = $video['title'];

// video.video_file: youtube:{id} / file:{저장된 파일명} / https://... (외부 URL) / 빈 값(NULL)
$video_file = $video['video_file'] ?? '';

include __DIR__ . '/includes/header.php';
?>

<div class="video-player">
    <?php if (str_starts_with($video_file, 'youtube:')): ?>
        <?php $yt_id = substr($video_file, strlen('youtube:')); ?>
        <iframe src="https://www.youtube.com/embed/<?= htmlspecialchars($yt_id) ?>"
                title="<?= htmlspecialchars($video['title']) ?>"
                frameborder="0" allowfullscreen></iframe>
    <?php elseif (str_starts_with($video_file, 'file:')): ?>
        <?php $filename = substr($video_file, strlen('file:')); ?>
        <video controls src="/uploads/<?= htmlspecialchars($filename) ?>"></video>
    <?php elseif ($video_file !== '' && filter_var($video_file, FILTER_VALIDATE_URL)): ?>
        <video controls src="<?= htmlspecialchars($video_file) ?>"></video>
    <?php elseif (!empty($video['thumbnail'])): ?>
        <img src="<?= htmlspecialchars($video['thumbnail']) ?>" alt="<?= htmlspecialchars($video['title']) ?>" style="width:100%;">
    <?php else: ?>
        <img src="/css/no-thumb.png" alt="<?= htmlspecialchars($video['title']) ?>" style="width:100%;">
    <?php endif; ?>
</div>

<span class="badge-category"><?= CATEGORIES[$video['category']] ?? $video['category'] ?></span>
<h1 class="view-title"><?= htmlspecialchars($video['title']) ?></h1>
<div class="view-meta">
    <span><?= htmlspecialchars($video['upload_date']) ?></span>
</div>

<div class="view-desc"><?= nl2br(htmlspecialchars($video['description'] ?? '설명이 없습니다.')) ?></div>

<?php // 영상 수정/삭제는 이 서버에서 더 이상 제공하지 않습니다 (인트라넷 관리자 전용). ?>

<?php include __DIR__ . '/includes/footer.php'; ?>
