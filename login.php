<?php
require_once __DIR__ . '/common.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/error.php';

if (is_login()) {
    alert_redirect('이미 로그인되어 있습니다.', 'index.php');
}

$page_title = '로그인';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        alert_back('아이디와 비밀번호를 입력해주세요.');
    }

    // 회원 DB는 이 서버에서 직접 접속할 수 없으므로, 인트라넷 API로 인증을 위임한다.
    $data = intranet_api_post(INTRANET_API_AUTH, [
        'username' => $username,
        'password' => $password,
    ]);

    if (!($data['ok'] ?? false)) {
        alert_back($data['error'] ?? '아이디 또는 비밀번호가 올바르지 않습니다.');
    }

    $member = $data['member'];

    // 세션 고정 공격 방지
    session_regenerate_id(true);

    $_SESSION['user_id']  = $member['id'];
    $_SESSION['username'] = $member['username'];
    $_SESSION['nickname'] = $member['nickname'];
    $_SESSION['grade']    = $member['role'] ?? 'user';   // 추가 — role → grade 매핑


    alert_redirect('로그인되었습니다.', 'index.php');
}

include __DIR__ . '/includes/header.php';
?>

<div class="form-box">
    <h2>로그인</h2>
    <form method="post">
        <div class="field">
            <label>이메일</label>
            <input type="text" name="username" required maxlength="20">
        </div>
        <div class="field">
            <label>비밀번호</label>
            <input type="password" name="password" required maxlength="255">
        </div>
        <button type="submit" class="btn-submit">로그인</button>
    </form>
    <p class="form-sub-link">계정이 없으신가요? <a href="/join.php">회원가입</a></p>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
