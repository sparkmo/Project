<?php
/**
 * api/auth.php
 *
 * 씨네나잇(OTT) 웹서버 ↔ 인트라넷 서버 간 서버-서버 로그인 인증 API.
 *
 * 씨네나잇 웹서버는 이제 회원 DB(member_db)에 전혀 직접 접속하지 않습니다.
 * 로그인 시도가 들어오면 씨네나잇은 이 API로 username/password를 그대로
 * 전달하고, 인트라넷 서버가 member_db를 조회해 비밀번호를 검증한 뒤
 * 결과(성공 여부 + 세션에 필요한 최소 정보)만 돌려줍니다.
 *
 * 인증 방식은 api/members.php 와 동일하게 정적 마스터 토큰 하나입니다.
 * (이 프로젝트 전체에서 서버-서버 인증은 이 토큰 하나로 통일되어 있고,
 *  이 토큰의 취약점은 README-VULN-API.md 에서 다룬 것과 동일합니다.)
 */

require_once __DIR__ . '/../common.php';

header('Content-Type: application/json; charset=utf-8');

function api_fail(int $status, string $message): void {
    http_response_code($status);
    echo json_encode(['ok' => false, 'error' => $message], JSON_UNESCAPED_UNICODE);
    exit;
}

// ----- 토큰 인증 -----
$token = $_SERVER['HTTP_X_API_TOKEN'] ?? ($_POST['token'] ?? '');
if (!is_string($token) || $token === '' || $token !== INTRANET_API_MASTER_TOKEN) {
    log_action($pdo, null, 'api_auth_fail', 'ip=' . ($_SERVER['REMOTE_ADDR'] ?? '-'));
    api_fail(401, 'invalid token');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    api_fail(405, 'POST only');
}

$username = trim($_POST['username'] ?? '');
$password = (string)($_POST['password'] ?? '');

if ($username === '' || $password === '') {
    api_fail(400, 'username/password required');
}

try {
    $pdo_member = get_member_pdo();

    $stmt = $pdo_member->prepare(
        'SELECT id, username, password_hash, nickname, email, phone, grade
         FROM members WHERE username = ?'
    );
    $stmt->execute([$username]);
    $member = $stmt->fetch();

    if (!$member || !password_verify($password, $member['password_hash'])) {
        log_action($pdo, null, 'api_auth_login_fail', 'username=' . $username);
        api_fail(401, '아이디 또는 비밀번호가 올바르지 않습니다.');
    }

    // 최근 로그인 시각 갱신
    $pdo_member->prepare('UPDATE members SET last_login = NOW() WHERE id = ?')->execute([$member['id']]);

    log_action($pdo, null, 'api_auth_login_ok', 'username=' . $username);

    unset($member['password_hash']); // 세션에 넘길 필요 없는 필드는 응답에서 제외

    echo json_encode(['ok' => true, 'member' => $member], JSON_UNESCAPED_UNICODE);
} catch (PDOException $e) {
    error_log('api/auth.php member_db 연결 실패: ' . $e->getMessage());
    api_fail(502, 'member_db unavailable');
}
