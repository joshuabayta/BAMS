<?php
require_once __DIR__ . "/includes/layout.php";
require_role([BAMS_ADMIN]);

$q = trim($_GET["q"] ?? "");
$sql = "SELECT c.*, u.username, (SELECT COUNT(*) FROM appointments a WHERE a.customer_id = c.customer_id) AS appts
        FROM customers c LEFT JOIN users u ON c.user_id = u.user_id";
if ($q !== "") {
    $sql .= " WHERE c.first_name LIKE ? OR c.last_name LIKE ? OR c.email LIKE ? OR c.contact_number LIKE ? OR u.username LIKE ?";
}
$sql .= " ORDER BY c.customer_id ASC";
$st = $conn->prepare($sql);
if ($q !== "") {
    $like = "%$q%";
    $st->bind_param("sssss", $like, $like, $like, $like, $like);
}
$st->execute();
$rows = $st->get_result()->fetch_all(MYSQLI_ASSOC);

layout_start("Customers", "manage_customers.php");
?>
<div class="page-head">
    <div><div class="eyebrow">Administration</div><h1>Customers</h1><p>Everyone who can book appointments.</p></div>
    <div class="actions"><a class="btn" href="manage_users.php">+ Add customer account</a></div>
</div>
<div class="card">
    <div class="card-head">
        <div class="card-title"><?php echo count($rows); ?> customer<?php echo count($rows) === 1 ? "" : "s"; ?></div>
        <form class="search-bar" method="GET"><input name="q" placeholder="Search name, email, phone" value="<?php echo e($q); ?>"><button class="btn btn-sm">Search</button><?php if ($q !== ""): ?><a class="btn btn-ghost btn-sm" href="manage_customers.php">Clear</a><?php endif; ?></form>
    </div>
    <?php if (!$rows): ?>
        <div class="empty"><div class="big">🧑</div><strong>No customers found</strong>Customer records appear here after they register.</div>
    <?php else: ?>
    <div class="table-wrap"><table>
        <thead><tr><th>Customer</th><th>Contact</th><th>Address</th><th>Appointments</th><th>Joined</th></tr></thead>
        <tbody>
        <?php foreach ($rows as $r): $name = trim($r["first_name"] . " " . $r["last_name"]); ?>
            <tr>
                <td><div class="cell-user"><div class="mini-avatar"><?php echo e(initial($name)); ?></div>
                    <div><div class="cell-title"><?php echo e($name); ?></div><div class="cell-sub">@<?php echo e($r["username"]); ?> &middot; ID <?php echo (int)$r["customer_id"]; ?></div></div></div></td>
                <td><div><?php echo e($r["contact_number"] ?: "-"); ?></div><div class="cell-sub"><?php echo e($r["email"] ?: "-"); ?></div></td>
                <td><?php echo e($r["address"] ?: "-"); ?></td>
                <td><span class="badge b-info"><?php echo (int)$r["appts"]; ?></span></td>
                <td><?php echo e($r["created_at"] ? date("M j, Y", strtotime($r["created_at"])) : "-"); ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table></div>
    <?php endif; ?>
</div>
<?php layout_end(); ?>
