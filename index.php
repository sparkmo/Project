<?php
require_once __DIR__ . '/common.php';
require_once __DIR__ . '/auth.php';

$page_title = '홈';

// 영상 DB는 이 서버에서 직접 접속할 수 없으므로, 인트라넷 API로 조회를 위임한다.

// 히어로 슬라이더용 최신 영상 5개
$hero_data   = intranet_api_get(INTRANET_API_VIDEOS, ['per_page' => 5]);
$hero_slides = $hero_data['videos'] ?? [];

// 카테고리별 최신 6개씩
$rows = [];
foreach (CATEGORIES as $key => $label) {
    if ($key === 'general') continue;
    $cat_data = intranet_api_get(INTRANET_API_VIDEOS, ['category' => $key, 'per_page' => 6]);
    $rows[$key] = $cat_data['videos'] ?? [];
}

function youtube_thumb($video_id) {
    return "https://img.youtube.com/vi/{$video_id}/hqdefault.jpg";
}

// 히어로 배경용 - 더 큰 해상도 썸네일
function hero_thumb($video) {
    if ($video['thumbnail']) {
        return htmlspecialchars($video['thumbnail']);
    }
    if ($video['video_type'] === 'youtube') {
        return "https://img.youtube.com/vi/{$video['video_source']}/sddefault.jpg";
    }
    return '/css/no-thumb.png';
}

function render_thumb($video) {
    if ($video['thumbnail']) {
        return htmlspecialchars($video['thumbnail']);
    }
    if ($video['video_type'] === 'youtube') {
        return youtube_thumb($video['video_source']);
    }
    return '/css/no-thumb.png';
}

include __DIR__ . '/includes/header.php';
?>

<?php if (!empty($hero_slides)): ?>
<section class="hero-slider" id="heroSlider">
    <?php foreach ($hero_slides as $i => $h): ?>
        <div class="hero-slide <?= $i === 0 ? 'active' : '' ?>" data-index="<?= $i ?>">
            <div class="hero-bg" style="background-image:url('<?= hero_thumb($h) ?>');"></div>
            <div class="hero-gradient"></div>
            <div class="hero-content">
                <?php if ($i === 0): ?>
                    <span class="badge-live"><span class="live-dot"></span>LIVE</span>
                <?php endif; ?>
                <span class="hero-tag">ONLY</span>
                <h1 class="hero-title"><?= htmlspecialchars($h['title']) ?></h1>
                <p class="hero-sub"><?= htmlspecialchars(u8_truncate($h['description'] ?? '', 40)) ?></p>
                <a class="hero-btn" href="/view.php?id=<?= (int)$h['id'] ?>">지금 재생하기</a>
            </div>
        </div>
    <?php endforeach; ?>

    <?php if (count($hero_slides) > 1): ?>
    <button type="button" class="hero-arrow prev" aria-label="이전 영상">&#8249;</button>
    <button type="button" class="hero-arrow next" aria-label="다음 영상">&#8250;</button>
    <div class="hero-dots">
        <?php foreach ($hero_slides as $i => $h): ?>
            <span class="dot-item <?= $i === 0 ? 'active' : '' ?>" data-index="<?= $i ?>"></span>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</section>
<script>
(function() {
    var slides  = document.querySelectorAll('#heroSlider .hero-slide');
    var dots    = document.querySelectorAll('#heroSlider .dot-item');
    var prevBtn = document.querySelector('#heroSlider .hero-arrow.prev');
    var nextBtn = document.querySelector('#heroSlider .hero-arrow.next');
    var current = 0;
    var timer;

    function show(index) {
        var total = slides.length;
        current = (index + total) % total;
        slides.forEach(function(s, i) { s.classList.toggle('active', i === current); });
        dots.forEach(function(d, i) { d.classList.toggle('active', i === current); });
    }
    function startAuto() {
        clearInterval(timer);
        if (slides.length > 1) {
            timer = setInterval(function() { show(current + 1); }, 5000);
        }
    }
    dots.forEach(function(d) {
        d.addEventListener('click', function() { show(parseInt(d.dataset.index, 10)); startAuto(); });
    });
    if (prevBtn) prevBtn.addEventListener('click', function() { show(current - 1); startAuto(); });
    if (nextBtn) nextBtn.addEventListener('click', function() { show(current + 1); startAuto(); });

    startAuto();
})();
</script>
<?php endif; ?>

<div class="tile-row">
    <?php foreach (CATEGORIES as $key => $label): if ($key === 'general') continue; ?>
        <a class="tile tile-<?= htmlspecialchars($key) ?>" href="/list.php?category=<?= urlencode($key) ?>"><?= htmlspecialchars($label) ?></a>
    <?php endforeach; ?>
    <a class="tile tile-all" href="/list.php">전체보기</a>
</div>

<?php foreach ($rows as $key => $list): ?>
    <?php if (empty($list)) continue; ?>
    <h2 class="section-title"><?= CATEGORIES[$key] ?></h2>
    <div class="video-grid">
        <?php foreach ($list as $v): ?>
            <a class="video-card" href="/view.php?id=<?= (int)$v['id'] ?>">
                <div class="video-thumb">
                    <img src="<?= render_thumb($v) ?>" alt="<?= htmlspecialchars($v['title']) ?>" loading="lazy">
                </div>
                <div class="video-info">
                    <p class="v-title"><?= htmlspecialchars($v['title']) ?></p>
                    <div class="v-meta">
                        <span><?= CATEGORIES[$v['category']] ?? $v['category'] ?></span>
                        <span>조회 <?= (int)$v['view_count'] ?></span>
                    </div>
                </div>
            </a>
        <?php endforeach; ?>
    </div>
<?php endforeach; ?>

<?php if (empty($hero_slides)): ?>
    <p class="empty-msg">아직 등록된 영상이 없습니다. 로그인 후 첫 영상을 업로드해보세요!</p>
<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>
