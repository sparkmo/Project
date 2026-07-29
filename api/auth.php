<?php
/**
 * api/auth.php
 *
 * 씨네나잇(OTT) 웹서버 ↔ 인트라넷 서버 간 서버-서버 로그인 인증 API.
 * ott_db.member 테이블 기준 (login_id, password bcrypt).
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
    log_action($pdo, null, 'api_auth_fail', 'ip=' . ($_SERVER['REMOTE_ADDR'] ?? '-'));
    api_fail(401, 'invalid token');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    api_fail(405, 'POST only');
}

$login_id = trim($_POST['username'] ?? '');
$password = (string)($_POST['password'] ?? '');

if ($login_id === '' || $password === '') {
    api_fail(400, 'username/password required');
}

try {
    $pdo_member = get_member_pdo();

  $stmt = $pdo_member->prepare(
        "SELECT id FROM users WHERE AES_DECRYPT(email, 'b3bc88d7a82fc5843ded886d04c490fe9f044d5c396f0942feb8a38594ec36e7') = ?"
    );
    $stmt->execute([$email]);
    $member = $stmt->fetch();

    if (!$member || !password_verify($password, $member['password'])) {
        log_action($pdo, null, 'api_auth_login_fail', 'login_id=' . $login_id);
        api_fail(401, '아이디 또는 비밀번호가 올바르지 않습니다.');
    }

    log_action($pdo, null, 'api_auth_login_ok', 'login_id=' . $login_id);

    unset($member['password']); // 세션에 넘길 필요 없는 필드는 응답에서 제외

    // 씨네나잇(WebServer)이 예전 컬럼명(id/username)을 그대로 읽고 있으므로,
    // 응답 키는 WebServer 쪽 호환을 위해 유지합니다 (DB 컬럼명과는 별개).
    $response_member = [
        'id'         => $member['member_id'],
        'username'   => $member['login_id'],
        'nickname'   => $member['nickname'],
        'email'      => $member['email'],
        'role'       => $member['role'],        // 추가
        'created_at' => $member['created_at'],
    ];

    echo json_encode(['ok' => true, 'member' => $response_member], JSON_UNESCAPED_UNICODE);
} catch (PDOException $e) {
    error_log('api/auth.php ott 연결 실패: ' . $e->getMessage());
    api_fail(502, 'ott db unavailable');
}
