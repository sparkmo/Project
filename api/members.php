<?php
/**
 * api/members.php
 *
 * 씨네나잇(OTT) 웹서버 ↔ 인트라넷 서버 간 서버-서버 연동 API.
 *
 * 용도: 씨네나잇 마이페이지에서 "가입 시 등록한 연락처/최근 로그인" 등을 보여줄 때,
 *       씨네나잇 웹서버는 member_db에 직접 접속할 수 없으므로(방화벽 차단),
 *       이 API를 통해 인트라넷 서버가 "대신" 조회해서 응답해준다.
 *
 * 인증 방식: 세션 로그인이 아니라 "마스터 토큰" 하나로만 인증한다.
 *           (서버-서버 통신이라 브라우저 세션 개념이 없기 때문)
 *
 * ⚠️ [VULN-API-1] 인증 수단이 정적 토큰(상수) 하나뿐입니다.
 *     - IP 화이트리스트 검사 없음 (원래는 씨네나잇 EC2의 사설 IP에서만 오는 게
 *       정상이지만, 이 코드는 요청이 "어디서" 왔는지는 전혀 보지 않고 토큰
 *       값만 봅니다.)
 *     - mTLS, HMAC 요청 서명, nonce/timestamp 재사용 방지 등이 전혀 없어서
 *       토큰 문자열 자체가 유출되면 그 즉시 누구나 이 API를 완전히 대체할 수
 *       있습니다.
 *
 * ⚠️ [VULN-API-2] username 파라미터가 없으면 "전체 회원"을 반환합니다.
 *     원래 마이페이지 연동은 로그인한 회원 1명의 정보만 필요한데, 배치
 *     동기화 등을 이유로 만들어둔 "username 없으면 전체 반환" 분기가
 *     그대로 남아있습니다. 요청 건수 제한(rate limit)도 없어서, 토큰만
 *     있으면 단 한 번의 요청으로 member_db 전체를 덤프할 수 있습니다.
 *
 * ⚠️ [VULN-API-3] 응답에 password_hash까지 그대로 포함됩니다.
 *     마이페이지 화면에 비밀번호 해시를 보여줄 이유가 없는데도, 조회 함수를
 *     그대로 재사용하면서 필드를 걸러내지 않았습니다.
 */

require_once __DIR__ . '/../common.php';

header('Content-Type: application/json; charset=utf-8');

function api_fail(int $status, string $message): void {
    http_response_code($status);
    echo json_encode(['ok' => false, 'error' => $message], JSON_UNESCAPED_UNICODE);
    exit;
}

// ----- 토큰 인증 -----
// 헤더(X-API-Token) 또는 쿼리스트링(token) 둘 다 허용 (연동 편의를 위해)
$token = $_SERVER['HTTP_X_API_TOKEN'] ?? ($_GET['token'] ?? '');

if (!is_string($token) || $token === '' || $token !== INTRANET_API_MASTER_TOKEN) {
    // 실패 로그는 남기지만, 실패 자체를 감지해 알림을 보내거나 IP를 차단하는
    // 로직은 없다 (탐지 체계 부재도 이 시나리오의 전제 중 하나).
    log_action($pdo, null, 'api_members_auth_fail', 'ip=' . ($_SERVER['REMOTE_ADDR'] ?? '-'));
    api_fail(401, 'invalid token');
}

$username = trim($_GET['username'] ?? '');

try {
    $pdo_member = get_member_pdo();

    if ($username !== '') {
        // 정상적인 의도: 마이페이지에서 로그인한 "본인" 1명만 조회
        $stmt = $pdo_member->prepare(
            'SELECT id, username, password_hash, nickname, email, phone, grade, reg_date, last_login
             FROM members WHERE username = ?'
        );
        $stmt->execute([$username]);
        $rows = $stmt->fetchAll();
    } else {
        // [VULN-API-2] username을 안 보내면 전체 반환 - 대량 조회 통로
        $stmt = $pdo_member->query(
            'SELECT id, username, password_hash, nickname, email, phone, grade, reg_date, last_login
             FROM members ORDER BY id DESC'
        );
        $rows = $stmt->fetchAll();
    }

    log_action(
        $pdo,
        null,
        'api_members_query',
        'username=' . ($username !== '' ? $username : '*ALL*') . ' count=' . count($rows)
    );

    echo json_encode(['ok' => true, 'count' => count($rows), 'members' => $rows], JSON_UNESCAPED_UNICODE);
} catch (PDOException $e) {
    error_log('api/members.php member_db 연결 실패: ' . $e->getMessage());
    api_fail(502, 'member_db unavailable');
}
