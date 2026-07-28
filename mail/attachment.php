<?php
require_once __DIR__ . '/../common.php';
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/_lib.php';
require_login();
require_mail_login();

$folder  = mail_allowed_folder($_GET['folder'] ?? 'INBOX');
$id      = (int)($_GET['id'] ?? 0);
$part_no = (string)($_GET['part'] ?? '');

// part 번호는 "1", "2.1" 같은 형태만 허용 (imap_fetchbody 에 그대로 넘어가므로 검증 필수)
if ($id <= 0 || $part_no === '' || !preg_match('/^[0-9]+(\.[0-9]+)*$/', $part_no)) {
    http_response_code(400);
    exit('잘못된 요청입니다.');
}

$inbox = mail_connect($folder);
if ($inbox === false) {
    http_response_code(401);
    exit('메일 서버 로그인이 만료되었습니다.');
}

$total = imap_num_msg($inbox);
if ($id > $total) {
    imap_close($inbox);
    http_response_code(404);
    exit('메일을 찾을 수 없습니다.');
}

$structure = imap_fetchstructure($inbox, $id);
$attachments = mail_collect_attachments($structure);

// 요청한 part_no가 실제로 이 메일의 첨부파일 목록에 있는지 확인
// (임의의 part 번호로 본문 등 다른 파트를 긁어가지 못하도록)
$target = null;
foreach ($attachments as $att) {
    if ($att['part_no'] === $part_no) {
        $target = $att;
        break;
    }
}

if ($target === null) {
    imap_close($inbox);
    http_response_code(404);
    exit('첨부파일을 찾을 수 없습니다.');
}

$raw = imap_fetchbody($inbox, $id, $part_no);
imap_close($inbox);

$content = mail_decode_attachment_body($raw, $target['encoding']);

// 파일명에 개행/제어문자가 섞여 헤더 인젝션으로 악용되지 않도록 정리
$safe_filename = preg_replace('/[\r\n"]+/', '_', $target['filename']);

header('Content-Type: application/octet-stream');
header('Content-Length: ' . strlen($content));
header('Content-Disposition: attachment; filename="' . $safe_filename . '"');
header('X-Content-Type-Options: nosniff');
echo $content;
