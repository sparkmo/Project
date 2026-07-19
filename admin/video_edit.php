<?php
/**
 * admin/video_edit.php
 * 씨네나잇 OTT 영상 메타데이터 수정 화면 (member_db.videos 직접 UPDATE).
 * 씨네나잇 웹서버에는 이 기능에 대응하는 API 자체가 없습니다 - 수정은
 * 오직 이 페이지(인트라넷 관리자)에서만 가능합니다.
 */
require_once __DIR__ . '/../common.php';
require_once __DIR__ . '/../auth.php';
require_admin();

$me = current_user($pdo);
$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$errors = [];
$success = '';

if ($id <= 0) {
    header('Location: /admin/videos.php');
    exit;
}

try {
    $pdo_member = get_member_pdo();
} catch (PDOException $e) {
    error_log('admin/video_edit.php 연결 실패: ' . $e->getMessage());
    $errors[] = '회원 데이터베이스에 연결할 수 없습니다.';
}

$stmt = isset($pdo_member) ? $pdo_member->prepare('SELECT * FROM videos WHERE id = ?') : null;
if ($stmt) {
    $stmt->execute([$id]);
    $video = $stmt->fetch();
} else {
    $video = null;
}

if (!$video && !$errors) {
    header('Location: /admin/videos.php');
    exit;
}

if ($video && $_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update') {
    $title       = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $video_type  = $_POST['video_type'] ?? 'youtube';
    $video_source= trim($_POST['video_source'] ?? '');
    $category    = trim($_POST['category'] ?? 'general');

    if ($title === '') {
        $errors[] = '제목을 입력해주세요.';
    }
    if ($video_source === '') {
        $errors[] = '영상 소스 값을 입력해주세요.';
    }
    if (!in_array($video_type, ['youtube', 'file', 'url'], true)) {
        $video_type = 'youtube';
    }

    if (!$errors) {
        $pdo_member->prepare(
            'UPDATE videos SET title=?, description=?, video_type=?, video_source=?, category=? WHERE id=?'
        )->execute([$title, $description, $video_type, $video_source, $category !== '' ? $category : 'general', $id]);

        log_action($pdo, $me['id'], 'admin_edit_video', 'video_id=' . $id);
        $success = '수정되었습니다.';

        // 최신값 다시 로드
        $stmt->execute([$id]);
        $video = $stmt->fetch();
    }
}

$page_title  = '관리자 - 영상 수정(씨네나잇)';
$active_menu = 'admin_videos';
require __DIR__ . '/../includes/header.php';
?>

<div class="topbar"><h1>영상 수정</h1></div>

<div class="card" style="max-width:640px;">
    <?php if ($success): ?>
        <div style="color:var(--accent); font-size:13px; margin-bottom:12px;"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    <?php foreach ($errors as $e): ?>
        <div style="color:var(--danger); font-size:13px; margin-bottom:12px;"><?= htmlspecialchars($e) ?></div>
    <?php endforeach; ?>

    <?php if ($video): ?>
    <form method="post">
        <input type="hidden" name="action" value="update">
        <input type="hidden" name="id" value="<?= (int)$video['id'] ?>">

        <div style="margin-bottom:12px;">
            <label style="display:block; font-size:13px; margin-bottom:4px;">제목</label>
            <input type="text" name="title" maxlength="200" value="<?= htmlspecialchars($video['title']) ?>"
                   style="width:100%; padding:8px; box-sizing:border-box;">
        </div>
        <div style="margin-bottom:12px;">
            <label style="display:block; font-size:13px; margin-bottom:4px;">설명</label>
            <textarea name="description" maxlength="2000" rows="4"
                      style="width:100%; padding:8px; box-sizing:border-box;"><?= htmlspecialchars($video['description'] ?? '') ?></textarea>
        </div>
        <div style="margin-bottom:12px;">
            <label style="display:block; font-size:13px; margin-bottom:4px;">카테고리</label>
            <input type="text" name="category" maxlength="50" value="<?= htmlspecialchars($video['category']) ?>"
                   style="width:100%; padding:8px; box-sizing:border-box;">
        </div>
        <div style="margin-bottom:12px;">
            <label style="display:block; font-size:13px; margin-bottom:4px;">영상 소스 종류</label>
            <select name="video_type" style="width:100%; padding:8px;">
                <?php foreach (['youtube'=>'유튜브','file'=>'직접 업로드','url'=>'외부 URL'] as $k=>$l): ?>
                    <option value="<?= $k ?>" <?= $video['video_type'] === $k ? 'selected' : '' ?>><?= $l ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div style="margin-bottom:16px;">
            <label style="display:block; font-size:13px; margin-bottom:4px;">영상 소스 값</label>
            <input type="text" name="video_source" maxlength="500" value="<?= htmlspecialchars($video['video_source']) ?>"
                   style="width:100%; padding:8px; box-sizing:border-box;">
            <p style="font-size:12px; color:var(--muted, #888); margin-top:4px;">
                file 타입인 경우 씨네나잇 웹서버 uploads/ 폴더의 실제 파일명과 반드시 일치해야 합니다
                (이 페이지에서 파일 자체를 업로드/교체하지는 않습니다).
            </p>
        </div>

        <button type="submit" class="btn-write">수정 완료</button>
        <a class="btn btn-ghost" href="/admin/videos.php" style="margin-left:8px;">목록으로</a>
    </form>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
