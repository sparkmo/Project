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
    $video_type  = $_POST['video_type'] ?? 'youtube';

    if ($title === '') {
        alert_back('제목을 입력해주세요.');
    }
    if (!array_key_exists($category, CATEGORIES)) {
        $category = 'general';
    }
    if (!in_array($video_type, ['youtube', 'file', 'url'], true)) {
        $video_type = 'youtube';
    }

    // video.video_file 컬럼 하나에 타입을 접두어로 인코딩해서 저장한다
    //   youtube:{videoId} / file:{저장된 파일명} / https://... (외부 URL은 그대로)
    if ($video_type === 'file') {
        $result = save_uploaded_video($_FILES['video_file'] ?? null);
        if (!$result['ok']) {
            alert_back($result['error']);
        }
        $video_file = 'file:' . $result['filename'];
    } elseif ($video_type === 'youtube') {
        $video_source = trim($_POST['video_source'] ?? '');
        if ($video_source === '') {
            alert_back('유튜브 URL 또는 영상 ID를 입력해주세요.');
        }
        $video_file = 'youtube:' . extract_youtube_id($video_source);
    } else {
        $video_source = trim($_POST['video_source'] ?? '');
        if (!filter_var($video_source, FILTER_VALIDATE_URL)) {
            alert_back('올바른 외부 영상 URL이 아닙니다.');
        }
        $video_file = $video_source;
    }

    // 메타데이터 + 영상 소스 등록은 인트라넷 API에 위임한다. (이 API는 생성만 가능하고
    // 수정/삭제는 제공하지 않는다 - 인트라넷 관리자 페이지 전용)
    $data = intranet_api_post(INTRANET_API_VIDEOS_UPLOAD, [
        'title'       => $title,
        'description' => $description,
        'thumbnail'   => $thumbnail,
        'category'    => $category,
        'video_file'  => $video_file,
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
    <form method="post" enctype="multipart/form-data" id="uploadForm">
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
        <div class="field">
            <label>영상 소스 종류</label>
            <select name="video_type" id="video_type_select">
                <option value="youtube">유튜브 (URL 또는 영상 ID)</option>
                <option value="file">직접 업로드 (mp4 / webm / ogg)</option>
                <option value="url">외부 영상 URL</option>
            </select>
        </div>

        <div class="field" id="field-source-text">
            <label>영상 소스 값</label>
            <input type="text" name="video_source" id="video_source_input" placeholder="예: https://www.youtube.com/watch?v=xxxxxxxxxxx">
        </div>

        <div class="field" id="field-source-file" style="display:none;">
            <label>영상 파일 (mp4 / webm / ogg, 최대 500MB)</label>
            <input type="file" name="video_file" id="video_file_input" accept="video/mp4,video/webm,video/ogg,.mp4,.webm,.ogg,.ogv">
        </div>

        <button type="submit" class="btn-submit">업로드</button>
    </form>
</div>

<script>
(function() {
    var select   = document.getElementById('video_type_select');
    var textBox  = document.getElementById('field-source-text');
    var fileBox  = document.getElementById('field-source-file');
    var textInput= document.getElementById('video_source_input');
    var fileInput= document.getElementById('video_file_input');

    function sync() {
        if (select.value === 'file') {
            textBox.style.display = 'none';
            fileBox.style.display = '';
            textInput.required = false;
            fileInput.required = true;
        } else {
            textBox.style.display = '';
            fileBox.style.display = 'none';
            textInput.required = true;
            fileInput.required = false;
        }
    }
    select.addEventListener('change', sync);
    sync();
})();
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
