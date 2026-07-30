<?php
/**
 * api/members.php
 * ott_db.users 테이블 기준
 * MariaDB AES_ENCRYPT() / AES_DECRYPT()로 email/phone/name 복호화
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
 
$user_id = (int)($_GET['id'] ?? 0);
$email   = trim($_GET['username'] ?? ''); // 씨네나잇 세션의 username은 실제로는 email 값
 
try {
    $pdo_member = get_member_pdo();
 
    $select = "SELECT id, role, created_at,
        CONVERT(AES_DECRYPT(email, UNHEX('" . AES_KEY . "')) USING utf8mb4) AS email,
        CONVERT(AES_DECRYPT(phone, UNHEX('" . AES_KEY . "')) USING utf8mb4) AS phone,
        CONVERT(AES_DECRYPT(name,  UNHEX('" . AES_KEY . "')) USING utf8mb4) AS name
    FROM users";
 
    if ($user_id > 0) {
        $stmt = $pdo_member->prepare($select . ' WHERE id = ?');
        $stmt->execute([$user_id]);
        $rows = $stmt->fetchAll();
    } elseif ($email !== '') {
        $stmt = $pdo_member->prepare(
            $select . " WHERE CONVERT(AES_DECRYPT(email, UNHEX('" . AES_KEY . "')) USING utf8mb4) = ?"
        );
        $stmt->execute([$email]);
        $rows = $stmt->fetchAll();
    } else {
        // [VULN-API-2] id/username 둘 다 없으면 전체 반환 (그대로 유지)
        $stmt = $pdo_member->prepare($select . ' ORDER BY id DESC');
        $stmt->execute([]);
        $rows = $stmt->fetchAll();
    }
 
    log_action(
        $pdo,
        null,
        'api_members_query',
        'id=' . ($user_id > 0 ? $user_id : ($email !== '' ? $email : '*ALL*')) . ' count=' . count($rows)
    );
 
    echo json_encode(['ok' => true, 'count' => count($rows), 'members' => $rows], JSON_UNESCAPED_UNICODE);
} catch (PDOException $e) {
    error_log('api/members.php ott 연결 실패: ' . $e->getMessage());
    api_fail(502, 'ott db unavailable');
}
