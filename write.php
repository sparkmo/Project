<?php
require_once __DIR__ . '/common.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/error.php';

require_admin();

$page_title = '영상 업로드';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title       = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $thumbnail   = trim($_POST['thumbnail'] ?? '');
    $category    = $_POST['category'] ?? 'general';

    if ($title === '') {
        alert_back('제목을 입력해주세요.');
    }
    if (!array_key_exists($category, CATEGORIES)) {
        $category = 'general';
    }

    // ott_db.video 테이블은 title, description, thumbnail, category, upload_date만 있고
    // (writer_username/video_type/video_source 컬럼은 최종 스키마에 없음),
    // 메타데이터 등록만 인트라넷 API에 위임한다. (이 API는 생성만 가능하고
    // 수정/삭제는 제공하지 않는다 - 인트라넷 관리자 페이지 전용)
    $data = intranet_api_post(INTRANET_API_VIDEOS_UPLOAD, [
        'title'       => $title,
        'description' => $description,
        'thumbnail'   => $thumbnail,
        'category'    => $category,
    ]);

    if (!($data['ok'] ?? false)) {
        alert_back($data['error'] ?? '영상 등록에 실패했습니다.');
    }

    $new_id = $data['id'];
    alert_redirect('영상이 등록되었습니다.', 'view.php?id=' . $new_id);
}

include __DIR__ . '/includes/header.php';
?>

<div class="form-box wide">
    <h2>영상 업로드</h2>
    <form method="post" id="uploadForm">
        <div class="field">
            <label>제목</label>
            <input type="text" name="title" required maxlength="200">
        </div>
        <div class="field">
            <label>설명</label>
            <textarea name="description" maxlength="2000"></textarea>
        </div>
        <div class="field">
            <label>카테고리</label>
            <select name="category">
                <?php foreach (CATEGORIES as $key => $label): ?>
                    <option value="<?= $key ?>"><?= $label ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="field">
            <label>썸네일 이미지 URL</label>
            <input type="text" name="thumbnail" placeholder="예: https://example.com/thumb.jpg (비워두면 기본 이미지 사용)">
        </div>

        <button type="submit" class="btn-submit">업로드</button>
    </form>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
