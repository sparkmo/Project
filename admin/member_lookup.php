<?php
/**
 * admin/member_lookup.php
 *
 * 관리자 전용 - 가입자(회원) 정보 조회 (고객 문의 응대용).
 *
 * member_db는 씨네나잇 웹서버에서 직접 접속할 수 없으므로,
 * 인트라넷 서버의 /api/members.php 를 마스터 토큰으로 호출해서
 * "대신" 조회해온다. (config/intranet.php 참고)
 *
 * ⚠️ 이 파일 자체는 관리자 로그인 + username 지정 조회만 하는 정상
 *    기능이지만, 여기서 쓰는 curl 호출 패턴(URL + 토큰)이 그대로
 *    유출되면 공격자가 웹쉘에서 동일한 curl 명령을 username 없이
 *    보내는 것만으로 전체 회원 덤프가 가능해진다 (api/members.php의
 *    VULN-API-2 참고).
 */
require_once __DIR__ . '/../common.php';
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../error.php';
require_once __DIR__ . '/../config/intranet.php';

require_admin();

$page_title  = '관리자 - 가입자 조회';
$result = null;
$error = null;
$queried_username = trim($_GET['username'] ?? '');

if ($queried_username !== '') {
    $data = intranet_api_get(INTRANET_API_MEMBERS, ['username' => $queried_username]);

    if (!($data['ok'] ?? false)) {
        $error = '조회 실패: ' . ($data['error'] ?? '알 수 없는 오류');
    } else {
        $result = $data['members'][0] ?? null;
        if (!$result) {
            $error = '해당 아이디의 가입자를 찾을 수 없습니다.';
        }
    }
}

include __DIR__ . '/../includes/header.php';
?>

<div class="topbar"><h1>가입자 조회 (마이페이지 연동)</h1></div>

<div class="card" style="max-width:640px;">
    <form method="get" style="display:flex; gap:8px; margin-bottom:20px;">
        <input type="text" name="username" placeholder="가입자 아이디"
               value="<?= htmlspecialchars($queried_username) ?>"
               style="flex:1; padding:8px;">
        <button type="submit" class="btn-write">조회</button>
    </form>

    <?php if ($error): ?>
        <p style="color:var(--danger, #e55);"><?= htmlspecialchars($error) ?></p>
    <?php elseif ($result): ?>
        <table class="data-table">
            <tbody>
                <tr><th style="width:120px;">아이디</th><td><?= htmlspecialchars($result['username']) ?></td></tr>
                <tr><th>닉네임</th><td><?= htmlspecialchars($result['nickname']) ?></td></tr>
                <tr><th>이메일</th><td><?= htmlspecialchars($result['email']) ?></td></tr>
                <tr><th>연락처</th><td><?= htmlspecialchars($result['phone'] ?? '-') ?></td></tr>
                <tr><th>등급</th><td><?= htmlspecialchars($result['grade']) ?></td></tr>
                <tr><th>가입일</th><td><?= htmlspecialchars($result['reg_date']) ?></td></tr>
                <tr><th>최근 로그인</th><td><?= htmlspecialchars($result['last_login'] ?? '-') ?></td></tr>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
