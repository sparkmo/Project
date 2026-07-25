<?php
/**
 * api/inquiry.php
 *
 * 씨네나잇(OTT) 웹서버 ↔ 인트라넷 서버 간 서버-서버 문의 등록/조회 대행 API.
 * ott_db.inquiry 테이블 기준: inquiry_id, member_id(FK), title, content, status, created_at
 *
 * 지원:
 *   - POST : 문의 등록 (username, title, content)
 *   - GET  : 본인 문의 목록 조회 (username)
 */

require_once __DIR__ . '/../common.php';

header('Content-Type: application/json; charset=utf-8');

function api_fail(int $status, string $message): void {
    http_response_code($status);
    echo json_encode(['ok' => false, 'error' => $message], JSON_UNESCAPED_UNICODE);
    exit;
}

$token = $_SERVER['HTTP_X_API_TOKEN'] ?? ($_POST['token'] ?? $_GET['token'] ?? '');
if (!is_string($token) || $token === '' || $token !== INTRANET_API_MASTER_TOKEN) {
    log_action($pdo, null, 'api_inquiry_auth_fail', 'ip=' . ($_SERVER['REMOTE_ADDR'] ?? '-'));
    api_fail(401, 'invalid token');
}

try {
    $pdo_member = get_member_pdo();

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $login_id = trim($_POST['username'] ?? '');
        $title    = trim($_POST['title'] ?? '');
        $content  = trim($_POST['content'] ?? '');

        if ($login_id === '' || $title === '' || $content === '') {
            api_fail(400, '아이디/제목/내용을 모두 입력해주세요.');
        }

        $stmt = $pdo_member->prepare('SELECT member_id FROM member WHERE login_id = ?');
        $stmt->execute([$login_id]);
        $member = $stmt->fetch();
        if (!$member) {
            api_fail(404, '존재하지 않는 회원입니다.');
        }

        $stmt = $pdo_member->prepare(
            "INSERT INTO inquiry (member_id, title, content, status)
             VALUES (?, ?, ?, '대기')"
        );
        $stmt->execute([$member['member_id'], $title, $content]);

        $new_id = (int)$pdo_member->lastInsertId();
        log_action($pdo, null, 'api_inquiry_create', 'login_id=' . $login_id . ' inquiry_id=' . $new_id);

        echo json_encode(['ok' => true, 'id' => $new_id], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // GET: 본인 문의 목록
    $login_id = trim($_GET['username'] ?? '');
    if ($login_id === '') {
        api_fail(400, 'username required');
    }

    $stmt = $pdo_member->prepare('SELECT member_id FROM member WHERE login_id = ?');
    $stmt->execute([$login_id]);
    $member = $stmt->fetch();
    if (!$member) {
        api_fail(404, '존재하지 않는 회원입니다.');
    }

    $stmt = $pdo_member->prepare(
        'SELECT inquiry_id, title, content, status, created_at
         FROM inquiry WHERE member_id = ? ORDER BY created_at DESC'
    );
    $stmt->execute([$member['member_id']]);
    $rows = $stmt->fetchAll();

    log_action($pdo, null, 'api_inquiry_list', 'login_id=' . $login_id . ' count=' . count($rows));

    echo json_encode(['ok' => true, 'inquiries' => $rows], JSON_UNESCAPED_UNICODE);
} catch (PDOException $e) {
    error_log('api/inquiry.php ott 연결 실패: ' . $e->getMessage());
    api_fail(502, 'ott db unavailable');
}
