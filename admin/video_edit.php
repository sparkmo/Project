<?php
/**
 * admin/video_edit.php
 * ott_db.video 메타데이터 수정 화면.
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

$stmt = isset($pdo_member) ? $pdo_member->prepare('SELECT * FROM video WHERE video_id = ?') : null;
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
    $thumbnail   = trim($_POST['thumbnail'] ?? '');
    $category    = trim($_POST['category'] ?? 'general');
    $video_type   = trim($_POST['video_type'] ?? 'youtube');   // 추가
    $video_source = trim($_POST['video_source'] ?? '');        // 추가
    if ($title === '') {
        $errors[] = '제목을 입력해주세요.';
    }
    if (!in_array($video_type, ['youtube', 'file', 'url'], true)) {   // 추가
        $video_type = 'youtube';
    }
    if ($video_source === '') {                                       // 추가
        $errors[] = '영상 소스(video_source)를 입력해주세요.';
    }
    

    if (!$errors) {
        $pdo_member->prepare(
            'UPDATE video SET title=?, description=?, thumbnail=?, category=?, video_type=?, video_source=? WHERE video_id=?'
        )->execute([
            $title, $description,
            $thumbnail !== '' ? $thumbnail : null,
            $category !== '' ? $category : 'general',
            $video_type, $video_source,   // 추가
            $id
        ]);

        log_action($pdo, $me['id'], 'admin_edit_video', 'video_id=' . $id);
        $success = '수정되었습니다.';

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
        <input type="hidden" name="id" value="<?= (int)$video['video_id'] ?>">

        <div style="margin-bottom:12px;">
            <label style="display:block; font-size:13px; margin-bottom:4px;">제목</label>
            <input type="text" name="title" maxlength="100" value="<?= htmlspecialchars($video['title']) ?>"
                   style="width:100%; padding:8px; box-sizing:border-box;">
        </div>
        <div style="margin-bottom:12px;">
            <label style="display:block; font-size:13px; margin-bottom:4px;">설명</label>
            <textarea name="description" rows="4"
                      style="width:100%; padding:8px; box-sizing:border-box;"><?= htmlspecialchars($video['description'] ?? '') ?></textarea>
        </div>
        <div style="margin-bottom:12px;">
            <label style="display:block; font-size:13px; margin-bottom:4px;">카테고리</label>
            <input type="text" name="category" maxlength="50" value="<?= htmlspecialchars($video['category']) ?>"
                   style="width:100%; padding:8px; box-sizing:border-box;">
        </div>
        <div style="margin-bottom:16px;">
            <label style="display:block; font-size:13px; margin-bottom:4px;">썸네일 경로</label>
            <input type="text" name="thumbnail" maxlength="255" value="<?= htmlspecialchars($video['thumbnail'] ?? '') ?>"
                   style="width:100%; padding:8px; box-sizing:border-box;">
        </div>

        <div style="margin-bottom:12px;">
            <label style="display:block; font-size:13px; margin-bottom:4px;">영상 타입</label>
            <select name="video_type" style="width:100%; padding:8px; box-sizing:border-box;">
                <?php foreach (['youtube' => '유튜브', 'file' => '파일', 'url' => '외부 URL'] as $val => $label): ?>
                    <option value="<?= $val ?>" <?= ($video['video_type'] ?? 'youtube') === $val ? 'selected' : '' ?>>
                        <?= $label ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div style="margin-bottom:12px;">
            <label style="display:block; font-size:13px; margin-bottom:4px;">영상 소스 (유튜브ID / 파일명 / URL)</label>
            <input type="text" name="video_source" maxlength="255" value="<?= htmlspecialchars($video['video_source'] ?? '') ?>"
                   style="width:100%; padding:8px; box-sizing:border-box;">
        </div>

        <button type="submit" class="btn-write">수정 완료</button>
        <a class="btn btn-ghost" href="/admin/videos.php" style="margin-left:8px;">목록으로</a>
    </form>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
