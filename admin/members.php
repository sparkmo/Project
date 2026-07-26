<?php
/**
 * admin/members.php
 * 씨네나잇 OTT 서비스 가입 회원(고객) 정보 조회 화면.
 * ott_db는 인트라넷 서버에서만 접근 가능하도록 격리되어 있으므로,
 * 이 페이지가 사실상 회원 개인정보에 접근할 수 있는 유일한 경로입니다.
 * (관리자 등급만 접근 가능 - require_admin())
 *
 * ott_db.member 스키마에는 phone/grade/last_login 컬럼이 없습니다.
 */
require_once __DIR__ . '/../common.php';
require_once __DIR__ . '/../auth.php';
require_admin();

$me = current_user($pdo);

$errors = [];
$members = [];

try {
    $pdo_member = get_member_pdo();
    $stmt = $pdo_member->query(
        'SELECT member_id, login_id, nickname, email, created_at
         FROM member
         ORDER BY member_id DESC'
    );
    $members = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log('회원DB 연결 실패: ' . $e->getMessage());
    $errors[] = '회원 데이터베이스에 연결할 수 없습니다. 네트워크/접속 정보를 확인해주세요.';
}

log_action($pdo, $me['id'], 'admin_view_members', 'count=' . count($members));

$page_title  = '관리자 - 회원 관리(씨네나잇)';
$active_menu = 'admin_members';
require __DIR__ . '/../includes/header.php';
?>

<div class="topbar"><h1>회원 관리 (씨네나잇 OTT)</h1></div>

<div class="card">
    <p style="font-size:13px; color:var(--muted, #888); margin-bottom:16px;">
        씨네나잇 서비스에 가입한 회원 목록입니다. 이 데이터는 별도의 회원 데이터베이스(내부망 전용)에
        저장되어 있으며, 씨네나잇 웹서버(콘텐츠 서비스)에서는 직접 조회할 수 없습니다.
    </p>

    <?php foreach ($errors as $e): ?>
        <div style="color:var(--danger); font-size:13px; margin-bottom:12px;"><?= htmlspecialchars($e) ?></div>
    <?php endforeach; ?>

    <?php if ($members): ?>
    <table class="data-table">
        <thead>
            <tr>
                <th style="width:50px;">ID</th>
                <th>아이디</th>
                <th>닉네임</th>
                <th>이메일</th>
                <th style="width:160px;">가입일</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($members as $m): ?>
            <tr>
                <td><?= (int)$m['member_id'] ?></td>
                <td><?= htmlspecialchars($m['login_id']) ?></td>
                <td><?= htmlspecialchars($m['nickname']) ?></td>
                <td><?= htmlspecialchars($m['email']) ?></td>
                <td><?= htmlspecialchars(format_datetime($m['created_at'])) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php elseif (!$errors): ?>
        <p style="font-size:13px; color:var(--muted, #888);">등록된 회원이 없습니다.</p>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
