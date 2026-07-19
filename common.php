<?php
/**
 * common.php
 *
 * ⚠️ 이 서버(씨네나잇 웹서버)는 더 이상 어떤 DB에도 직접 접속하지 않습니다.
 *    회원(로그인/가입/마이페이지)과 영상(목록/상세/등록) 데이터는 전부
 *    인트라넷 서버의 API(config/intranet.php 참고)를 통해서만 주고받습니다.
 *    영상 수정/삭제 API는 아예 존재하지 않으며, 인트라넷 관리자 페이지에서만
 *    처리됩니다 - 이 웹서버가 뚫려서 인트라넷 토큰이 유출되더라도 회원가입/
 *    조회/영상 등록 이상은 할 수 없도록 하는 것이 이 구조의 의도입니다.
 *
 * 이 파일은 세션 시작 + 공통 상수/헬퍼 함수 + 인트라넷 API 호출 헬퍼를 담당합니다.
 */

// 인트라넷 연동 설정 (API 엔드포인트 + 마스터 토큰)
require_once __DIR__ . '/config/intranet.php';

// 세션 시작 (다른 include 보다 먼저 실행되어야 함)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 시간대
date_default_timezone_set('Asia/Seoul');

// ----- 사이트 공통 설정 -----
define('SITE_NAME', '씨네나잇');
define('SITE_TAGLINE', '자정 이후에 펼쳐지는 이야기들');

// 카테고리 목록 (index.php, list.php, write.php 등에서 공용으로 사용)
define('CATEGORIES', [
    'general' => '전체',
    'movie'   => '영화',
    'drama'   => '드라마',
    'ent'     => '예능',
    'anime'   => '애니메이션',
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
 * mb_substr($str, 0, 1) 대체용 - 첫 글자(한글 포함) 한 글자만 안전하게 추출
 */
function u8_first_char($str) {
    $str = (string)$str;
    if (preg_match('/^./u', $str, $m)) {
        return $m[0];
    }
    return $str;
}

// =====================================================================
// 인트라넷 서버 API 호출 헬퍼
// =====================================================================

/**
 * 인트라넷 API에 GET 요청. 항상 X-API-Token 헤더를 실어 보낸다.
 * 반환값은 항상 배열 (['ok'=>bool, ...]) - 통신 실패 시에도 배열로 통일.
 */
function intranet_api_get(string $url, array $params = []): array {
    $full = $url . (strpos($url, '?') === false ? '?' : '&') . http_build_query($params);

    $ch = curl_init($full);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 5,
        CURLOPT_HTTPHEADER     => ['X-API-Token: ' . INTRANET_API_TOKEN],
    ]);
    $response = curl_exec($ch);
    curl_close($ch);

    if ($response === false) {
        return ['ok' => false, 'error' => '인트라넷 서버에 연결할 수 없습니다.'];
    }
    $data = json_decode($response, true);
    if (!is_array($data)) {
        return ['ok' => false, 'error' => '인트라넷 응답을 해석할 수 없습니다.'];
    }
    return $data;
}

/**
 * 인트라넷 API에 POST 요청 (로그인/가입/영상등록처럼 값을 전송해야 하는 경우).
 */
function intranet_api_post(string $url, array $fields): array {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 5,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => http_build_query($fields),
        CURLOPT_HTTPHEADER     => [
            'X-API-Token: ' . INTRANET_API_TOKEN,
            'Content-Type: application/x-www-form-urlencoded',
        ],
    ]);
    $response = curl_exec($ch);
    curl_close($ch);

    if ($response === false) {
        return ['ok' => false, 'error' => '인트라넷 서버에 연결할 수 없습니다.'];
    }
    $data = json_decode($response, true);
    if (!is_array($data)) {
        return ['ok' => false, 'error' => '인트라넷 응답을 해석할 수 없습니다.'];
    }
    return $data;
}

// ----- 직접 업로드(file 타입) 영상 관련 설정 -----
// (실제 영상 파일은 지금도 이 웹서버의 uploads/ 폴더에 저장됩니다.
//  member_db에는 메타데이터만 등록되며, 그건 api/videos_upload.php가 담당합니다.)
define('UPLOAD_DIR', __DIR__ . '/uploads/');
define('UPLOAD_MAX_SIZE', 500 * 1024 * 1024); // 500MB
define('ALLOWED_VIDEO_EXT', ['mp4', 'webm', 'ogg', 'ogv']);
define('ALLOWED_VIDEO_MIME', ['video/mp4', 'video/webm', 'video/ogg']);

/**
 * 유튜브 URL 전체를 입력해도 영상 ID(11자)만 추출
 */
function extract_youtube_id($input) {
    $input = trim($input);
    if (preg_match('/^[a-zA-Z0-9_-]{11}$/', $input)) {
        return $input;
    }
    if (preg_match('~(?:youtu\.be/|youtube\.com/(?:watch\?v=|embed/|shorts/))([a-zA-Z0-9_-]{11})~', $input, $m)) {
        return $m[1];
    }
    return $input;
}

/**
 * 업로드된 영상 파일을 검증 후 uploads/ 에 저장
 * $_FILES['video_file'] 배열을 그대로 넘기면 됨
 * 반환: ['ok' => bool, 'filename' => string, 'error' => string]
 */
// [VULN-HIGH] 위험 확장자 "블랙리스트" — 화이트리스트가 아니라 "이것만 막으면 된다"는
// 발상 자체가 이 레벨의 핵심 결함이다. 목록에 없는 위험한 확장자(.phtml, .pht 등)나
// 대소문자를 바꾼 변형은 아래 in_array 비교(대소문자 구분)를 그대로 통과한다.
define('BLOCKED_EXT', ['php', 'php3', 'php4', 'php5', 'phar', 'cgi', 'pl', 'asp', 'aspx', 'jsp', 'exe', 'sh']);

function save_uploaded_video($file) {
    if (!is_array($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return ['ok' => false, 'error' => '영상 파일을 선택해주세요.'];
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['ok' => false, 'error' => '업로드 중 오류가 발생했습니다. (에러코드: ' . $file['error'] . ')'];
    }
    if ($file['size'] <= 0 || $file['size'] > UPLOAD_MAX_SIZE) {
        return ['ok' => false, 'error' => '파일 용량은 ' . (int)(UPLOAD_MAX_SIZE / 1024 / 1024) . 'MB를 넘을 수 없습니다.'];
    }

    // [VULN-HIGH-1] 화이트리스트(ALLOWED_VIDEO_EXT) 대신 블랙리스트(BLOCKED_EXT) 사용
    // ------------------------------------------------------------------
    // "위험해 보이는 것만 막자"는 방식으로 되어 있는데, 이 비교는 대소문자를
    // 구분(strict, case-sensitive)한다. 즉 BLOCKED_EXT 목록에 정확히 소문자로
    // 없는 확장자는 전부 통과한다.
    $ext_raw = pathinfo($file['name'], PATHINFO_EXTENSION); // 소문자 변환을 하지 않음
    if (in_array($ext_raw, BLOCKED_EXT, true)) {
        return ['ok' => false, 'error' => '허용되지 않는 파일 형식입니다.'];
    }

    // [VULN-HIGH-2] 파일 내용(매직 바이트) 검사 — 하지만 "포함 여부"만 확인
    // ------------------------------------------------------------------
    // strpos()로 "파일 앞부분 어딘가에 시그니처 문자열이 포함되어 있는가"만 볼 뿐,
    // 그 시그니처가 "파일의 맨 앞(오프셋 0)"에 있어야 한다는 조건은 검사하지 않는다.
    $head = file_get_contents($file['tmp_name'], false, null, 0, 4096);
    $known_signatures = ["ftyp", "\x1A\x45\xDF\xA3", "OggS"]; // mp4/webm/ogg 계열 시그니처 일부
    $looks_like_video = false;
    foreach ($known_signatures as $sig) {
        if ($head !== false && strpos($head, $sig) !== false) {
            $looks_like_video = true;
            break;
        }
    }
    if (!$looks_like_video) {
        return ['ok' => false, 'error' => '파일 내용이 올바른 영상 형식이 아닙니다.'];
    }

    if (!is_dir(UPLOAD_DIR)) {
        mkdir(UPLOAD_DIR, 0755, true);
    }

    // [VULN-HIGH-3] 원본 파일명을 (확장자 포함) 그대로 사용
    // ------------------------------------------------------------------
    // 원본 파일명 + 원본 확장자를 그대로 사용한다. basename()으로 경로 조작(../)은
    // 막았지만, 확장자 자체는 검증되지 않은 원본 그대로 저장된다.
    $safe_name = basename($file['name']);
    $filename = uniqid('', true) . '_' . $safe_name; // 충돌 방지용 접두사만 추가, 확장자는 그대로
    $dest = UPLOAD_DIR . $filename;

    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        return ['ok' => false, 'error' => '파일 저장에 실패했습니다. uploads 폴더 쓰기 권한을 확인해주세요.'];
    }

    return ['ok' => true, 'filename' => $filename];
}

/**
 * uploads/ 에 저장된 영상 파일 삭제
 * (현재 OTT 쪽에서는 영상 삭제 기능 자체가 없으므로 더는 호출되지 않지만,
 *  추후 필요할 수 있어 헬퍼 함수는 남겨둡니다.)
 */
function delete_uploaded_video($filename) {
    if (!$filename) return;
    $path = UPLOAD_DIR . basename($filename); // basename 으로 경로 조작 방지
    if (is_file($path)) {
        @unlink($path);
    }
}
