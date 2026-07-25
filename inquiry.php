<?php
require_once __DIR__ . '/common.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/error.php';

require_login();

$page_title = '문의하기';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title   = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');

    if ($title === '' || $content === '') {
        alert_back('제목과 내용을 모두 입력해주세요.');
    }

    $data = intranet_api_post(INTRANET_API_INQUIRY, [
        'username' => $_SESSION['username'],
        'title'    => $title,
        'content'  => $content,
    ]);

    if (!($data['ok'] ?? false)) {
        alert_back($data['error'] ?? '문의 등록에 실패했습니다.');
    }

    alert_redirect('문의가 등록되었습니다.', 'inquiry.php');
}

// 회원 DB는 이 서버에서 직접 접속할 수 없으므로, 인트라넷 API로 조회를 위임한다.
$inquiry_data = intranet_api_get(INTRANET_API_INQUIRY, ['username' => $_SESSION['username']]);
$my_inquiries = $inquiry_data['inquiries'] ?? [];

include __DIR__ . '/includes/header.php';
?>

<h2 class="section-title" style="margin-top:0;">문의하기</h2>

<div class="form-box">
    <form method="post">
        <div class="field">
            <label>제목</label>
            <input type="text" name="title" required maxlength="100">
        </div>
        <div class="field">
            <label>내용</label>
            <textarea name="content" required rows="5" maxlength="2000"></textarea>
        </div>
        <button type="submit" class="btn-submit">문의 등록</button>
    </form>
</div>

<h2 class="section-title">내 문의 내역 (<?= count($my_inquiries) ?>)</h2>

<?php if (empty($my_inquiries)): ?>
    <p class="empty-msg">등록한 문의가 없습니다.</p>
<?php else: ?>
    <table class="data-table">
        <thead>
            <tr>
                <th>제목</th>
                <th style="width:90px;">상태</th>
                <th style="width:160px;">작성일</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($my_inquiries as $q): ?>
            <tr>
                <td><?= htmlspecialchars($q['title']) ?></td>
                <td><?= htmlspecialchars($q['status']) ?></td>
                <td><?= htmlspecialchars($q['created_at']) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>
