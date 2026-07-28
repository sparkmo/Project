<?php
/**
 * api/videos_upload.php
 *
 * 영상 등록(생성 전용) API. ott_db.video 테이블은 회원과 관계가 없으므로
 * writer_username 개념 자체를 제거했습니다.
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

$title        = trim($_POST['title'] ?? '');
$description  = trim($_POST['description'] ?? '');
$thumbnail    = trim($_POST['thumbnail'] ?? '');
$category     = trim($_POST['category'] ?? 'general');
$video_type   = trim($_POST['video_type'] ?? 'youtube');
$video_source = trim($_POST['video_source'] ?? '');

if ($title === '') {
    api_fail(400, '제목은 필수입니다.');
}
if (!in_array($video_type, ['youtube', 'file', 'url'], true)) {
    $video_type = 'youtube';
}
if ($video_source === '') {
    api_fail(400, '영상 소스(video_source)는 필수입니다.');
}

try {
    $pdo_member = get_member_pdo();

    $stmt = $pdo_member->prepare(
        'INSERT INTO video (title, description, thumbnail, category, video_type, video_source)
         VALUES (?, ?, ?, ?, ?, ?)'
    );
    $stmt->execute([$title, $description, $thumbnail !== '' ? $thumbnail : null, $category !== '' ? $category : 'general', $video_type, $video_source]);

    $new_id = (int)$pdo_member->lastInsertId();

    log_action($pdo, null, 'api_videos_upload', 'new_id=' . $new_id);

    echo json_encode(['ok' => true, 'id' => $new_id], JSON_UNESCAPED_UNICODE);
} catch (PDOException $e) {
    error_log('api/videos_upload.php ott 연결 실패: ' . $e->getMessage());
    api_fail(502, 'ott db unavailable');
}
