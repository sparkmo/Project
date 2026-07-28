<?php
/**
 * config/mail.php
 *
 * 사내 메일 서버(윈도우 메일 서버, IMAP) 연동 설정.
 *
 * 직원 개개인의 메일함은 이 인트라넷 DB(intranet_db)가 아니라 별도의 메일
 * 서버(윈도우)에 있습니다. 인트라넷은 이 메일함을 "대신 로그인해서 조회"하지
 * 않고, 직원이 자기 메일 비밀번호를 직접 입력하면 그 값으로 매 요청마다
 * IMAP 세션을 새로 여는 방식입니다 (/mail/login.php, /mail/inbox.php 참고).
 *
 * - 로그인 아이디: 직원의 회사 이메일 주소를 그대로 사용 (intranet_db.employee.email)
 * - 로그인 비밀번호: 인트라넷 로그인 비밀번호와는 별개이며, DB에 저장하지 않고
 *   PHP 세션에만 보관합니다 (자세한 내용은 README.md의 "사내 메일" 섹션 참고).
 *
 * ⚠️ 이 프로젝트는 학습/테스트용 예제입니다. 세션에 메일 비밀번호를 평문으로
 *    잠시 들고 있는 것 자체도 실제 운영 환경이라면 추가 검토가 필요한
 *    지점입니다 (예: 세션 저장소 암호화, 짧은 만료시간, HTTPS 강제 등).
 */

// 윈도우 메일 서버 주소 (인트라넷과 같은 사설망/VPN 안에 있다고 가정)
define('MAIL_HOST', getenv('MAIL_HOST') ?: '192.168.20.20');

// IMAP 포트 (기본 SSL: 993 / 평문 또는 STARTTLS: 143)
define('MAIL_PORT', (int)(getenv('MAIL_PORT') ?: 993));

// 암호화 방식: ssl | tls | none
define('MAIL_ENCRYPTION', getenv('MAIL_ENCRYPTION') ?: 'ssl');

// 자체서명 인증서 등을 쓰는 사내망 메일 서버가 많아 기본은 인증서 검증을 끔.
// 운영 환경에서는 가능하면 true로 두고 정식 인증서를 쓰는 것을 권장합니다.
define('MAIL_VALIDATE_CERT', (getenv('MAIL_VALIDATE_CERT') ?: 'false') === 'true');

/**
 * PHP imap 확장에 넘길 mailbox 접속 문자열(DSN)을 만들어줌.
 * 예: {192.168.20.20:993/imap/ssl/novalidate-cert}INBOX
 */
function mail_build_mailbox_spec(string $folder = 'INBOX'): string {
    $flags = ['imap'];

    if (MAIL_ENCRYPTION === 'ssl') {
        $flags[] = 'ssl';
    } elseif (MAIL_ENCRYPTION === 'tls') {
        $flags[] = 'tls';
    } else {
        $flags[] = 'notls';
    }

    if (!MAIL_VALIDATE_CERT) {
        $flags[] = 'novalidate-cert';
    }

    return '{' . MAIL_HOST . ':' . MAIL_PORT . '/' . implode('/', $flags) . '}' . $folder;
}

// ----------------------------------------------------------------------
// 발신(SMTP) 설정 — 메일 쓰기(mail/send.php) 기능에서 사용.
// 기본값은 수신(IMAP)과 같은 메일 서버 호스트를 쓰되, 포트/암호화 방식만
// SMTP 제출용(587/STARTTLS)으로 다르게 잡습니다. 환경에 따라 .env 에서
// 별도 값으로 덮어쓸 수 있습니다.
// ----------------------------------------------------------------------

define('SMTP_HOST', getenv('SMTP_HOST') ?: MAIL_HOST);

// 제출 포트(submission) 기본 587(STARTTLS). SMTPS(암묵적 SSL)면 465.
define('SMTP_PORT', (int)(getenv('SMTP_PORT') ?: 587));

// 암호화 방식: starttls | ssl | none
define('SMTP_ENCRYPTION', getenv('SMTP_ENCRYPTION') ?: 'starttls');

define('SMTP_VALIDATE_CERT', (getenv('SMTP_VALIDATE_CERT') ?: (MAIL_VALIDATE_CERT ? 'true' : 'false')) === 'true');

// 보낸 메일을 저장해둘 IMAP 폴더 이름. hMailServer 기본값은 보통 'Sent'.
// (서버마다 'Sent Items' 등으로 다를 수 있어 환경변수로 조정 가능하게 함)
define('MAIL_SENT_FOLDER', getenv('MAIL_SENT_FOLDER') ?: 'Sent');
