<?php
require_once __DIR__ . "/includes/layout.php";
require_once __DIR__ . "/includes/data.php";
require_role([BAMS_PROVIDER]);

$uid = (int)$_SESSION["user_id"];
$stmt = $conn->prepare("SELECT provider_id, first_name, availability, specialization FROM service_providers WHERE user_id = ?");
$stmt->bind_param("i", $uid);
$stmt->execute();
$provider = $stmt->get_result()->fetch_assoc();

$rows = [];
if ($provider) {
    $pid = (int)$provider["provider_id"];
    $rows = appointment_rows($conn, "a.provider_id = $pid AND a.status NOT IN ('Cancelled','Canceled')", "a.appointment_date ASC, a.appointment_time ASC");
}
$today = date("Y-m-d");
$upcoming = array_values(array_filter($rows, fn($r) => $r["appointment_date"] >= $today && $r["status"] !== "Completed"));
$past = array_reverse(array_values(array_filter($rows, fn($r) => $r["appointment_date"] < $today || $r["status"] === "Completed")));
$days = [];
foreach ($upcoming as $r) {
    $days[$r["appointment_date"]][] = $r;
}
$nextWeek = count(array_filter($upcoming, fn($r) => $r["appointment_date"] <= date("Y-m-d", strtotime("+7 days"))));

layout_start("My Schedule", "my_schedule.php");
?>
<div class="page-head">
    <div><div class="eyebrow">Service provider</div><h1>My Schedule</h1><p>Your upcoming appointments, day by day.</p></div>
    <div class="actions"><a class="btn btn-ghost" href="calendar.php">Open calendar</a><a class="btn" href="provider_appointments.php">Manage appointments</a></div>
</div>
<?php if (!$provider): ?><div class="alert alert-warn">Your provider profile was not found. Please contact an administrator.</div><?php endif; ?>
<div class="stats">
    <div class="stat"><div class="stat-ico">📆</div><div class="stat-label">Upcoming</div><div class="stat-value"><?php echo count($upcoming); ?></div></div>
    <div class="stat"><div class="stat-ico">🗓️</div><div class="stat-label">Next 7 days</div><div class="stat-value"><?php echo $nextWeek; ?></div></div>
    <div class="stat"><div class="stat-ico">🏁</div><div class="stat-label">Past / completed</div><div class="stat-value"><?php echo count($past); ?></div></div>
    <div class="stat"><div class="stat-ico">🕒</div><div class="stat-label">Availability</div><div class="stat-value" style="font-size:20px;line-height:1.4"><?php echo e($provider["availability"] ?? "Not set"); ?></div></div>
</div>
<?php if (!$days): ?>
    <div class="card"><div class="empty"><div class="big">📅</div><strong>Nothing scheduled</strong>New bookings assigned to you will appear here.</div></div>
<?php else: foreach ($days as $date => $list): $isToday = $date === $today; ?>
    <div class="card">
        <div class="card-head">
            <div><div class="card-title"><?php echo e(date("l, F j, Y", strtotime($date))); ?></div><div class="card-sub"><?php echo count($list); ?> appointment<?php echo count($list) === 1 ? "" : "s"; ?></div></div>
            <?php if ($isToday): ?><span class="badge b-info">Today</span><?php endif; ?>
        </div>
        <?php foreach ($list as $r): ?>
            <div class="list-item">
                <div class="ico">🕒</div>
                <div class="grow"><div class="cell-title"><?php echo e(fmt_time($r["appointment_time"])); ?> &middot; <?php echo e($r["service_name"]); ?></div>
                    <div class="cell-sub">with <?php echo e($r["customer_name"]); ?> &middot; <?php echo (int)$r["duration"]; ?> min</div></div>
                <?php echo status_badge((string)$r["status"]); ?>
                <a class="btn btn-ghost btn-sm" href="update_status.php?id=<?php echo (int)$r["appointment_id"]; ?>">Update</a>
            </div>
        <?php endforeach; ?>
    </div>
<?php endforeach; endif; ?>
<?php if ($past): ?>
<div class="card">
    <div class="card-head"><div class="card-title">Recent past appointments</div></div>
    <?php render_appointment_table(array_slice($past, 0, 8), ["date", "time", "customer", "service", "status"]); ?>
</div>
<?php endif; ?>
<?php layout_end(); ?>
