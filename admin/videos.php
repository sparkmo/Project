<?php
/**
 * admin/videos.php
 * ott_db.video 목록 + 삭제 화면 (video는 회원과 관계 없음, 독립 테이블).
 */
require_once __DIR__ . '/../common.php';
require_once __DIR__ . '/../auth.php';
require_admin();

$me = current_user($pdo);

$errors = [];
$success = '';
$videos = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    $id = (int)($_POST['id'] ?? 0);
    try {
        $pdo_member = get_member_pdo();
        $stmt = $pdo_member->prepare('SELECT title FROM video WHERE video_id = ?');
        $stmt->execute([$id]);
        $target = $stmt->fetch();

        if ($target) {
            $pdo_member->prepare('DELETE FROM video WHERE video_id = ?')->execute([$id]);
            log_action($pdo, $me['id'], 'admin_delete_video', 'video_id=' . $id . ' title=' . $target['title']);
            $success = '영상이 삭제되었습니다.';
        } else {
            $errors[] = '존재하지 않는 영상입니다.';
        }
    } catch (PDOException $e) {
        error_log('admin/videos.php 삭제 실패: ' . $e->getMessage());
        $errors[] = '회원 데이터베이스에 연결할 수 없습니다.';
    }
}

try {
    $pdo_member = get_member_pdo();
    $videos = $pdo_member->query('SELECT * FROM video ORDER BY upload_date DESC')->fetchAll();
} catch (PDOException $e) {
    error_log('admin/videos.php 조회 실패: ' . $e->getMessage());
    $errors[] = '회원 데이터베이스에 연결할 수 없습니다. 네트워크/접속 정보를 확인해주세요.';
}

$page_title  = '관리자 - 영상 관리(씨네나잇)';
$active_menu = 'admin_videos';
require __DIR__ . '/../includes/header.php';
?>

<div class="topbar"><h1>영상 관리 (씨네나잇 OTT)</h1></div>

<div class="card">
    <p style="font-size:13px; color:var(--muted, #888); margin-bottom:16px;">
        ott_db.video 테이블은 회원과 관계를 갖지 않으며, 영상 등록/수정/삭제는
        관리자만 이 페이지에서 수행합니다.
    </p>

    <?php if ($success): ?>
        <div style="color:var(--accent); font-size:13px; margin-bottom:12px;"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    <?php foreach ($errors as $e): ?>
        <div style="color:var(--danger); font-size:13px; margin-bottom:12px;"><?= htmlspecialchars($e) ?></div>
    <?php endforeach; ?>

    <?php if ($videos): ?>
    <table class="data-table">
        <thead>
            <tr>
                <th style="width:50px;">ID</th>
                <th>제목</th>
                <th style="width:90px;">카테고리</th>
                <th style="width:160px;">등록일</th>
                <th style="width:150px;"></th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($videos as $v): ?>
            <tr>
                <td><?= (int)$v['video_id'] ?></td>
                <td><?= htmlspecialchars($v['title']) ?></td>
                <td><?= htmlspecialchars($v['category']) ?></td>
                <td><?= htmlspecialchars(format_datetime($v['upload_date'])) ?></td>
                <td>
                    <a class="btn btn-ghost" style="padding:4px 10px; font-size:12px;" href="/admin/video_edit.php?id=<?= (int)$v['video_id'] ?>">수정</a>
                    <form method="post" style="display:inline;" onsubmit="return confirm('정말 삭제하시겠습니까?');">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= (int)$v['video_id'] ?>">
                        <button type="submit" class="btn btn-ghost" style="padding:4px 10px; font-size:12px;">삭제</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php elseif (!$errors): ?>
        <p style="font-size:13px; color:var(--muted, #888);">등록된 영상이 없습니다.</p>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
