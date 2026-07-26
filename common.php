<?php
/**
 * common.php
 * DB 연결 (PDO / MariaDB) + 세션 시작 + 공통 설정
 * 모든 페이지 최상단에서 include 하여 사용합니다.
 */

// ----- DB 접속 정보 (실제 값은 .env 로 주입, 커밋하지 않음) -----
// 1) 인트라넷 자체 DB (직원 계정, 공지 등)
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_NAME', getenv('DB_NAME') ?: 'intranet_db');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');
define('DB_CHARSET', 'utf8mb4');

// 2) 씨네나잇 회원(고객) DB - 다른 서버(DB서버, 3308 포트)
//    이 접속 정보는 인트라넷 서버에만 존재하며, 씨네나잇 웹서버(common.php)에는
//    절대 배포하지 않습니다. (최소 권한 원칙 - 회원DB는 인트라넷 경유로만 접근)
define('MEMBER_DB_HOST', getenv('MEMBER_DB_HOST') ?: '172.16.0.10');
define('MEMBER_DB_PORT', (int)(getenv('MEMBER_DB_PORT') ?: 3308));
define('MEMBER_DB_NAME', getenv('MEMBER_DB_NAME') ?: 'ott'); // 실제 DB명은 member_db가 아니라 ott
define('MEMBER_DB_USER', getenv('MEMBER_DB_USER') ?: 'member_admin');
define('MEMBER_DB_PASS', getenv('MEMBER_DB_PASS') ?: '');

// 3) 씨네나잇 웹서버 ↔ 인트라넷 서버 간 서버-서버 API 인증용 마스터 토큰
//    (마이페이지 연동: 씨네나잇이 회원 정보를 화면에 보여줘야 할 때 이 토큰을 실어
//    /api/members.php 를 호출하면, 인트라넷 서버가 대신 member_db를 조회해서 응답한다)
//    ⚠️ 이 값은 씨네나잇 웹서버의 config/intranet.php 에도 동일하게 존재해야 합니다.
//       두 서버 모두 이 값을 평문 상수(코드)로 갖고 있으면, 어느 한쪽 서버든 파일이
//       노출되는 순간 토큰 하나로 member_db 전체 조회가 가능해집니다 — 이 프로젝트가
//       다루는 취약점(README-VULN 계열)의 전제이기도 하니, 이 토큰 자체를 안전하게
//       관리하는 것(=코드에 안 남기고 .env로 주입)은 그 취약점과 별개로 지켜야 합니다.
//
//    ⚠️ [VULN-HIGH-4] 소스코드에는 평문 값이 없지만(순수 getenv()), 공격
//       벡터가 웹쉘(RCE)이므로 getenv()/phpinfo()/system('env') 등으로
//       프로세스가 아는 모든 환경변수를 그대로 조회할 수 있어 실질적인
//       방어가 되지 못한다. 문제의 본질은 "웹쉘(RCE) 자체를 막지 못했다"는
//       것이지, 토큰이 코드에 있느냐 env에 있느냐가 아니다.
define('INTRANET_API_MASTER_TOKEN', getenv('INTRANET_API_MASTER_TOKEN') ?: '');

// ----- 사내 메일 서버 연동 설정 -----
// 별도의 (윈도우) 메일 서버에 IMAP으로 접속해서 직원 메일함을 읽어오는 기능용.
// 자세한 내용/보안 참고는 config/mail.php 및 README.md 참고.
require_once __DIR__ . '/config/mail.php';

// DB 서버가 아직 안 붙어있어도(연결 실패해도) 로그인 화면 자체는 뜨도록,
// 여기서 die() 시키지 않고 $pdo = null 로 두고 넘어갑니다.
// (로그인 자체는 attempt_login()에서 $pdo가 null이면 실패 처리하도록 별도 방어)
try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
        PDO::ATTR_TIMEOUT            => 3, // DB가 안 떠 있을 때 페이지가 오래 멈춰있지 않도록
    ]);
} catch (PDOException $e) {
    error_log('DB 연결 실패: ' . $e->getMessage());
    $pdo = null;
}

/**
 * 회원(고객) DB 연결을 필요할 때만 지연 생성해서 반환.
 * admin/members.php 처럼 실제로 회원 정보 조회가 필요한 페이지에서만 호출.
 */
function get_member_pdo(): PDO {
    static $pdo_member = null;
    if ($pdo_member === null) {
        $dsn = "mysql:host=" . MEMBER_DB_HOST . ";port=" . MEMBER_DB_PORT
             . ";dbname=" . MEMBER_DB_NAME . ";charset=" . DB_CHARSET;
        $pdo_member = new PDO($dsn, MEMBER_DB_USER, MEMBER_DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    }
    return $pdo_member;
}

// 세션 시작 (다른 include 보다 먼저 실행되어야 함)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

date_default_timezone_set('Asia/Seoul');

// ----- 사이트 공통 설정 -----
define('SITE_NAME', '사내 인트라넷');

// 좌측 사이드바 메뉴 구성 (label, path, icon 텍스트)
define('SIDEBAR_MENU', [
    ['label' => '대시보드',   'path' => '/index.php',        'key' => 'dashboard'],
    ['label' => '공지사항',   'path' => '/notice/list.php',  'key' => 'notice'],
    ['label' => '사내 메일',  'path' => '/mail/inbox.php',   'key' => 'mail'],
    ['label' => '관리자페이지', 'path' => '/admin/users.php', 'key' => 'admin'],
    ['label' => '회원 관리(씨네나잇)', 'path' => '/admin/members.php', 'key' => 'admin_members'],
    ['label' => '영상 관리(씨네나잇)', 'path' => '/admin/videos.php', 'key' => 'admin_videos'],
]);

/**
 * mbstring 확장이 없어도 동작하는 UTF-8 안전 문자열 자르기
 */
function u8_truncate($str, $len, $trim = '...') {
    $str = (string)$str;
    $chars = preg_split('//u', $str, -1, PREG_SPLIT_NO_EMPTY);
    if ($chars === false) {
        return $str;
    }
    if (count($chars) <= $len) {
        return $str;
    }
    return implode('', array_slice($chars, 0, $len)) . $trim;
}

/**
 * 상대/절대 시간 표시 (예: "3분 전", "2024-01-01")
 */
function format_datetime($datetime) {
    $ts = strtotime($datetime);
    $diff = time() - $ts;
    if ($diff < 60)   return '방금 전';
    if ($diff < 3600) return floor($diff / 60) . '분 전';
    if ($diff < 86400) return floor($diff / 3600) . '시간 전';
    return date('Y-m-d H:i', $ts);
}

/**
 * 관리자/시스템 행위 로그 기록.
 * DBServer2의 03-adminserver-app-support.sql이 미리 만들어둔
 * intranet_db.access_logs 테이블에 기록한다 (테이블은 이미 존재 - 새로 안 만듦).
 *
 * @param PDO|null   $pdo        intranet_db 연결 (common.php 상단의 $pdo)
 * @param int|null   $actor_id   행위자(직원) ID. employee.employee_id를 FK로 참조하므로
 *                                존재하지 않는 employee_id면 NULL로 넘겨야 함.
 * @param string     $action     행위 종류 (예: 'api_auth_login_ok', 'admin_delete_video')
 * @param string     $detail     부가 정보 (예: 'login_id=tester') - target 컬럼에 저장됨
 */
function log_action(?PDO $pdo, ?int $actor_id, string $action, string $detail = ''): void {
    if ($pdo === null) {
        error_log("[log_action skipped: no DB] action={$action} detail={$detail}");
        return;
    }

    try {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';

        $stmt = $pdo->prepare(
            'INSERT INTO access_logs (user_id, ip, action, target) VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([$actor_id, $ip, $action, $detail !== '' ? $detail : '-']);
    } catch (PDOException $e) {
        // FK 제약(employee_id가 실제로 없는 값) 등으로 실패해도 페이지 자체는 죽으면 안 됨
        error_log('log_action 기록 실패: ' . $e->getMessage());
    }
}


