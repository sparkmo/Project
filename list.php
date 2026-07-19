<?php
require_once __DIR__ . '/common.php';
require_once __DIR__ . '/auth.php';

$page_title = '전체보기';

$category = $_GET['category'] ?? '';
$keyword  = trim($_GET['q'] ?? '');
$page     = max(1, (int)($_GET['page'] ?? 1));
$per_page = 12;

// 영상 DB는 이 서버에서 직접 접속할 수 없으므로, 인트라넷 API로 조회를 위임한다.
$api_params = ['page' => $page, 'per_page' => $per_page];
if ($category !== '' && array_key_exists($category, CATEGORIES)) {
    $api_params['category'] = $category;
}
if ($keyword !== '') {
    $api_params['q'] = $keyword;
}

$data = intranet_api_get(INTRANET_API_VIDEOS, $api_params);
$videos      = $data['videos'] ?? [];
$total       = (int)($data['total'] ?? 0);
$total_pages = (int)($data['total_pages'] ?? 1);

function yt_thumb($id) { return "https://img.youtube.com/vi/{$id}/hqdefault.jpg"; }
function thumb_of($v) {
    if ($v['thumbnail']) return htmlspecialchars($v['thumbnail']);
    if ($v['video_type'] === 'youtube') return yt_thumb($v['video_source']);
    return '/css/no-thumb.png';
}

include __DIR__ . '/includes/header.php';
?>

<h1 class="section-title" style="margin-top:0;">
    <?= $category !== '' && isset(CATEGORIES[$category]) ? CATEGORIES[$category] : '전체' ?> 영상
</h1>

<form method="get" style="margin-bottom:24px; display:flex; gap:8px;">
    <input type="hidden" name="category" value="<?= htmlspecialchars($category) ?>">
    <input type="text" name="q" value="<?= htmlspecialchars($keyword) ?>" placeholder="제목 검색"
           style="flex:1; padding:10px 12px; background:#0e0e13; border:1px solid #26262f; border-radius:6px; color:#fff;">
    <button class="btn-small" type="submit">검색</button>
</form>

<?php if (empty($videos)): ?>
    <p class="empty-msg">조건에 맞는 영상이 없습니다.</p>
<?php else: ?>
    <div class="video-grid">
        <?php foreach ($videos as $v): ?>
            <a class="video-card" href="/view.php?id=<?= (int)$v['id'] ?>">
                <div class="video-thumb">
                    <img src="<?= thumb_of($v) ?>" alt="<?= htmlspecialchars($v['title']) ?>" loading="lazy">
                </div>
                <div class="video-info">
                    <span class="badge-category"><?= CATEGORIES[$v['category']] ?? $v['category'] ?></span>
                    <p class="v-title"><?= htmlspecialchars($v['title']) ?></p>
                    <div class="v-meta">
                        <span><?= htmlspecialchars($v['nickname']) ?></span>
                        <span>조회 <?= (int)$v['view_count'] ?></span>
                    </div>
                </div>
            </a>
        <?php endforeach; ?>
    </div>

    <div class="pagination">
        <?php for ($p = 1; $p <= $total_pages; $p++): ?>
            <?php
                $qs = $_GET; $qs['page'] = $p;
                $url = '?' . http_build_query($qs);
            ?>
            <?php if ($p === $page): ?>
                <span class="current"><?= $p ?></span>
            <?php else: ?>
                <a href="<?= htmlspecialchars($url) ?>"><?= $p ?></a>
            <?php endif; ?>
        <?php endfor; ?>
    </div>
<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>
