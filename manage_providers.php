<?php
require_once __DIR__ . "/includes/layout.php";
require_role([BAMS_ADMIN]);

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (csrf_ok()) {
        $id = (int)($_POST["provider_id"] ?? 0);
        $spec = trim($_POST["specialization"] ?? "");
        $contact = trim($_POST["contact_number"] ?? "");
        $avail = trim($_POST["availability"] ?? "");
        $st = $conn->prepare("UPDATE service_providers SET specialization = ?, contact_number = ?, availability = ? WHERE provider_id = ?");
        $st->bind_param("sssi", $spec, $contact, $avail, $id);
        $st->execute();
        flash_set("success", "Provider details were saved.");
    } else {
        flash_set("error", "Your session expired. Please try again.");
    }
    header("Location: manage_providers.php");
    exit();
}

$q = trim($_GET["q"] ?? "");
$sql = "SELECT p.*, u.username, (SELECT COUNT(*) FROM appointments a WHERE a.provider_id = p.provider_id) AS appts
        FROM service_providers p LEFT JOIN users u ON p.user_id = u.user_id";
if ($q !== "") {
    $sql .= " WHERE p.first_name LIKE ? OR p.last_name LIKE ? OR p.specialization LIKE ? OR u.username LIKE ?";
}
$sql .= " ORDER BY p.provider_id ASC";
$st = $conn->prepare($sql);
if ($q !== "") {
    $like = "%$q%";
    $st->bind_param("ssss", $like, $like, $like, $like);
}
$st->execute();
$rows = $st->get_result()->fetch_all(MYSQLI_ASSOC);

layout_start("Service Providers", "manage_providers.php");
?>
<div class="page-head">
    <div><div class="eyebrow">Administration</div><h1>Service Providers</h1><p>The people who serve customers. Edit their specialization and availability.</p></div>
    <div class="actions"><a class="btn" href="manage_users.php">+ Add provider account</a></div>
</div>
<div class="card">
    <div class="card-head">
        <div class="card-title"><?php echo count($rows); ?> provider<?php echo count($rows) === 1 ? "" : "s"; ?></div>
        <form class="search-bar" method="GET"><input name="q" placeholder="Search name or specialization" value="<?php echo e($q); ?>"><button class="btn btn-sm">Search</button><?php if ($q !== ""): ?><a class="btn btn-ghost btn-sm" href="manage_providers.php">Clear</a><?php endif; ?></form>
    </div>
    <?php if (!$rows): ?>
        <div class="empty"><div class="big">🧑‍💼</div><strong>No providers found</strong></div>
    <?php else: ?>
    <div class="table-wrap"><table>
        <thead><tr><th>Provider</th><th>Specialization</th><th>Contact</th><th>Availability</th><th>Appts</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($rows as $r): $name = trim($r["first_name"] . " " . $r["last_name"]); $fid = "f" . (int)$r["provider_id"]; ?>
            <tr>
                <td><div class="cell-user"><div class="mini-avatar"><?php echo e(initial($name)); ?></div>
                    <div><div class="cell-title"><?php echo e($name); ?></div><div class="cell-sub">@<?php echo e($r["username"]); ?> &middot; ID <?php echo (int)$r["provider_id"]; ?></div></div></div></td>
                <td><input form="<?php echo $fid; ?>" name="specialization" value="<?php echo e($r["specialization"]); ?>" style="min-width:150px"></td>
                <td><input form="<?php echo $fid; ?>" name="contact_number" value="<?php echo e($r["contact_number"]); ?>" style="min-width:130px"></td>
                <td><input form="<?php echo $fid; ?>" name="availability" value="<?php echo e($r["availability"]); ?>" style="min-width:150px"></td>
                <td><span class="badge b-info"><?php echo (int)$r["appts"]; ?></span></td>
                <td><form id="<?php echo $fid; ?>" method="POST"><?php echo csrf_field(); ?><input type="hidden" name="provider_id" value="<?php echo (int)$r["provider_id"]; ?>"><button class="btn btn-sm">Save</button></form></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table></div>
    <?php endif; ?>
</div>
<?php layout_end(); ?>
