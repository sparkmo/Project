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

/** 현재 로그인한 사용자 정보 (DB 재조회) */
function current_user(PDO $pdo) {
    if (!is_logged_in()) return null;
    $stmt = $pdo->prepare('SELECT id, username, name, dept, grade, email FROM users WHERE id = ?');
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch();
}

/**
 * 로그인 시도
 * 성공 시 세션에 사용자 정보를 저장하고 true 반환
 */
function attempt_login(PDO $pdo, string $username, string $password) {
    $stmt = $pdo->prepare('SELECT id, username, password_hash, name, grade FROM users WHERE username = ?');
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password_hash'])) {
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
function log_action(PDO $pdo, ?int $user_id, string $action, string $target = '-') {
    $stmt = $pdo->prepare('INSERT INTO access_logs (user_id, ip, action, target) VALUES (?, ?, ?, ?)');
    $stmt->execute([$user_id, $_SERVER['REMOTE_ADDR'] ?? '-', $action, $target]);
}
