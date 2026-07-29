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
   // 바꿀 것 (users 테이블, email로 찾고 AES_DECRYPT로 비밀번호 비교)
    $key = 'b3bc88d7a82fc5843ded886d04c490fe9f044d5c396f0942feb8a38594ec36e7';
    
    // login_id 대신 email로 찾음 (users 테이블 구조)
    $stmt = $pdo_member->prepare(
        "SELECT id, role, created_at,
                AES_DECRYPT(email,    '$key') AS email,
                AES_DECRYPT(name,     '$key') AS name,
                AES_DECRYPT(password, '$key') AS password_plain
         FROM users
         WHERE AES_DECRYPT(email, '$key') = ?"
    );
    $stmt->execute([$login_id]); // 씨네나잇은 username 필드로 받지만 실제론 email로 매핑
    
    $member = $stmt->fetch();
    
    // bcrypt 대신 평문 비교 (AES_DECRYPT로 꺼낸 값이 원래 비밀번호)
    if (!$member || !hash_equals((string)$member['password_plain'], $password)) { ... }
        log_action($pdo, null, 'api_auth_login_ok', 'login_id=' . $login_id);

    unset($member['password']); // 세션에 넘길 필요 없는 필드는 응답에서 제외

    // 씨네나잇(WebServer)이 예전 컬럼명(id/username)을 그대로 읽고 있으므로,
    // 응답 키는 WebServer 쪽 호환을 위해 유지합니다 (DB 컬럼명과는 별개).
    $response_member = [
        'id'       => $member['id'],
        'username' => $member['email'],  // login_id 없으니 email로 대체
        'nickname' => $member['name'],   // nickname → name
        'email'    => $member['email'],
        'role'     => $member['role'],
        'created_at' => $member['created_at'],
    ];

    echo json_encode(['ok' => true, 'member' => $response_member], JSON_UNESCAPED_UNICODE);
} catch (PDOException $e) {
    error_log('api/auth.php ott 연결 실패: ' . $e->getMessage());
    api_fail(502, 'ott db unavailable');
}
