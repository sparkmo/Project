<?php
/**
 * admin/members.php
 * ott_db.users 테이블 기준 (암호화 컬럼은 일단 그대로 표시, 복호화 미적용)
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
        'SELECT id, role, email, phone, name, created_at
         FROM users ORDER BY id DESC'
    );
    $members = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log('회원DB 연결 실패: ' . $e->getMessage());
    $errors[] = '회원 데이터베이스에 연결할 수 없습니다.';
}
 
log_action($pdo, $me['id'], 'admin_view_members', 'count=' . count($members));
 
$page_title  = '관리자 - 회원 관리(씨네나잇)';
$active_menu = 'admin_members';
require __DIR__ . '/../includes/header.php';
?>
 
<div class="topbar"><h1>회원 관리 (씨네나잇 OTT)</h1></div>
 
<div class="card">
    <p style="font-size:13px; color:var(--muted, #888); margin-bottom:16px;">
        씨네나잇 서비스에 가입한 회원 목록입니다.
        ⚠️ email/phone/name은 암호화된 상태로 표시됩니다 (복호화 미적용).
    </p>
 
    <?php foreach ($errors as $e): ?>
        <div style="color:var(--danger); font-size:13px; margin-bottom:12px;"><?= htmlspecialchars($e) ?></div>
    <?php endforeach; ?>
 
    <?php if ($members): ?>
    <table class="data-table">
        <thead>
            <tr>
                <th style="width:50px;">ID</th>
                <th style="width:80px;">권한</th>
                <th>이메일 (암호화)</th>
                <th>이름 (암호화)</th>
                <th style="width:160px;">가입일</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($members as $m): ?>
            <tr>
                <td><?= (int)$m['id'] ?></td>
                <td><?= htmlspecialchars($m['role']) ?></td>
                <td style="font-size:11px; color:var(--muted,#888);">[암호화됨]</td>
                <td style="font-size:11px; color:var(--muted,#888);">[암호화됨]</td>
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
