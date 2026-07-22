<?php
/**
 * auth.php
 * 로그인/권한 체크 헬퍼 함수 모음
 * common.php 이후에 include 해야 합니다.
 */

/** 현재 로그인 여부 */
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

/** 로그인 상태가 아니면 로그인 페이지로 리다이렉트 */
function require_login() {
    if (!is_logged_in()) {
        header('Location: /login.php');
        exit;
    }
}

/** 관리자 권한이 아니면 접근 차단 */
function require_admin() {
    require_login();
    if (($_SESSION['grade'] ?? '') !== 'admin') {
        header('Location: /error.php?msg=' . urlencode('접근 권한이 없습니다.'));
        exit;
    }
}

/**
 * 현재 로그인한 사용자 정보 (DB 재조회)
 * 실제 스키마는 intranet_db.employee (employee_id/login_id/department/role) 이지만,
 * 나머지 코드(header.php, index.php, notice/*.php 등)가 기존 필드명
 * (id/username/dept/grade)을 그대로 쓰고 있어서 AS로 별칭만 맞춰줍니다.
 * role은 'Admin'/'Manager'/'User'(대문자)라서 LOWER()로 소문자화 —
 * 기존 코드의 grade === 'admin' 비교와 호환하기 위함 (Manager는 '일반' 취급됨).
 */
function current_user(?PDO $pdo) {
    if (!is_logged_in() || $pdo === null) return null;
    $stmt = $pdo->prepare(
        'SELECT employee_id AS id, login_id AS username, name, department AS dept,
                LOWER(role) AS grade, email, created_at
         FROM employee WHERE employee_id = ?'
    );
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch();
}

/**
 * 로그인 시도
 * 성공 시 세션에 사용자 정보를 저장하고 true 반환
 *
 * ⚠️ [VULN] employee.password는 bcrypt 해시가 아니라 평문으로 저장되어 있습니다
 *    (실제 덤프 확인: 'admin01' 계정 비밀번호가 '1234' 그대로). 원래 코드는
 *    password_verify()를 썼지만 실제 스키마엔 해시가 없으므로 hash_equals()로
 *    평문 비교만 합니다. 운영 전환 전 반드시 password_hash()로 재해싱하고
 *    로그인 로직도 password_verify()로 되돌려야 합니다.
 */
function attempt_login(?PDO $pdo, string $username, string $password) {
    if ($pdo === null) return false; // DB 서버 미연결 상태 — 로그인만 실패 처리, 페이지는 계속 뜨게
    $stmt = $pdo->prepare(
        'SELECT employee_id AS id, login_id AS username, password, name, LOWER(role) AS grade
         FROM employee WHERE login_id = ?'
    );
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if (!$user || !hash_equals((string)$user['password'], $password)) {
        return false;
    }

    // 세션 고정 공격 방지
    session_regenerate_id(true);

    $_SESSION['user_id']  = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['name']     = $user['name'];
    $_SESSION['grade']    = $user['grade'];

    return true;
}

function logout() {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
}

/** 활동 로그 기록 */
function log_action(?PDO $pdo, ?int $user_id, string $action, string $target = '-') {
    if ($pdo === null) return; // DB 없으면 로그도 그냥 건너뜀
    $stmt = $pdo->prepare('INSERT INTO access_logs (user_id, ip, action, target) VALUES (?, ?, ?, ?)');
    $stmt->execute([$user_id, $_SERVER['REMOTE_ADDR'] ?? '-', $action, $target]);
}
