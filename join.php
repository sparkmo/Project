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
    $phone1   = trim($_POST['phone1'] ?? '');
    $phone2   = trim($_POST['phone2'] ?? '');
    $phone3   = trim($_POST['phone3'] ?? '');

    if ($username === '' || $password === '' || $nickname === '' || $email === '' || $phone1 === '' || $phone2 === '' || $phone3 === '') {
        alert_back('모든 항목을 입력해주세요.');
    }
    if (!preg_match('/^[a-zA-Z0-9_]{4,20}$/', $username)) {
        alert_back('아이디는 영문/숫자/언더바 4~20자로 입력해주세요.');
    }
    if (strlen($password) < 6) {
        alert_back('패스워드는 6자 이상 입력해주세요.');
    }
    if ($password !== $password2) {
        alert_back('패스워드가 일치하지 않습니다.');
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        alert_back('올바른 이메일 형식이 아닙니다.');
    }
    // 010 / 011 등 국번 2~3자리 - 3~4자리 - 4자리
    if (!preg_match('/^0\d{1,2}$/', $phone1)) {
        alert_back('전화번호 앞자리를 확인해주세요. (예: 010)');
    }
    if (!preg_match('/^\d{3,4}$/', $phone2)) {
        alert_back('전화번호 가운데 자리를 확인해주세요.');
    }
    if (!preg_match('/^\d{4}$/', $phone3)) {
        alert_back('전화번호 끝자리 4자리를 확인해주세요.');
    }
    $phone_digits = $phone1 . $phone2 . $phone3;
    if (!preg_match('/^0\d{9,10}$/', $phone_digits)) {
        alert_back('올바른 전화번호 형식이 아닙니다.');
    }
    $phone = $phone1 . '-' . $phone2 . '-' . $phone3; // DB에는 하이픈 포함해서 저장

    // 회원 DB는 이 서버에서 직접 접속할 수 없으므로, 인트라넷 API로 가입 처리를 위임한다.
    // (비밀번호 해시도 이 서버가 아니라 인트라넷 서버에서 생성한다)
    $data = intranet_api_post(INTRANET_API_JOIN, [
        'username' => $username,
        'password' => $password,
        'nickname' => $nickname,
        'email'    => $email,
        'phone'    => $phone,
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
            <label>이름</label>
            <input type="text" name="nickname" required maxlength="50">
        </div>
        <div class="field">
            <label>이메일</label>
            <input type="email" name="email" required maxlength="100">
        </div>
        <div class="field">
            <label>패스워드</label>
            <input type="password" name="password" required maxlength="255" placeholder="6자 이상">
        </div>
        <div class="field">
            <label>패스워드 확인</label>
            <input type="password" name="password2" required maxlength="255">
        </div>
        <div class="field">
            <label>폰</label>
            <div style="display:flex; align-items:center; gap:6px;">
                <input type="text" name="phone1" required maxlength="3" placeholder="010" style="width:60px; text-align:center;">
                <span>-</span>
                <input type="text" name="phone2" required maxlength="4" placeholder="1234" style="width:70px; text-align:center;">
                <span>-</span>
                <input type="text" name="phone3" required maxlength="4" placeholder="5678" style="width:70px; text-align:center;">
            </div>
        </div>
        <button type="submit" class="btn-submit">가입하기</button>
    </form>
    <p class="form-sub-link">이미 계정이 있으신가요? <a href="/login.php">로그인</a></p>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
