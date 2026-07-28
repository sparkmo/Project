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
 * 받은편지함의 안읽은 메일 수를 가져온다 (대시보드 요약 카드용).
 * 메일함에 로그인되어 있지 않으면 null (대시보드에서는 "-"로 표시하고
 * 로그인 유도 링크만 보여줌 - 강제로 메일 로그인 페이지로 리다이렉트하지 않음).
 */
function mail_unseen_count(): ?int {
    if (!is_mail_logged_in() || !function_exists('imap_open')) {
        return null;
    }
    $mailbox = mail_build_mailbox_spec('INBOX');
    $conn = @imap_open($mailbox, $_SESSION['mail_email'], $_SESSION['mail_password']);
    if ($conn === false) {
        return null;
    }
    $status = @imap_status($conn, $mailbox, SA_UNSEEN);
    imap_close($conn);
    return $status !== false ? (int)$status->unseen : null;
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

/**
 * 화면/URL에서 넘어온 폴더 이름을 화이트리스트로 검증.
 * 지원 폴더 외의 값이 오면 기본값(INBOX)으로 대체합니다.
 */
function mail_allowed_folder(?string $folder): string {
    $allowed = ['INBOX' => 'INBOX', 'SENT' => MAIL_SENT_FOLDER];
    $key = strtoupper((string)$folder);
    return $allowed[$key] ?? 'INBOX';
}

/** 폴더 실제 이름(IMAP)으로 화면에 쓸 짧은 키('INBOX'/'SENT')를 되돌려줌 */
function mail_folder_key(string $folder): string {
    return $folder === MAIL_SENT_FOLDER ? 'SENT' : 'INBOX';
}

/**
 * 받은편지함/보낸편지함 공용 목록 조회.
 * inbox.php 와 sent.php 가 함께 사용합니다.
 *
 * @return array{ok: bool, error: string, messages: array, total: int}
 */
function mail_list_messages(string $folder, int $page, int $per_page): array {
    $inbox = mail_connect($folder);
    if ($inbox === false) {
        mail_session_clear();
        return ['ok' => false, 'error' => '메일 서버 로그인이 만료되었습니다.', 'messages' => [], 'total' => 0];
    }

    $total = imap_num_msg($inbox);
    $messages = [];

    if ($total > 0) {
        // 최신 메일이 위로 오도록 번호를 거꾸로 계산 (imap 메시지 번호는 1부터 시작, 오래된 순)
        $start = $total - ($page - 1) * $per_page;
        $end   = max(1, $start - $per_page + 1);

        if ($start >= 1) {
            $range = $end . ':' . $start;
            $overview = imap_fetch_overview($inbox, $range, 0);
            usort($overview, function ($a, $b) { return $b->msgno <=> $a->msgno; });
            $messages = $overview;
        }
    }

    imap_close($inbox);

    return ['ok' => true, 'error' => '', 'messages' => $messages, 'total' => $total];
}

/**
 * 메일 구조(imap_fetchstructure)를 재귀적으로 훑어서 첨부파일 목록을 뽑아낸다.
 * 파일명이 있는(Content-Disposition: attachment 이거나 name/filename 파라미터가
 * 붙은) 파트만 첨부로 취급합니다. 본문(text/plain, text/html)은 제외.
 *
 * @return array<int, array{part_no: string, filename: string, size: int, encoding: int}>
 */
function mail_collect_attachments($structure, string $prefix = ''): array {
    $result = [];
    if (!isset($structure->parts) || !is_array($structure->parts)) {
        return $result;
    }

    foreach ($structure->parts as $i => $part) {
        $part_no = $prefix . ($i + 1);
        $filename = '';

        foreach (($part->dparameters ?? []) as $p) {
            if (strcasecmp($p->attribute, 'filename') === 0) {
                $filename = $p->value;
            }
        }
        if ($filename === '') {
            foreach (($part->parameters ?? []) as $p) {
                if (strcasecmp($p->attribute, 'name') === 0) {
                    $filename = $p->value;
                }
            }
        }

        $is_attachment = ($filename !== '') &&
            !(($part->type ?? 0) === 0 && in_array(strtolower($part->subtype ?? ''), ['plain', 'html'], true));

        if ($is_attachment) {
            $result[] = [
                'part_no'  => $part_no,
                'filename' => $filename !== '' ? imap_utf8($filename) : ('attachment-' . $part_no),
                'size'     => (int)($part->bytes ?? 0),
                'encoding' => (int)($part->encoding ?? 0),
            ];
        }

        if (!empty($part->parts)) {
            $result = array_merge($result, mail_collect_attachments($part, $part_no . '.'));
        }
    }

    return $result;
}

/** imap 인코딩 상수(0~5)에 맞춰 첨부파일 raw 바디를 실제 바이트로 디코딩 */
function mail_decode_attachment_body(string $raw, int $encoding): string {
    switch ($encoding) {
        case ENC7BIT:
        case ENC8BIT:
        case ENCBINARY:
            return $raw;
        case ENCBASE64:
            return (string)base64_decode($raw);
        case ENCQUOTEDPRINTABLE:
            return quoted_printable_decode($raw);
        default:
            return $raw;
    }
}

/**
 * mail/send.php 에서 사용하는 아주 단순한 SMTP 클라이언트.
 *
 * PHP의 imap 확장은 "수신"만 담당하고 발송 기능이 없고, 기본 mail() 함수는
 * 로컬 sendmail을 전제로 해서 사내 메일 서버(다른 호스트, 인증 필요)로
 * 보내는 용도로는 못 씁니다. 그래서 config/mail.php 의 SMTP_* 설정을 이용해
 * fsockopen 기반으로 RFC 5321 SMTP 대화를 직접 주고받습니다.
 *
 * 인증은 세션에 보관된 값(직원이 /mail/login.php 에서 입력한 이메일/비밀번호)을
 * 그대로 사용합니다 — 즉 "로그인한 본인 계정"으로만 보낼 수 있고, 발신자를
 * 다른 사람으로 바꿔치기하는 기능은 없습니다.
 *
 * 성공 시 발송에 실제로 사용한 RFC822 원문(raw)도 함께 돌려줍니다.
 * (mail/send.php 가 이 원문을 그대로 IMAP "Sent" 폴더에 appen드 하기 위함)
 *
 * @return array{ok: bool, error: string, raw: string}
 */
function mail_smtp_send(string $to, string $subject, string $body): array {
    if (!is_mail_logged_in()) {
        return ['ok' => false, 'error' => '메일함에 로그인되어 있지 않습니다.', 'raw' => ''];
    }
    if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
        return ['ok' => false, 'error' => '받는사람 이메일 주소 형식이 올바르지 않습니다.', 'raw' => ''];
    }

    $from = $_SESSION['mail_email'];
    $password = $_SESSION['mail_password'];

    $encoded_subject = '=?UTF-8?B?' . base64_encode($subject) . '?=';
    // 메일 본문 줄 앞의 '.'은 SMTP 프로토콜상 종료 신호와 겹치므로 이스케이프
    $escaped_body = preg_replace('/^\./m', '..', $body);

    $headers = implode("\r\n", [
        'From: ' . $from,
        'To: ' . $to,
        'Subject: ' . $encoded_subject,
        'Date: ' . date('r'),
        'MIME-Version: 1.0',
        'Content-Type: text/plain; charset=UTF-8',
        'Content-Transfer-Encoding: 8bit',
    ]);

    $raw_message = $headers . "\r\n\r\n" . $escaped_body;

    $use_ssl = (SMTP_ENCRYPTION === 'ssl');
    $host = ($use_ssl ? 'ssl://' : '') . SMTP_HOST;

    $context = stream_context_create([
        'ssl' => [
            'verify_peer'      => SMTP_VALIDATE_CERT,
            'verify_peer_name' => SMTP_VALIDATE_CERT,
            'allow_self_signed' => !SMTP_VALIDATE_CERT,
        ],
    ]);

    $sock = @stream_socket_client($host . ':' . SMTP_PORT, $errno, $errstr, 10,
        STREAM_CLIENT_CONNECT, $context);
    if (!$sock) {
        return ['ok' => false, 'error' => "메일 서버에 연결할 수 없습니다. ({$errstr})", 'raw' => ''];
    }

    // 응답 코드가 기대값으로 시작하는지 확인하며 한 줄(멀티라인 포함) 읽기
    $read = function (string $expect_code) use ($sock): array {
        $line = '';
        do {
            $line = fgets($sock, 1024);
            if ($line === false) {
                return [false, ''];
            }
        } while (isset($line[3]) && $line[3] === '-'); // "250-..." 형태의 중간 응답은 계속 읽음
        return [substr($line, 0, 3) === $expect_code, $line];
    };

    $send = function (string $cmd) use ($sock): void {
        fwrite($sock, $cmd . "\r\n");
    };

    try {
        [$ok] = $read('220');
        if (!$ok) return ['ok' => false, 'error' => '메일 서버가 연결을 받아주지 않았습니다.', 'raw' => ''];

        $send('EHLO admin-server.local');
        [$ok] = $read('250');
        if (!$ok) return ['ok' => false, 'error' => 'EHLO 인사에 실패했습니다.', 'raw' => ''];

        if (SMTP_ENCRYPTION === 'starttls') {
            $send('STARTTLS');
            [$ok] = $read('220');
            if (!$ok) return ['ok' => false, 'error' => 'STARTTLS 시작에 실패했습니다.', 'raw' => ''];

            if (!stream_socket_enable_crypto($sock, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                return ['ok' => false, 'error' => 'TLS 협상에 실패했습니다.', 'raw' => ''];
            }

            $send('EHLO admin-server.local');
            [$ok] = $read('250');
            if (!$ok) return ['ok' => false, 'error' => 'STARTTLS 이후 EHLO에 실패했습니다.', 'raw' => ''];
        }

        //$send('AUTH LOGIN');
        //[$ok] = $read('334');
        //if (!$ok) return ['ok' => false, 'error' => '메일 서버가 AUTH LOGIN을 지원하지 않습니다.', 'raw' => ''];

        //$send(base64_encode($from));
        //[$ok] = $read('334');
        //if (!$ok) return ['ok' => false, 'error' => '메일 계정 인증에 실패했습니다.', 'raw' => ''];

        //$send(base64_encode($password));
        //[$ok] = $read('235');
        //if (!$ok) return ['ok' => false, 'error' => '메일 비밀번호 인증에 실패했습니다.', 'raw' => ''];

        $send('MAIL FROM:<' . $from . '>');
        [$ok] = $read('250');
        if (!$ok) return ['ok' => false, 'error' => '발신자 주소가 거부되었습니다.', 'raw' => ''];

        $send('RCPT TO:<' . $to . '>');
        [$ok] = $read('250');
        if (!$ok) return ['ok' => false, 'error' => '받는사람 주소가 거부되었습니다.', 'raw' => ''];

        $send('DATA');
        [$ok] = $read('354');
        if (!$ok) return ['ok' => false, 'error' => 'DATA 명령이 거부되었습니다.', 'raw' => ''];

        $send($raw_message . "\r\n.");
        [$ok] = $read('250');
        if (!$ok) return ['ok' => false, 'error' => '메일 전송이 서버에서 거부되었습니다.', 'raw' => ''];

        $send('QUIT');
        fclose($sock);

        return ['ok' => true, 'error' => '', 'raw' => $raw_message];
    } catch (\Throwable $e) {
        if (is_resource($sock)) {
            fclose($sock);
        }
        return ['ok' => false, 'error' => '메일 발송 중 오류가 발생했습니다.', 'raw' => ''];
    }
}

/**
 * 발송에 성공한 메일 원문을 IMAP "Sent" 폴더에 저장(append)한다.
 * 실패해도(예: Sent 폴더가 서버에 없음) 발송 자체는 이미 끝난 뒤이므로
 * 이 함수의 실패는 send.php에서 경고 정도로만 다룹니다.
 */
function mail_append_to_sent(string $raw_message): bool {
    if (!is_mail_logged_in() || !function_exists('imap_open')) {
        return false;
    }
    // 폴더 자체에 접속(폴더가 없으면 실패)해서 append
    $mailbox = mail_build_mailbox_spec(MAIL_SENT_FOLDER);
    $conn = @imap_open($mailbox, $_SESSION['mail_email'], $_SESSION['mail_password']);
    if ($conn === false) {
        return false;
    }
    $ok = @imap_append($conn, $mailbox, $raw_message . "\r\n", '\\Seen');
    imap_close($conn);
    return (bool)$ok;
}

/**
 * 메일 한 통을 삭제(휴지통으로 이동이 아니라 즉시 삭제 플래그 후 expunge)한다.
 * hMailServer 등 대부분의 IMAP 서버에서 삭제는 "Deleted 플래그 설정 + expunge" 방식.
 */
function mail_delete_message(string $folder, int $msgno): bool {
    $conn = mail_connect($folder);
    if ($conn === false) {
        return false;
    }
    $ok = imap_delete($conn, (string)$msgno);
    if ($ok) {
        imap_expunge($conn);
    }
    imap_close($conn);
    return $ok;
}
