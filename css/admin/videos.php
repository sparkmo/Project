<?php
/**
 * admin/videos.php
 * 씨네나잇 OTT 서비스에 등록된 영상 목록 + 삭제 화면.
 * member_db(회원DB, 3308)에 videos 테이블도 함께 두었으므로, 씨네나잇
 * 웹서버는 이 데이터를 직접 조회/수정할 수 없고 이 관리자 페이지에서만
 * 수정/삭제가 가능합니다. (관리자 등급만 접근 가능 - require_admin())
 */
require_once __DIR__ . '/../common.php';
require_once __DIR__ . '/../auth.php';
require_admin();

$me = current_user($pdo);

$errors = [];
$success = '';
$videos = [];

// 삭제 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    $id = (int)($_POST['id'] ?? 0);
    try {
        $pdo_member = get_member_pdo();
        $stmt = $pdo_member->prepare('SELECT video_type, video_source, title FROM videos WHERE id = ?');
        $stmt->execute([$id]);
        $target = $stmt->fetch();

        if ($target) {
            $pdo_member->prepare('DELETE FROM videos WHERE id = ?')->execute([$id]);
            log_action($pdo, $me['id'], 'admin_delete_video', 'video_id=' . $id . ' title=' . $target['title']);
            $success = '영상이 삭제되었습니다.';
            if ($target['video_type'] === 'file') {
                $success .= ' (참고: 실제 파일은 씨네나잇 웹서버 uploads/ 폴더에 남아있을 수 있습니다 - 인트라넷 서버에서 원격으로 지울 수 없습니다.)';
            }
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
    $videos = $pdo_member->query(
        'SELECT v.*, m.nickname, m.username AS writer_username
         FROM videos v JOIN members m ON m.id = v.writer_id
         ORDER BY v.reg_date DESC'
    )->fetchAll();
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
        씨네나잇에 등록된 영상 목록입니다. 이 데이터는 회원 데이터베이스(member_db, 내부망 전용)에
        함께 저장되며, 씨네나잇 웹서버는 업로드(등록)만 API로 요청할 수 있고
        수정/삭제는 이 페이지에서만 가능합니다.
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
                <th style="width:90px;">종류</th>
                <th style="width:90px;">카테고리</th>
                <th>등록자</th>
                <th style="width:70px;">조회수</th>
                <th style="width:160px;">등록일</th>
                <th style="width:150px;"></th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($videos as $v): ?>
            <tr>
                <td><?= (int)$v['id'] ?></td>
                <td><?= htmlspecialchars($v['title']) ?></td>
                <td><?= htmlspecialchars($v['video_type']) ?></td>
                <td><?= htmlspecialchars($v['category']) ?></td>
                <td><?= htmlspecialchars($v['nickname']) ?> (@<?= htmlspecialchars($v['writer_username']) ?>)</td>
                <td><?= (int)$v['view_count'] ?></td>
                <td><?= htmlspecialchars(format_datetime($v['reg_date'])) ?></td>
                <td>
                    <a class="btn btn-ghost" style="padding:4px 10px; font-size:12px;" href="/admin/video_edit.php?id=<?= (int)$v['id'] ?>">수정</a>
                    <form method="post" style="display:inline;" onsubmit="return confirm('정말 삭제하시겠습니까?');">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= (int)$v['id'] ?>">
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
