<?php
/**
 * api/videos.php
 *
 * 씨네나잇(OTT) 웹서버 ↔ 인트라넷 서버 간 서버-서버 영상 조회 대행 API.
 *
 * ott_db 최종 스키마: video 테이블은 회원과 관계를 갖지 않습니다(독립).
 * 컬럼: video_id, title, description, thumbnail, category, upload_date
 * (기존의 writer_id/video_type/video_source/view_count는 새 스키마에 없습니다.)
 *
 * 지원 파라미터 (전부 GET):
 *   - id       : 단일 영상 상세 조회
 *   - category : 카테고리 필터
 *   - q        : 제목 검색 키워드
 *   - page, per_page : 페이지네이션 (기본 1 / 12, 최대 per_page 50)
 */

require_once __DIR__ . '/../common.php';

header('Content-Type: application/json; charset=utf-8');

function api_fail(int $status, string $message): void {
    http_response_code($status);
    echo json_encode(['ok' => false, 'error' => $message], JSON_UNESCAPED_UNICODE);
    exit;
}

$token = $_SERVER['HTTP_X_API_TOKEN'] ?? ($_GET['token'] ?? '');
if (!is_string($token) || $token === '' || $token !== INTRANET_API_MASTER_TOKEN) {
    log_action($pdo, null, 'api_videos_auth_fail', 'ip=' . ($_SERVER['REMOTE_ADDR'] ?? '-'));
    api_fail(401, 'invalid token');
}

try {
    $pdo_member = get_member_pdo();

    $id = (int)($_GET['id'] ?? 0);

    if ($id > 0) {
        $stmt = $pdo_member->prepare('SELECT * FROM video WHERE video_id = ?');
        $stmt->execute([$id]);
        $video = $stmt->fetch();

        if (!$video) {
            api_fail(404, '존재하지 않는 영상입니다.');
        }

        log_action($pdo, null, 'api_videos_view', 'id=' . $id);
        echo json_encode(['ok' => true, 'video' => $video], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $category = trim($_GET['category'] ?? '');
    $keyword  = trim($_GET['q'] ?? '');
    $page     = max(1, (int)($_GET['page'] ?? 1));
    $per_page = min(50, max(1, (int)($_GET['per_page'] ?? 12)));
    $offset   = ($page - 1) * $per_page;

    $where  = [];
    $params = [];

    if ($category !== '') {
        $where[] = 'category = ?';
        $params[] = $category;
    }
    if ($keyword !== '') {
        $where[] = 'title LIKE ?';
        $params[] = '%' . $keyword . '%';
    }
    $where_sql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

    $count_stmt = $pdo_member->prepare("SELECT COUNT(*) FROM video $where_sql");
    $count_stmt->execute($params);
    $total = (int)$count_stmt->fetchColumn();

    $sql = "SELECT * FROM video
            $where_sql
            ORDER BY upload_date DESC
            LIMIT $per_page OFFSET $offset";
    $stmt = $pdo_member->prepare($sql);
    $stmt->execute($params);
    $videos = $stmt->fetchAll();

    log_action($pdo, null, 'api_videos_list', 'count=' . count($videos));

    echo json_encode([
        'ok'         => true,
        'videos'     => $videos,
        'total'      => $total,
        'page'       => $page,
        'per_page'   => $per_page,
        'total_pages'=> max(1, (int)ceil($total / $per_page)),
    ], JSON_UNESCAPED_UNICODE);
} catch (PDOException $e) {
    error_log('api/videos.php ott 연결 실패: ' . $e->getMessage());
    api_fail(502, 'ott db unavailable');
}
