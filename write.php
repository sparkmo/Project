<?php
require_once __DIR__ . '/common.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/error.php';

require_admin();

$page_title = '영상 업로드';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title       = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $video_type  = $_POST['video_type'] ?? 'youtube';
    $category    = $_POST['category'] ?? 'general';

    if ($title === '') {
        alert_back('제목을 입력해주세요.');
    }
    if (!array_key_exists($category, CATEGORIES)) {
        $category = 'general';
    }
    if (!in_array($video_type, ['youtube', 'file', 'url'], true)) {
        $video_type = 'youtube';
    }

    if ($video_type === 'file') {
        // 직접 업로드: 파일 검증 후 uploads/ 에 저장
        $result = save_uploaded_video($_FILES['video_file'] ?? null);
        if (!$result['ok']) {
            alert_back($result['error']);
        }
        $video_source = $result['filename'];
    } else {
        // 유튜브 / 외부 URL: 텍스트 입력값 사용
        $video_source = trim($_POST['video_source'] ?? '');
        if ($video_source === '') {
            alert_back('영상 정보를 입력해주세요.');
        }
        if ($video_type === 'youtube') {
            $video_source = extract_youtube_id($video_source);
        }
    }

    // 영상 파일 자체는 방금 이 서버의 uploads/ 에 저장되었고(직접 업로드인 경우),
    // 메타데이터 등록만 인트라넷 API에 위임한다. (이 API는 생성만 가능하고
    // 수정/삭제는 제공하지 않는다 - 인트라넷 관리자 페이지 전용)
    $data = intranet_api_post(INTRANET_API_VIDEOS_UPLOAD, [
        'writer_username' => $_SESSION['username'],
        'title'            => $title,
        'description'      => $description,
        'video_type'       => $video_type,
        'video_source'     => $video_source,
        'category'         => $category,
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
