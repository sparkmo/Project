<?php
/**
 * api/join.php
 *
 * 씨네나잇(OTT) 웹서버 ↔ 인트라넷 서버 간 서버-서버 회원가입 대행 API.
 *
 * ott_db 최종 스키마 (member 테이블) 기준:
 *   member_id, login_id, password(bcrypt), nickname, email, created_at
 * grade/phone 컬럼은 이 테이블에 없습니다.
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
    log_action($pdo, null, 'api_join_auth_fail', 'ip=' . ($_SERVER['REMOTE_ADDR'] ?? '-'));
    api_fail(401, 'invalid token');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    api_fail(405, 'POST only');
}

$login_id = trim($_POST['username'] ?? '');   // 씨네나잇 폼 필드명(username)은 그대로 받고, DB 컬럼명만 login_id로 매핑
$password = (string)($_POST['password'] ?? '');
$nickname = trim($_POST['nickname'] ?? '');
$email    = trim($_POST['email'] ?? '');

if ($login_id === '' || $password === '' || $nickname === '' || $email === '') {
    api_fail(400, '모든 항목을 입력해주세요.');
}
if (!preg_match('/^[a-zA-Z0-9_]{4,20}$/', $login_id)) {
    api_fail(400, '아이디는 영문/숫자/언더바 4~20자로 입력해주세요.');
}
if (strlen($password) < 6) {
    api_fail(400, '비밀번호는 6자 이상 입력해주세요.');
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    api_fail(400, '올바른 이메일 형식이 아닙니다.');
}

try {
    $pdo_member = get_member_pdo();

    $stmt = $pdo_member->prepare('SELECT member_id FROM member WHERE login_id = ?');
    $stmt->execute([$login_id]);
    if ($stmt->fetch()) {
        api_fail(409, '이미 사용 중인 아이디입니다.');
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $pdo_member->prepare(
        'INSERT INTO member (login_id, password, nickname, email)
         VALUES (?, ?, ?, ?)'
    );
    $stmt->execute([$login_id, $hash, $nickname, $email]);

    $new_id = (int)$pdo_member->lastInsertId();

    log_action($pdo, null, 'api_join', 'login_id=' . $login_id . ' new_id=' . $new_id);

    echo json_encode(['ok' => true, 'id' => $new_id], JSON_UNESCAPED_UNICODE);
} catch (PDOException $e) {
    error_log('api/join.php ott 연결 실패: ' . $e->getMessage());
    api_fail(502, 'ott db unavailable');
}
