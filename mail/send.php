<?php
require_once __DIR__ . '/../common.php';
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/_lib.php';
require_login();
require_mail_login();

$me = current_user($pdo);

$error = '';
$success = false;
$sent_warning = '';

$to = '';
$subject = '';
$body = '';

// 답장(/mail/view.php 의 "답장" 버튼)으로 들어온 경우, 원본 메일을 읽어서
// 받는사람/제목/인용 본문을 미리 채워준다. GET 으로만 동작 (POST 제출 시에는 무시).
if ($_SERVER['REQUEST_METHOD'] === 'GET' && !empty($_GET['reply_to'])) {
    $reply_id = (int)$_GET['reply_to'];
    $reply_folder = mail_allowed_folder($_GET['folder'] ?? 'INBOX');

    $conn = mail_connect($reply_folder);
    if ($conn !== false) {
        $total = imap_num_msg($conn);
        if ($reply_id > 0 && $reply_id <= $total) {
            $header = imap_headerinfo($conn, $reply_id);
            $orig_subject = isset($header->subject) ? imap_utf8($header->subject) : '';
            $orig_from    = isset($header->from[0]) ? imap_utf8($header->from[0]->mailbox . '@' . $header->from[0]->host) : '';
            $orig_date    = isset($header->date) ? date('Y-m-d H:i', strtotime($header->date)) : '';

            $to = $orig_from;
            $subject = (stripos($orig_subject, 're:') === 0) ? $orig_subject : ('Re: ' . $orig_subject);

            $structure = imap_fetchstructure($conn, $reply_id);
            $orig_body = '';
            if (!empty($structure->parts)) {
                $plain_part = mail_find_reply_part($structure, 'text/plain');
                if ($plain_part) {
                    $orig_body = imap_fetchbody($conn, $reply_id, $plain_part);
                }
            } else {
                $raw = imap_body($conn, $reply_id);
                $orig_body = (($structure->subtype ?? '') === 'HTML') ? strip_tags($raw) : $raw;
            }

            $quoted = preg_replace('/^/m', '> ', trim($orig_body));
            $body = "\n\n----- 원본 메일 (" . $orig_from . ", " . $orig_date . ") -----\n" . $quoted;
        }
        imap_close($conn);
    }
}

/** send.php 전용으로 답장 인용문을 뽑기 위한 최소 버전 (view.php 의 mail_find_part 와 동일 로직) */
function mail_find_reply_part($structure, $mime_type, $prefix = '') {
    if (!isset($structure->parts) || !is_array($structure->parts)) {
        return null;
    }
    foreach ($structure->parts as $i => $part) {
        $part_no = $prefix . ($i + 1);
        $subtype = strtolower($part->subtype ?? '');
        $full_type = ($part->type ?? 0) === 0 ? 'text/' . $subtype : $subtype;
        if ($full_type === $mime_type) {
            return $part_no;
        }
        if (!empty($part->parts)) {
            $found = mail_find_reply_part($part, $mime_type, $part_no . '.');
            if ($found) return $found;
        }
    }
    return null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $to      = trim((string)($_POST['to'] ?? ''));
    $subject = trim((string)($_POST['subject'] ?? ''));
    $body    = (string)($_POST['body'] ?? '');

    if ($to === '' || $subject === '' || $body === '') {
        $error = '받는사람, 제목, 내용을 모두 입력해주세요.';
    } elseif (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
        $error = '받는사람 이메일 주소 형식이 올바르지 않습니다.';
    } else {
        $result = mail_smtp_send($to, $subject, $body);
        if ($result['ok']) {
            $success = true;
            if (!mail_append_to_sent($result['raw'])) {
                // 발송 자체는 성공했으니 에러로 취급하지 않고 안내만 함
                $sent_warning = '메일은 정상적으로 발송됐지만, 보낸 메일함에 저장하는 데는 실패했습니다.';
            }
            $to = '';
            $subject = '';
            $body = '';
        } else {
            $error = $result['error'];
        }
    }
}

$page_title  = '메일 쓰기';
$active_menu = 'mail';
require __DIR__ . '/../includes/header.php';
?>

<div class="topbar">
    <div>
        <div class="breadcrumb"><a href="/mail/inbox.php">사내 메일</a> &rsaquo; 메일 쓰기</div>
        <h1>메일 쓰기</h1>
    </div>
    <a href="/mail/inbox.php" class="btn btn-ghost">받은 메일함</a>
</div>

<div class="card" style="max-width:640px;">
    <p style="font-size:13px; color:var(--muted, #888); margin-bottom:16px;">
        보내는사람은 현재 로그인된 메일 계정(<?= htmlspecialchars($me['email']) ?>)으로 고정됩니다.
    </p>

    <?php if ($success): ?>
        <p style="color:var(--success, #2e7d32); font-size:13px; margin-bottom:16px;">메일을 보냈습니다.</p>
        <?php if ($sent_warning): ?>
            <p style="color:var(--muted, #888); font-size:12px; margin-bottom:16px;"><?= htmlspecialchars($sent_warning) ?></p>
        <?php endif; ?>
    <?php endif; ?>

    <?php if ($error): ?>
        <p style="color:var(--danger); font-size:13px; margin-bottom:16px;"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <form method="post">
        <div class="form-group">
            <label>보내는사람</label>
            <input type="text" value="<?= htmlspecialchars($me['email']) ?>" disabled
                   style="width:100%; padding:8px; box-sizing:border-box;">
        </div>
        <div class="form-group">
            <label for="to">받는사람</label>
            <input type="email" id="to" name="to" required
                   value="<?= htmlspecialchars($to) ?>"
                   placeholder="name@company.example"
                   style="width:100%; padding:8px; box-sizing:border-box;">
        </div>
        <div class="form-group">
            <label for="subject">제목</label>
            <input type="text" id="subject" name="subject" required
                   value="<?= htmlspecialchars($subject) ?>"
                   style="width:100%; padding:8px; box-sizing:border-box;">
        </div>
        <div class="form-group">
            <label for="body">내용</label>
            <textarea id="body" name="body" required rows="12"
                      style="width:100%; padding:8px; box-sizing:border-box; font-family:inherit;"><?= htmlspecialchars($body) ?></textarea>
        </div>
        <button type="submit" class="btn btn-primary">보내기</button>
    </form>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
