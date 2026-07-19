<?php
/**
 * api/videos_upload.php
 *
 * 씨네나잇(OTT) 웹서버 ↔ 인트라넷 서버 간 서버-서버 "영상 등록" 대행 API.
 *
 * 실제 영상 파일(file 업로드 방식)은 지금도 씨네나잇 웹서버의 uploads/ 폴더에
 * 그대로 저장됩니다 (save_uploaded_video() 로직/취약점은 변경되지 않음).
 * 이 API는 그 결과(파일명 또는 유튜브ID/외부URL 등 메타데이터)만 받아서
 * member_db.videos 테이블에 새 행을 등록하는 역할만 합니다.
 *
 * ⚠️ 이 API는 "생성(INSERT)"만 제공합니다. 수정/삭제 엔드포인트는 의도적으로
 *    만들지 않았습니다 - 씨네나잇 웹서버(및 그 토큰)가 탈취되어도 기존 영상을
 *    변조/삭제할 방법이 없도록 하기 위함입니다. 수정/삭제는 인트라넷
 *    관리자 페이지에서만 가능합니다.
 */

require_once __DIR__ . '/../common.php';

header('Content-Type: application/json; charset=utf-8');

function api_fail(int $status, string $message): void {
    http_response_code($status);
    echo json_encode(['ok' => false, 'error' => $message], JSON_UNESCAPED_UNICODE);
    exit;
}

$token = $_SERVER['HTTP_X_API_TOKEN'] ?? ($_POST['token'] ?? '');
if (!is_string($token) || $token === '' || $token !== INTRANET_API_MASTER_TOKEN) {
    log_action($pdo, null, 'api_videos_upload_auth_fail', 'ip=' . ($_SERVER['REMOTE_ADDR'] ?? '-'));
    api_fail(401, 'invalid token');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    api_fail(405, 'POST only');
}

$writer_username = trim($_POST['writer_username'] ?? '');
$title           = trim($_POST['title'] ?? '');
$description     = trim($_POST['description'] ?? '');
$video_type      = $_POST['video_type'] ?? 'youtube';
$video_source    = trim($_POST['video_source'] ?? '');
$category        = trim($_POST['category'] ?? 'general');

if ($writer_username === '' || $title === '' || $video_source === '') {
    api_fail(400, '필수 값이 누락되었습니다.');
}
if (!in_array($video_type, ['youtube', 'file', 'url'], true)) {
    $video_type = 'youtube';
}

try {
    $pdo_member = get_member_pdo();

    $stmt = $pdo_member->prepare('SELECT id FROM members WHERE username = ?');
    $stmt->execute([$writer_username]);
    $writer = $stmt->fetch();

    if (!$writer) {
        api_fail(404, '작성자 회원을 찾을 수 없습니다.');
    }

    $stmt = $pdo_member->prepare(
        'INSERT INTO videos (title, description, video_type, video_source, category, writer_id)
         VALUES (?, ?, ?, ?, ?, ?)'
    );
    $stmt->execute([$title, $description, $video_type, $video_source, $category !== '' ? $category : 'general', $writer['id']]);

    $new_id = (int)$pdo_member->lastInsertId();

    log_action($pdo, null, 'api_videos_upload', 'writer=' . $writer_username . ' new_id=' . $new_id);

    echo json_encode(['ok' => true, 'id' => $new_id], JSON_UNESCAPED_UNICODE);
} catch (PDOException $e) {
    error_log('api/videos_upload.php member_db 연결 실패: ' . $e->getMessage());
    api_fail(502, 'member_db unavailable');
}
