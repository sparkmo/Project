<?php
require_once __DIR__ . '/common.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/error.php';

if (is_login()) {
    alert_redirect('이미 로그인되어 있습니다.', 'index.php');
}

$page_title = '회원가입';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $password2= $_POST['password2'] ?? '';
    $nickname = trim($_POST['nickname'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $phone    = trim($_POST['phone'] ?? '');

    if ($username === '' || $password === '' || $nickname === '' || $email === '' || $phone === '') {
        alert_back('모든 항목을 입력해주세요.');
    }
    if (!preg_match('/^[a-zA-Z0-9_]{4,20}$/', $username)) {
        alert_back('아이디는 영문/숫자/언더바 4~20자로 입력해주세요.');
    }
    if (strlen($password) < 6) {
        alert_back('비밀번호는 6자 이상 입력해주세요.');
    }
    if ($password !== $password2) {
        alert_back('비밀번호가 일치하지 않습니다.');
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        alert_back('올바른 이메일 형식이 아닙니다.');
    }
    // 하이픈 있어도/없어도 허용 후 숫자만 남겨서 정규화 (010-1234-5678, 01012345678 등)
    $phone_digits = preg_replace('/\D/', '', $phone);
    if (!preg_match('/^0\d{9,10}$/', $phone_digits)) {
        alert_back('올바른 전화번호 형식이 아닙니다. (예: 010-1234-5678)');
    }

    // 회원 DB는 이 서버에서 직접 접속할 수 없으므로, 인트라넷 API로 가입 처리를 위임한다.
    // (비밀번호 해시도 이 서버가 아니라 인트라넷 서버에서 생성한다)
    $data = intranet_api_post(INTRANET_API_JOIN, [
        'username' => $username,
        'password' => $password,
        'nickname' => $nickname,
        'email'    => $email,
        'phone'    => $phone_digits,
    ]);

    if (!($data['ok'] ?? false)) {
        alert_back($data['error'] ?? '회원가입에 실패했습니다.');
    }

    alert_redirect('회원가입이 완료되었습니다. 로그인해주세요.', 'login.php');
}

include __DIR__ . '/includes/header.php';
?>

<div class="form-box">
    <h2>회원가입</h2>
    <form method="post">
        <div class="field">
            <label>아이디</label>
            <input type="text" name="username" required maxlength="20" placeholder="영문/숫자 4~20자">
        </div>
        <div class="field">
            <label>비밀번호</label>
            <input type="password" name="password" required maxlength="255" placeholder="6자 이상">
        </div>
        <div class="field">
            <label>비밀번호 확인</label>
            <input type="password" name="password2" required maxlength="255">
        </div>
        <div class="field">
            <label>닉네임</label>
            <input type="text" name="nickname" required maxlength="50">
        </div>
        <div class="field">
            <label>이메일</label>
            <input type="email" name="email" required maxlength="100">
        </div>
        <div class="field">
            <label>전화번호</label>
            <input type="tel" name="phone" required maxlength="20" placeholder="010-1234-5678">
        </div>
        <button type="submit" class="btn-submit">가입하기</button>
    </form>
    <p class="form-sub-link">이미 계정이 있으신가요? <a href="/login.php">로그인</a></p>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
