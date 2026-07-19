<?php
/**
 * api/videos.php
 *
 * 씨네나잇(OTT) 웹서버 ↔ 인트라넷 서버 간 서버-서버 영상 조회 대행 API.
 *
 * 씨네나잇은 videos 테이블에 직접 접근할 수 없으므로, 메인/목록/상세/마이페이지
 * 화면에 필요한 조회는 전부 이 API를 거칩니다.
 *
 * ⚠️ 이 API는 "조회"만 제공합니다. 영상 수정/삭제는 이 API에 존재하지 않으며,
 *    인트라넷 관리자 페이지(admin/videos.php, admin/video_edit.php)에서
 *    member_db에 직접 접근해서만 처리합니다 - 씨네나잇 웹서버가 뚫리더라도
 *    (토큰이 유출되더라도) 영상을 수정/삭제할 방법 자체가 없도록 하는 것이
 *    이 구조의 핵심 의도입니다.
 *
 * 지원 파라미터 (전부 GET):
 *   - id              : 단일 영상 상세 조회. view=1 이면 조회수 +1 후 반환.
 *   - category        : 카테고리 필터
 *   - q               : 제목 검색 키워드
 *   - writer_username : 특정 회원이 올린 영상만 (마이페이지용)
 *   - page, per_page  : 페이지네이션 (기본 1 / 12, 최대 per_page 50)
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
        // ----- 단일 영상 상세 조회 -----
        $stmt = $pdo_member->prepare(
            'SELECT v.*, m.nickname, m.username AS writer_username
             FROM videos v JOIN members m ON m.id = v.writer_id
             WHERE v.id = ?'
        );
        $stmt->execute([$id]);
        $video = $stmt->fetch();

        if (!$video) {
            api_fail(404, '존재하지 않는 영상입니다.');
        }

        if (($_GET['view'] ?? '') === '1') {
            $pdo_member->prepare('UPDATE videos SET view_count = view_count + 1 WHERE id = ?')->execute([$id]);
            $video['view_count']++;
        }

        log_action($pdo, null, 'api_videos_view', 'id=' . $id);
        echo json_encode(['ok' => true, 'video' => $video], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // ----- 목록 조회 -----
    $category = trim($_GET['category'] ?? '');
    $keyword  = trim($_GET['q'] ?? '');
    $writer_username = trim($_GET['writer_username'] ?? '');
    $page     = max(1, (int)($_GET['page'] ?? 1));
    $per_page = min(50, max(1, (int)($_GET['per_page'] ?? 12)));
    $offset   = ($page - 1) * $per_page;

    $where  = [];
    $params = [];

    if ($category !== '') {
        $where[] = 'v.category = ?';
        $params[] = $category;
    }
    if ($keyword !== '') {
        $where[] = 'v.title LIKE ?';
        $params[] = '%' . $keyword . '%';
    }
    if ($writer_username !== '') {
        $where[] = 'm.username = ?';
        $params[] = $writer_username;
    }
    $where_sql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

    $count_stmt = $pdo_member->prepare("SELECT COUNT(*) FROM videos v JOIN members m ON m.id = v.writer_id $where_sql");
    $count_stmt->execute($params);
    $total = (int)$count_stmt->fetchColumn();

    $sql = "SELECT v.*, m.nickname, m.username AS writer_username
            FROM videos v JOIN members m ON m.id = v.writer_id
            $where_sql
            ORDER BY v.reg_date DESC
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
    error_log('api/videos.php member_db 연결 실패: ' . $e->getMessage());
    api_fail(502, 'member_db unavailable');
}
