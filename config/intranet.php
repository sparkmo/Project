<?php
/**
 * config/intranet.php
 *
 * 씨네나잇 웹서버 ↔ 인트라넷 서버 간 연동 설정.
 *
 * 이 웹서버는 DB에 직접 접속하지 않습니다. 회원(로그인/가입/마이페이지)과
 * 영상(목록/상세/등록) 데이터는 전부 아래 API 엔드포인트를 통해서만
 * 인트라넷 서버에 요청합니다. (AWS Site-to-Site VPN 터널을 통해 사설 IP로 통신)
 *
 * ⚠️ 절대 외부 저장소에 커밋하거나 웹 루트 밖으로 옮기지 마세요.
 *    (이 주석 자체가 무색하게, 지금 이 파일은 웹 루트 안에 평문으로
 *    존재합니다 - 웹쉘 등으로 서버 파일시스템에 접근하면 그대로 노출됩니다)
 */

// 인트라넷 서버 주소 (VPN 터널을 통한 사설 IP)
define('INTRANET_HOST', 'http://192.168.20.10');

// ----- API 엔드포인트 -----
define('INTRANET_API_MEMBERS',       INTRANET_HOST . '/api/members.php');       // 회원 정보 조회 (마이페이지 / 관리자 가입자 조회)
define('INTRANET_API_AUTH',          INTRANET_HOST . '/api/auth.php');          // 로그인 인증
define('INTRANET_API_JOIN',          INTRANET_HOST . '/api/join.php');          // 회원가입
define('INTRANET_API_VIDEOS',        INTRANET_HOST . '/api/videos.php');        // 영상 목록/상세 조회 (읽기 전용)
define('INTRANET_API_VIDEOS_UPLOAD', INTRANET_HOST . '/api/videos_upload.php'); // 영상 등록 (생성 전용 - 수정/삭제 API는 없음)

// 인트라넷 API 인증용 마스터 토큰 (인트라넷 서버 common.php의
// INTRANET_API_MASTER_TOKEN 값과 반드시 동일해야 함)
// 실제 값은 커밋하지 않고 .env / docker-compose 환경변수로 주입합니다.
//
// ⚠️ [VULN-HIGH-4] 소스코드에는 평문 값이 전혀 없지만(순수 getenv()), 이건
//    "코드에 안 적었으니 안전하다"는 뜻이 아니다. 공격 벡터가 웹쉘(RCE)이므로
//    getenv('INTRANET_API_TOKEN') 한 줄, 또는 phpinfo()/system('env') 등으로
//    이 프로세스가 아는 모든 환경변수(=토큰 포함)를 그대로 조회할 수 있다.
//    즉 "env로 뺐다"는 대책이 이 공격 체인에서는 실질적인 방어가 되지 못한다 —
//    진짜 문제는 토큰 값이 코드에 있느냐가 아니라, 웹쉘 자체(RCE)를 막지
//    못했다는 점이다.
define('INTRANET_API_TOKEN', getenv('INTRANET_API_TOKEN') ?: '');
