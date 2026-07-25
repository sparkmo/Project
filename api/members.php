<?php
/**
 * api/members.php
 *
 * 씨네나잇(OTT) 마이페이지 조회 대행 API. ott_db.member 테이블 기준.
 *
 * ⚠️ [VULN-API-1]~[VULN-API-3] 취약점 성격은 이전과 동일하게 유지합니다
 *    (정적 토큰 인증만, username 없으면 전체 반환, 응답에 해시 포함 등).
 *    스키마만 새 설계(member/login_id)에 맞춥니다.
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
    log_action($pdo, null, 'api_members_auth_fail', 'ip=' . ($_SERVER['REMOTE_ADDR'] ?? '-'));
    api_fail(401, 'invalid token');
}

$login_id = trim($_GET['username'] ?? '');

try {
    $pdo_member = get_member_pdo();

    if ($login_id !== '') {
        $stmt = $pdo_member->prepare(
            'SELECT member_id, login_id, password, nickname, email, created_at
             FROM member WHERE login_id = ?'
        );
        $stmt->execute([$login_id]);
        $rows = $stmt->fetchAll();
    } else {
        // [VULN-API-2] username을 안 보내면 전체 반환 - 대량 조회 통로 (그대로 유지)
        $stmt = $pdo_member->query(
            'SELECT member_id, login_id, password, nickname, email, created_at
             FROM member ORDER BY member_id DESC'
        );
        $rows = $stmt->fetchAll();
    }

    log_action(
        $pdo,
        null,
        'api_members_query',
        'login_id=' . ($login_id !== '' ? $login_id : '*ALL*') . ' count=' . count($rows)
    );

    echo json_encode(['ok' => true, 'count' => count($rows), 'members' => $rows], JSON_UNESCAPED_UNICODE);
} catch (PDOException $e) {
    error_log('api/members.php ott 연결 실패: ' . $e->getMessage());
    api_fail(502, 'ott db unavailable');
}
