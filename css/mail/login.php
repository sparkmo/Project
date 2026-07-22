<?php
require_once __DIR__ . '/../common.php';
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/_lib.php';
require_login();

$me = current_user($pdo);

if (is_mail_logged_in()) {
    header('Location: /mail/inbox.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = (string)($_POST['mail_password'] ?? '');

    if ($password === '') {
        $error = '메일 비밀번호를 입력해주세요.';
    } elseif (!function_exists('imap_open')) {
        // 이 서버에 PHP imap 확장이 설치되어 있지 않은 경우 (Dockerfile 참고)
        $error = '메일 서버 연동 모듈(imap 확장)이 설치되어 있지 않습니다.';
    } else {
        // 직원 본인의 회사 이메일을 아이디로 사용
        $_SESSION['mail_email']    = $me['email'];
        $_SESSION['mail_password'] = $password;

        $inbox = mail_connect('INBOX');
        if ($inbox === false) {
            mail_session_clear();
            $error = '메일 서버 로그인에 실패했습니다. 이메일/비밀번호 또는 메일 서버 접속 정보를 확인해주세요.';
        } else {
            imap_close($inbox);
            header('Location: /mail/inbox.php');
            exit;
        }
    }
}

$page_title  = '사내 메일 로그인';
$active_menu = 'mail';
require __DIR__ . '/../includes/header.php';
?>

<div class="topbar"><h1>사내 메일</h1></div>

<div class="card" style="max-width:480px;">
    <p style="font-size:13px; color:var(--muted, #888); margin-bottom:16px;">
        사내 메일함은 인트라넷과는 별도의 메일 서버에서 관리됩니다.
        인트라넷 로그인 계정과 무관하게, 회사 이메일 계정의 메일 비밀번호를
        입력해주세요. (아이디는 회사 이메일 주소로 고정됩니다.)
    </p>

    <?php if ($error): ?>
        <p style="color:var(--danger); font-size:13px; margin-bottom:16px;"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <form method="post">
        <div class="form-group">
            <label>회사 이메일</label>
            <input type="text" value="<?= htmlspecialchars($me['email']) ?>" disabled
                   style="width:100%; padding:8px; box-sizing:border-box;">
        </div>
        <div class="form-group">
            <label for="mail_password">메일 비밀번호</label>
            <input type="password" id="mail_password" name="mail_password" required autofocus
                   style="width:100%; padding:8px; box-sizing:border-box;">
        </div>
        <button type="submit" class="btn btn-primary">메일함 열기</button>
    </form>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
