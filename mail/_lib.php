<?php
/**
 * mail/_lib.php
 * 사내 메일(IMAP) 기능에서 공용으로 쓰는 헬퍼 함수.
 * mail/*.php 에서 require_once 로 불러와서 사용합니다.
 */

/** 메일함 로그인 여부 (인트라넷 로그인과는 별개의 세션 값) */
function is_mail_logged_in(): bool {
    return isset($_SESSION['mail_email'], $_SESSION['mail_password']);
}

/** 메일함 로그인이 안 되어 있으면 로그인 페이지로 리다이렉트 */
function require_mail_login(): void {
    if (!is_mail_logged_in()) {
        header('Location: /mail/login.php');
        exit;
    }
}

/** 메일함 세션 정보 제거 */
function mail_session_clear(): void {
    unset($_SESSION['mail_email'], $_SESSION['mail_password']);
}

/**
 * 세션에 저장된 자격증명으로 IMAP 연결을 연다.
 * 실패 시 false 를 반환 (imap_last_error() 로 상세 사유 확인 가능)
 *
 * @return resource|\IMAP\Connection|false
 */
function mail_connect(string $folder = 'INBOX') {
    if (!is_mail_logged_in()) {
        return false;
    }
    if (!function_exists('imap_open')) {
        return false;
    }
    $mailbox = mail_build_mailbox_spec($folder);
    // @ 로 경고 억제 - 실패 여부는 반환값과 imap_last_error() 로 판단
    return @imap_open($mailbox, $_SESSION['mail_email'], $_SESSION['mail_password']);
}
