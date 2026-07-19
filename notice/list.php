<?php
require_once __DIR__ . '/../common.php';
require_once __DIR__ . '/../auth.php';
require_login();

$per_page = 15;
$page = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * $per_page;

$total = (int)$pdo->query('SELECT COUNT(*) FROM notices')->fetchColumn();
$total_pages = max(1, (int)ceil($total / $per_page));

$stmt = $pdo->prepare(
    'SELECT n.id, n.title, n.is_pinned, n.view_count, n.created_at, u.name AS author_name
     FROM notices n JOIN users u ON u.id = n.author_id
     ORDER BY n.is_pinned DESC, n.created_at DESC
     LIMIT :limit OFFSET :offset'
);
$stmt->bindValue(':limit', $per_page, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$notices = $stmt->fetchAll();

$page_title  = '공지사항';
$active_menu = 'notice';
require __DIR__ . '/../includes/header.php';
?>

<div class="topbar">
    <h1>공지사항</h1>
    <a href="/notice/write.php" class="btn btn-primary">글쓰기</a>
</div>

<div class="card">
    <?php if (empty($notices)): ?>
        <div class="empty-state">등록된 공지사항이 없습니다.</div>
    <?php else: ?>
        <table class="data-table">
            <thead>
                <tr><th style="width:60px;">번호</th><th>제목</th><th style="width:100px;">작성자</th><th style="width:100px;">조회수</th><th style="width:140px;">작성일</th></tr>
            </thead>
            <tbody>
            <?php foreach ($notices as $n): ?>
                <tr>
                    <td><?= (int)$n['id'] ?></td>
                    <td>
                        <?php if ($n['is_pinned']): ?><span class="badge badge-pinned">고정</span> <?php endif; ?>
                        <a href="/notice/view.php?id=<?= (int)$n['id'] ?>"><?= htmlspecialchars($n['title']) ?></a>
                    </td>
                    <td><?= htmlspecialchars($n['author_name']) ?></td>
                    <td><?= (int)$n['view_count'] ?></td>
                    <td><?= htmlspecialchars(format_datetime($n['created_at'])) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

        <div class="pagination">
            <?php for ($p = 1; $p <= $total_pages; $p++): ?>
                <?php if ($p === $page): ?>
                    <span class="current"><?= $p ?></span>
                <?php else: ?>
                    <a href="?page=<?= $p ?>"><?= $p ?></a>
                <?php endif; ?>
            <?php endfor; ?>
        </div>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
