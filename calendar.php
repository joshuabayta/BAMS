<?php
require_once __DIR__ . "/includes/layout.php";
require_once __DIR__ . "/includes/data.php";
require_login();

$role = current_role();
$uid = (int)$_SESSION["user_id"];

/* Each role only sees what it is allowed to see. */
$scope = "";
$scopeLabel = "All appointments";
if ($role === BAMS_CUSTOMER) {
    $scope = "a.customer_id IN (SELECT customer_id FROM customers WHERE user_id = $uid)";
    $scopeLabel = "Your appointments";
} elseif ($role === BAMS_PROVIDER) {
    $scope = "a.provider_id IN (SELECT provider_id FROM service_providers WHERE user_id = $uid)";
    $scopeLabel = "Your appointments";
}

$month = isset($_GET["m"]) && preg_match('/^\d{4}-\d{2}$/', $_GET["m"]) ? $_GET["m"] : date("Y-m");
$first = strtotime($month . "-01");
$daysInMonth = (int)date("t", $first);
$startDow = (int)date("w", $first);
$prev = date("Y-m", strtotime("-1 month", $first));
$next = date("Y-m", strtotime("+1 month", $first));

$where = "a.appointment_date >= '" . date("Y-m-01", $first) . "' AND a.appointment_date <= '" . date("Y-m-t", $first) . "'";
if ($scope !== "") {
    $where .= " AND $scope";
}
$rows = appointment_rows($conn, $where, "a.appointment_date ASC, a.appointment_time ASC");
$byDay = [];
foreach ($rows as $r) {
    $byDay[(int)date("j", strtotime($r["appointment_date"]))][] = $r;
}
$counts = [];
foreach ($rows as $r) {
    $counts[$r["status"]] = ($counts[$r["status"]] ?? 0) + 1;
}
function ev_class(string $s): string
{
    $s = strtolower($s);
    if (in_array($s, ["confirmed", "completed"], true)) return "good";
    if (in_array($s, ["booked", "rescheduled"], true)) return "wait";
    if (in_array($s, ["cancelled", "canceled"], true)) return "bad";
    return "";
}

layout_start("Calendar", "calendar.php");
?>
<div class="page-head">
    <div><div class="eyebrow"><?php echo e($scopeLabel); ?></div><h1>Appointment Calendar</h1><p>Review scheduled appointments, services and statuses by month.</p></div>
    <div class="cal-nav">
        <a class="btn btn-ghost btn-sm" href="calendar.php?m=<?php echo $prev; ?>">&larr; Prev</a>
        <strong style="min-width:150px;text-align:center"><?php echo date("F Y", $first); ?></strong>
        <a class="btn btn-ghost btn-sm" href="calendar.php?m=<?php echo $next; ?>">Next &rarr;</a>
        <a class="btn btn-sm" href="calendar.php">Today</a>
    </div>
</div>
<div class="stats">
    <div class="stat"><div class="stat-label">This month</div><div class="stat-value"><?php echo count($rows); ?></div></div>
    <div class="stat"><div class="stat-label">Booked / Rescheduled</div><div class="stat-value"><?php echo (int)($counts["Booked"] ?? 0) + (int)($counts["Rescheduled"] ?? 0); ?></div></div>
    <div class="stat"><div class="stat-label">Confirmed</div><div class="stat-value"><?php echo (int)($counts["Confirmed"] ?? 0); ?></div></div>
    <div class="stat"><div class="stat-label">Completed</div><div class="stat-value"><?php echo (int)($counts["Completed"] ?? 0); ?></div></div>
</div>
<div class="card">
    <div class="cal">
        <?php foreach (["Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat"] as $d): ?><div class="cal-dow"><?php echo $d; ?></div><?php endforeach; ?>
        <?php for ($i = 0; $i < $startDow; $i++): ?><div class="cal-day off"></div><?php endfor; ?>
        <?php for ($d = 1; $d <= $daysInMonth; $d++):
            $isToday = $month === date("Y-m") && $d === (int)date("j"); ?>
            <div class="cal-day<?php echo $isToday ? " today" : ""; ?>">
                <div class="cal-num"><?php echo $d; ?></div>
                <?php foreach (array_slice($byDay[$d] ?? [], 0, 3) as $ev): ?>
                    <span class="cal-ev <?php echo ev_class($ev["status"]); ?>" title="<?php echo e($ev["service_name"] . " - " . $ev["customer_name"] . " (" . $ev["status"] . ")"); ?>"><?php echo e(fmt_time($ev["appointment_time"])); ?> <?php echo e($ev["service_name"]); ?></span>
                <?php endforeach; ?>
                <?php if (count($byDay[$d] ?? []) > 3): ?><span class="cell-sub">+<?php echo count($byDay[$d]) - 3; ?> more</span><?php endif; ?>
            </div>
        <?php endfor; ?>
        <?php $filled = ($startDow + $daysInMonth) % 7; if ($filled) for ($i = $filled; $i < 7; $i++): ?><div class="cal-day off"></div><?php endfor; ?>
    </div>
</div>
<div class="card">
    <div class="card-head"><div class="card-title">Appointments in <?php echo date("F Y", $first); ?></div></div>
    <?php render_appointment_table($rows, $role === BAMS_CUSTOMER ? ["date", "time", "service", "provider", "status"] : ["date", "time", "customer", "service", "provider", "status"], "No appointments this month."); ?>
</div>
<?php layout_end(); ?>
