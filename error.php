<?php
/**
 * error.php
 * 간단한 알림 후 이동/뒤로가기 처리 헬퍼
 */

// 알림 후 뒤로가기
function alert_back($msg) {
    echo "<script>alert('" . addslashes($msg) . "'); history.back();</script>";
    exit;
}

// 알림 후 특정 페이지로 이동
function alert_redirect($msg, $url) {
    echo "<script>alert('" . addslashes($msg) . "'); location.href='" . $url . "';</script>";
    exit;
}
