<?php
require_once __DIR__ . '/common.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/error.php';

require_login();

$page_title = '마이페이지';

// 회원 DB는 이 서버에서 직접 접속할 수 없으므로, 인트라넷 API로 조회를 위임한다.
$member_data = intranet_api_get(INTRANET_API_MEMBERS, ['username' => $_SESSION['username']]);
$user = $member_data['members'][0] ?? null;
if (!$user) {
    alert_redirect('회원 정보를 불러올 수 없습니다.', 'index.php');
}

$videos_data = intranet_api_get(INTRANET_API_VIDEOS, ['writer_username' => $_SESSION['username'], 'per_page' => 50]);
$my_videos = $videos_data['videos'] ?? [];

function yt_thumb2($id) { return "https://img.youtube.com/vi/{$id}/hqdefault.jpg"; }
function thumb_of2($v) {
    if ($v['thumbnail']) return htmlspecialchars($v['thumbnail']);
    if ($v['video_type'] === 'youtube') return yt_thumb2($v['video_source']);
    return '/css/no-thumb.png';
}

include __DIR__ . '/includes/header.php';
?>

<div class="profile-box">
    <div class="avatar"><?= htmlspecialchars(u8_first_char($user['nickname'])) ?></div>
    <div>
        <h2 style="margin:0 0 4px;"><?= htmlspecialchars($user['nickname']) ?></h2>
        <p style="margin:0; color:var(--text-dim); font-size:13px;">
            @<?= htmlspecialchars($user['username']) ?> ·
            <?= htmlspecialchars($user['email']) ?> ·
            <?= $user['grade'] === 'admin' ? '관리자' : '일반회원' ?>
        </p>
    </div>
</div>

<h2 class="section-title" style="margin-top:0;">내가 올린 영상 (<?= count($my_videos) ?>)</h2>

<?php if (empty($my_videos)): ?>
    <p class="empty-msg">아직 업로드한 영상이 없습니다.</p>
<?php else: ?>
    <div class="video-grid">
        <?php foreach ($my_videos as $v): ?>
            <a class="video-card" href="/view.php?id=<?= (int)$v['id'] ?>">
                <div class="video-thumb">
                    <img src="<?= thumb_of2($v) ?>" alt="<?= htmlspecialchars($v['title']) ?>" loading="lazy">
                </div>
                <div class="video-info">
                    <span class="badge-category"><?= CATEGORIES[$v['category']] ?? $v['category'] ?></span>
                    <p class="v-title"><?= htmlspecialchars($v['title']) ?></p>
                    <div class="v-meta">
                        <span>조회 <?= (int)$v['view_count'] ?></span>
                        <span><?= htmlspecialchars($v['reg_date']) ?></span>
                    </div>
                </div>
            </a>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>
