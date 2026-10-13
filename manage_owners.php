<?php
require_once __DIR__ . "/includes/layout.php";
require_role([BAMS_ADMIN]);

$q = trim($_GET["q"] ?? "");
$sql = "SELECT user_id, username, created_at FROM users WHERE role = 'Business Owner'";
if ($q !== "") {
    $sql .= " AND username LIKE ?";
}
$sql .= " ORDER BY created_at DESC, user_id DESC";
$st = $conn->prepare($sql);
if ($q !== "") {
    $like = "%$q%";
    $st->bind_param("s", $like);
}
$st->execute();
$rows = $st->get_result()->fetch_all(MYSQLI_ASSOC);

layout_start("Business Owners", "manage_owners.php");
?>
<div class="page-head">
    <div><div class="eyebrow">Administration</div><h1>Business Owners</h1><p>Accounts that can see the business overview and reports.</p></div>
    <div class="actions"><a class="btn" href="manage_users.php">+ Add owner account</a></div>
</div>
<div class="card">
    <div class="card-head">
        <div class="card-title"><?php echo count($rows); ?> owner<?php echo count($rows) === 1 ? "" : "s"; ?></div>
        <form class="search-bar" method="GET"><input name="q" placeholder="Search username" value="<?php echo e($q); ?>"><button class="btn btn-sm">Search</button><?php if ($q !== ""): ?><a class="btn btn-ghost btn-sm" href="manage_owners.php">Clear</a><?php endif; ?></form>
    </div>
    <?php if (!$rows): ?>
        <div class="empty"><div class="big">🏢</div><strong>No business owners found</strong></div>
    <?php else: ?>
    <div class="table-wrap"><table>
        <thead><tr><th>Owner</th><th>Role</th><th>Created</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($rows as $r): ?>
            <tr>
                <td><div class="cell-user"><div class="mini-avatar"><?php echo e(initial($r["username"])); ?></div><div><div class="cell-title"><?php echo e($r["username"]); ?></div><div class="cell-sub">ID <?php echo (int)$r["user_id"]; ?></div></div></div></td>
                <td><span class="badge b-wait">Business Owner</span></td>
                <td><?php echo e($r["created_at"] ? date("M j, Y", strtotime($r["created_at"])) : "-"); ?></td>
                <td><a class="btn btn-ghost btn-sm" href="manage_users.php?q=<?php echo urlencode($r["username"]); ?>">Manage account</a></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table></div>
    <?php endif; ?>
</div>
<?php layout_end(); ?>
