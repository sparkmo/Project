<?php
/**
 * api/join.php
 *
 * 씨네나잇(OTT) 웹서버 ↔ 인트라넷 서버 간 서버-서버 회원가입 대행 API.
 *
 * 씨네나잇은 member_db에 직접 INSERT할 수 없으므로, 가입 폼 값을 그대로
 * 이 API에 전달하면 인트라넷 서버가 검증 후 member_db.members에 새 행을
 * 추가합니다. (비밀번호 해시는 반드시 인트라넷 서버에서 생성합니다 - 평문
 * 비밀번호가 이 API 호출 구간 밖으로 나가지 않도록.)
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

$username = trim($_POST['username'] ?? '');
$password = (string)($_POST['password'] ?? '');
$nickname = trim($_POST['nickname'] ?? '');
$email    = trim($_POST['email'] ?? '');
$phone    = trim($_POST['phone'] ?? '');

if ($username === '' || $password === '' || $nickname === '' || $email === '') {
    api_fail(400, '모든 항목을 입력해주세요.');
}
if (!preg_match('/^[a-zA-Z0-9_]{4,20}$/', $username)) {
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

    $stmt = $pdo_member->prepare('SELECT id FROM members WHERE username = ?');
    $stmt->execute([$username]);
    if ($stmt->fetch()) {
        api_fail(409, '이미 사용 중인 아이디입니다.');
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $pdo_member->prepare(
        'INSERT INTO members (username, password_hash, nickname, email, phone, grade)
         VALUES (?, ?, ?, ?, ?, ?)'
    );
    $stmt->execute([$username, $hash, $nickname, $email, $phone !== '' ? $phone : null, 'member']);

    $new_id = (int)$pdo_member->lastInsertId();

    log_action($pdo, null, 'api_join', 'username=' . $username . ' new_id=' . $new_id);

    echo json_encode(['ok' => true, 'id' => $new_id], JSON_UNESCAPED_UNICODE);
} catch (PDOException $e) {
    error_log('api/join.php member_db 연결 실패: ' . $e->getMessage());
    api_fail(502, 'member_db unavailable');
}
