<?php
/**
 * auth.php
 * 로그인 상태 및 권한 체크용 헬퍼 함수
 * common.php 다음에 include 해서 사용합니다 (세션이 필요하므로).
 */

// 로그인 여부
function is_login() {
    return isset($_SESSION['user_id']);
}

// 관리자 여부 (DB role 컬럼은 'ADMIN'/'USER' 대문자로 저장되어 있음)
function is_admin() {
    return is_login() && strtoupper($_SESSION['grade'] ?? '') === 'ADMIN';
}

// 현재 로그인한 사용자 정보 (세션에 있는 값만 간단히 반환)
function current_user() {
    if (!is_login()) return null;
    return [
        'id'       => $_SESSION['user_id'],
        'username' => $_SESSION['username'] ?? '',
        'nickname' => $_SESSION['nickname'] ?? '',
        'grade'    => $_SESSION['grade'] ?? 'member',
    ];
}

// 로그인이 필요한 페이지 상단에서 호출
function require_login() {
    if (!is_login()) {
        echo "<script>alert('로그인이 필요합니다.'); location.href='login.php';</script>";
        exit;
    }
}

// 관리자만 접근 가능한 페이지 상단에서 호출
function require_admin() {
    if (!is_admin()) {
        echo "<script>alert('관리자만 접근할 수 있습니다.'); location.href='index.php';</script>";
        exit;
    }
}

