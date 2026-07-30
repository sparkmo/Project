<?php
require_once __DIR__ . '/common.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/error.php';

require_login();

$page_title = '마이페이지';

// 회원 DB는 이 서버에서 직접 접속할 수 없으므로, 인트라넷 API로 조회를 위임한다.
// (api/members.php는 id, role, created_at, email, phone, name 컬럼을 응답합니다.
//  username(email)로 본인 한 명만 조회합니다)
$member_data = intranet_api_get(INTRANET_API_MEMBERS, ['username' => $_SESSION['username']]);
$user = $member_data['members'][0] ?? null;
if (!$user) {
    alert_redirect('회원 정보를 불러올 수 없습니다.', 'index.php');
}

// 참고: video 테이블은 회원과 관계를 갖지 않습니다 (ott_db 최종 스키마).
// 영상 업로드는 관리자만 하므로, "내가 올린 영상" 같은 회원별 영상 목록 기능은
// 이 스키마에서는 제공할 수 없습니다 (필요하면 DB 담당자에게 관계 컬럼 추가를 요청).

include __DIR__ . '/includes/header.php';
?>

<div class="profile-box">
    <div class="avatar"><?= htmlspecialchars(u8_first_char($user['name'])) ?></div>
    <div>
        <h2 style="margin:0 0 4px;"><?= htmlspecialchars($user['name']) ?></h2>
        <p style="margin:0; color:var(--text-dim); font-size:13px;">
            <?= htmlspecialchars($user['email']) ?> ·
            <?= htmlspecialchars($user['phone'] ?? '-') ?> ·
            가입일 <?= htmlspecialchars($user['created_at']) ?>
        </p>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
