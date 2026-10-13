<?php
require_once __DIR__ . "/includes/layout.php";
require_once __DIR__ . "/includes/data.php";
require_role([BAMS_PROVIDER]);

$uid = (int)$_SESSION["user_id"];
$stmt = $conn->prepare("SELECT provider_id, first_name, last_name FROM service_providers WHERE user_id = ?");
$stmt->bind_param("i", $uid);
$stmt->execute();
$provider = $stmt->get_result()->fetch_assoc();

$rows = [];
if ($provider) {
    $pid = (int)$provider["provider_id"];
    $rows = appointment_rows($conn, "a.provider_id = $pid", "a.appointment_date ASC, a.appointment_time ASC");
}
$today = date("Y-m-d");
$filter = $_GET["show"] ?? "all";
$isDone = fn($r) => in_array(strtolower($r["status"]), ["completed", "cancelled", "canceled"], true);
$total = count($rows);
$toConfirm = count(array_filter($rows, fn($r) => in_array($r["status"], ["Booked", "Rescheduled"], true)));
$upcoming = count(array_filter($rows, fn($r) => !$isDone($r) && $r["appointment_date"] >= $today));
$completed = count(array_filter($rows, fn($r) => $r["status"] === "Completed"));
$shown = array_values(array_filter($rows, function ($r) use ($filter, $today, $isDone) {
    if ($filter === "confirm") return in_array($r["status"], ["Booked", "Rescheduled"], true);
    if ($filter === "upcoming") return !$isDone($r) && $r["appointment_date"] >= $today;
    if ($filter === "done") return $r["status"] === "Completed";
    if ($filter === "cancelled") return in_array(strtolower($r["status"]), ["cancelled", "canceled"], true);
    return true;
}));

layout_start("My Appointments", "provider_appointments.php");
?>
<div class="page-head">
    <div><div class="eyebrow">Service provider</div><h1>My Appointments</h1><p>Review your bookings, confirm them and mark them as completed.</p></div>
</div>
<?php if (!$provider): ?><div class="alert alert-warn">Your provider profile was not found. Please contact an administrator.</div><?php endif; ?>
<div class="stats">
    <div class="stat"><div class="stat-ico">📋</div><div class="stat-label">Total</div><div class="stat-value"><?php echo $total; ?></div></div>
    <div class="stat"><div class="stat-ico">⏳</div><div class="stat-label">To confirm</div><div class="stat-value"><?php echo $toConfirm; ?></div></div>
    <div class="stat"><div class="stat-ico">📆</div><div class="stat-label">Upcoming</div><div class="stat-value"><?php echo $upcoming; ?></div></div>
    <div class="stat"><div class="stat-ico">🏁</div><div class="stat-label">Completed</div><div class="stat-value"><?php echo $completed; ?></div></div>
</div>
<div class="filters" style="margin-bottom:16px">
    <?php foreach (["all" => "All", "confirm" => "To confirm", "upcoming" => "Upcoming", "done" => "Completed", "cancelled" => "Cancelled"] as $k => $l): ?>
        <a class="chip<?php echo $filter === $k ? " active" : ""; ?>" href="provider_appointments.php?show=<?php echo $k; ?>"><?php echo $l; ?></a>
    <?php endforeach; ?>
</div>
<div class="card">
    <div class="card-head"><div class="card-title"><?php echo count($shown); ?> record<?php echo count($shown) === 1 ? "" : "s"; ?></div></div>
    <?php if (!$shown): ?>
        <div class="empty"><div class="big">📅</div><strong>No appointments here</strong>New bookings assigned to you will appear in this list.</div>
    <?php else: ?>
    <div class="table-wrap"><table>
        <thead><tr><th>ID</th><th>Customer</th><th>Service</th><th>Date</th><th>Time</th><th>Status</th><th>Actions</th></tr></thead>
        <tbody>
        <?php foreach ($shown as $r): $finished = $isDone($r); ?>
            <tr>
                <td>#<?php echo (int)$r["appointment_id"]; ?></td>
                <td><div class="cell-user"><div class="mini-avatar"><?php echo e(initial($r["customer_name"])); ?></div><div class="cell-title"><?php echo e($r["customer_name"]); ?></div></div></td>
                <td><?php echo e($r["service_name"]); ?></td>
                <td><?php echo e(fmt_date($r["appointment_date"])); ?></td>
                <td><?php echo e(fmt_time($r["appointment_time"])); ?></td>
                <td><?php echo status_badge((string)$r["status"]); ?></td>
                <td>
                    <?php if ($finished): ?><span class="cell-sub">No action</span>
                    <?php else: ?>
                    <div class="row-actions">
                        <?php if (in_array($r["status"], ["Booked", "Rescheduled"], true)): ?>
                        <form method="POST" action="update_status.php"><?php echo csrf_field(); ?><input type="hidden" name="id" value="<?php echo (int)$r["appointment_id"]; ?>"><input type="hidden" name="status" value="Confirmed"><input type="hidden" name="quick" value="1"><button class="btn btn-good btn-sm">Confirm</button></form>
                        <?php endif; ?>
                        <?php if ($r["status"] === "Confirmed" || $r["status"] === "In Progress"): ?>
                        <form method="POST" action="update_status.php"><?php echo csrf_field(); ?><input type="hidden" name="id" value="<?php echo (int)$r["appointment_id"]; ?>"><input type="hidden" name="status" value="Completed"><input type="hidden" name="quick" value="1"><button class="btn btn-sm">Complete</button></form>
                        <?php endif; ?>
                        <a class="btn btn-ghost btn-sm" href="update_status.php?id=<?php echo (int)$r["appointment_id"]; ?>">More</a>
                    </div>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table></div>
    <?php endif; ?>
</div>
<?php layout_end(); ?>
