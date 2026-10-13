<?php
require_once __DIR__ . "/includes/layout.php";
require_once __DIR__ . "/includes/data.php";
require_role([BAMS_ADMIN, BAMS_OWNER]);

$today = date("Y-m-d");
$sql = "SELECT p.provider_id, p.first_name, p.last_name, p.specialization, p.contact_number, p.availability, p.created_at,
               (SELECT COUNT(*) FROM appointments a WHERE a.provider_id = p.provider_id) AS total,
               (SELECT COUNT(*) FROM appointments a WHERE a.provider_id = p.provider_id AND a.appointment_date = '$today' AND a.status NOT IN ('Cancelled','Canceled')) AS today_count,
               (SELECT COUNT(*) FROM appointments a WHERE a.provider_id = p.provider_id AND a.status = 'Completed') AS done
        FROM service_providers p ORDER BY p.provider_id ASC";
$staff = $conn->query($sql)->fetch_all(MYSQLI_ASSOC);
layout_start("Staff", "staff.php");
?>
<div class="page-head">
    <div><div class="eyebrow">Team</div><h1>Staff Management</h1><p>Your service providers, their specialization and workload.</p></div>
    <?php if (current_role() === BAMS_ADMIN): ?><div class="actions"><a class="btn" href="manage_providers.php">Edit providers</a></div><?php endif; ?>
</div>
<div class="stats" style="grid-template-columns:repeat(3,minmax(0,1fr))">
    <div class="stat"><div class="stat-ico">🧑‍💼</div><div class="stat-label">Staff members</div><div class="stat-value"><?php echo count($staff); ?></div></div>
    <div class="stat"><div class="stat-ico">🕒</div><div class="stat-label">Working today</div><div class="stat-value"><?php echo count(array_filter($staff, fn($s) => (int)$s["today_count"] > 0)); ?></div></div>
    <div class="stat"><div class="stat-ico">🏁</div><div class="stat-label">Completed visits</div><div class="stat-value"><?php echo array_sum(array_map(fn($s) => (int)$s["done"], $staff)); ?></div></div>
</div>
<div class="card">
    <div class="card-head"><div class="card-title">Service providers</div><div class="search-bar"><input id="staffSearch" type="search" placeholder="Search name, specialization, contact..."></div></div>
    <?php if (!$staff): ?>
        <div class="empty"><div class="big">👥</div><strong>No staff yet</strong></div>
    <?php else: ?>
    <div class="table-wrap"><table id="staffTable">
        <thead><tr><th>Provider</th><th>Specialization</th><th>Contact</th><th>Availability</th><th>Today</th><th>Total</th><th>Added</th></tr></thead>
        <tbody>
        <?php foreach ($staff as $s): $n = trim($s["first_name"] . " " . $s["last_name"]); ?>
            <tr data-search="<?php echo e(strtolower($n . " " . $s["specialization"] . " " . $s["contact_number"] . " " . $s["availability"])); ?>">
                <td><div class="cell-user"><div class="mini-avatar"><?php echo e(initial($n)); ?></div><div><div class="cell-title"><?php echo e($n); ?></div><div class="cell-sub">ID <?php echo (int)$s["provider_id"]; ?></div></div></div></td>
                <td><?php echo e($s["specialization"] ?: "General Service"); ?></td>
                <td><?php echo e($s["contact_number"] ?: "Not provided"); ?></td>
                <td><span class="badge b-good"><?php echo e($s["availability"] ?: "Not specified"); ?></span></td>
                <td><span class="badge <?php echo (int)$s["today_count"] ? "b-wait" : "b-muted"; ?>"><?php echo (int)$s["today_count"]; ?></span></td>
                <td><?php echo (int)$s["total"]; ?></td>
                <td><?php echo e($s["created_at"] ? date("M j, Y", strtotime($s["created_at"])) : "-"); ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table></div>
    <?php endif; ?>
</div>
<script>
document.getElementById("staffSearch")?.addEventListener("input", function () {
    var q = this.value.toLowerCase().trim();
    document.querySelectorAll("#staffTable tbody tr").forEach(function (tr) { tr.style.display = tr.dataset.search.indexOf(q) === -1 ? "none" : ""; });
});
</script>
<?php layout_end(); ?>
