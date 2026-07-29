<?php
/**
 * admin/inquiries.php
 * ott_db.inquiry 전체 목록 조회 + 상태 변경(대기 -> 처리완료) 화면.
 *
 * 참고: inquiry 테이블에는 '답변 내용'을 저장할 컬럼이 없습니다
 * (title/content/status/created_at 뿐). 관리자가 실제로 답변 내용을 남겨야
 * 한다면 DB 담당자에게 answer(TEXT), answered_at(DATETIME) 같은 컬럼
 * 추가를 요청해야 합니다. 지금은 상태값만 변경 가능합니다.
 */
require_once __DIR__ . '/../common.php';
require_once __DIR__ . '/../auth.php';
require_admin();
 
$me = current_user($pdo);
 
$errors = [];
$success = '';
$inquiries = [];
 
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_status') {
    $id     = (int)($_POST['id'] ?? 0);
    $status = $_POST['status'] ?? '';
 
    if (!in_array($status, ['대기', '처리완료'], true)) {
        $errors[] = '올바르지 않은 상태값입니다.';
    } else {
        try {
            $pdo_member = get_member_pdo();
            $pdo_member->prepare('UPDATE inquiry SET status=? WHERE inquiry_id=?')->execute([$status, $id]);
            log_action($pdo, $me['id'], 'admin_update_inquiry_status', 'inquiry_id=' . $id . ' status=' . $status);
            $success = '상태가 변경되었습니다.';
        } catch (PDOException $e) {
            error_log('admin/inquiries.php 상태 변경 실패: ' . $e->getMessage());
            $errors[] = '회원 데이터베이스에 연결할 수 없습니다.';
        }
    }
}
 
try {
    $pdo_member = get_member_pdo();
 
    // AES_KEY는 common.php에서 정의 (.env의 AES_KEY 값)
 
    $inquiries = $pdo_member->query(
        "SELECT i.inquiry_id, i.title, i.content, i.status, i.created_at,
                AES_DECRYPT(u.email, '" . AES_KEY . "') AS email,
                AES_DECRYPT(u.name,  '" . AES_KEY . "') AS nickname
         FROM inquiry i
         JOIN users u ON u.id = i.member_id
         ORDER BY i.created_at DESC"
    )->fetchAll();
} catch (PDOException $e) {
    error_log('admin/inquiries.php 조회 실패: ' . $e->getMessage());
    $errors[] = '회원 데이터베이스에 연결할 수 없습니다. 네트워크/접속 정보를 확인해주세요.';
}
 
$page_title  = '관리자 - 문의 관리(씨네나잇)';
$active_menu = 'admin_inquiries';
require __DIR__ . '/../includes/header.php';
?>
 
<div class="topbar"><h1>문의 관리 (씨네나잇 OTT)</h1></div>
 
<div class="card">
    <?php if ($success): ?>
        <div style="color:var(--accent); font-size:13px; margin-bottom:12px;"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    <?php foreach ($errors as $e): ?>
        <div style="color:var(--danger); font-size:13px; margin-bottom:12px;"><?= htmlspecialchars($e) ?></div>
    <?php endforeach; ?>
 
    <?php if ($inquiries): ?>
    <table class="data-table">
        <thead>
            <tr>
                <th style="width:50px;">ID</th>
                <th style="width:120px;">작성자</th>
                <th>제목</th>
                <th style="width:90px;">상태</th>
                <th style="width:160px;">작성일</th>
                <th style="width:150px;"></th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($inquiries as $q): ?>
            <tr>
                <td><?= (int)$q['inquiry_id'] ?></td>
                <td><?= htmlspecialchars($q['nickname']) ?> (<?= htmlspecialchars($q['email']) ?>)</td>
                <td>
                    <?= htmlspecialchars($q['title']) ?>
                    <div style="font-size:12px; color:var(--muted, #888); margin-top:4px;">
                        <?= nl2br(htmlspecialchars($q['content'])) ?>
                    </div>
                </td>
                <td><?= htmlspecialchars($q['status']) ?></td>
                <td><?= htmlspecialchars(format_datetime($q['created_at'])) ?></td>
                <td>
                    <form method="post" style="display:inline;">
                        <input type="hidden" name="action" value="update_status">
                        <input type="hidden" name="id" value="<?= (int)$q['inquiry_id'] ?>">
                        <input type="hidden" name="status" value="<?= $q['status'] === '대기' ? '처리완료' : '대기' ?>">
                        <button type="submit" class="btn btn-ghost" style="padding:4px 10px; font-size:12px;">
                            <?= $q['status'] === '대기' ? '처리완료로 변경' : '대기로 되돌리기' ?>
                        </button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php elseif (!$errors): ?>
        <p style="font-size:13px; color:var(--muted, #888);">등록된 문의가 없습니다.</p>
    <?php endif; ?>
</div>
 
<?php require __DIR__ . '/../includes/footer.php'; ?>
