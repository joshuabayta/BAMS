<?php
require_once __DIR__ . "/includes/layout.php";
require_once __DIR__ . "/includes/data.php";
require_role([BAMS_ADMIN, BAMS_OWNER]);

$st = status_counts($conn);
$total = array_sum($st);
$today = date("Y-m-d");
$todayCount = db_count($conn, "SELECT COUNT(*) FROM appointments WHERE appointment_date = '$today'");
$monthCount = db_count($conn, "SELECT COUNT(*) FROM appointments WHERE appointment_date >= '" . date("Y-m-01") . "' AND appointment_date <= '" . date("Y-m-t") . "'");
$customers = db_count($conn, "SELECT COUNT(*) FROM customers");
$providers = db_count($conn, "SELECT COUNT(*) FROM service_providers");
$done = (int)($st["Completed"] ?? 0);
$cancelled = (int)($st["Cancelled"] ?? 0) + (int)($st["Canceled"] ?? 0);
$completionRate = $total > 0 ? round($done / $total * 100) : 0;
$cancelRate = $total > 0 ? round($cancelled / $total * 100) : 0;

$byService = $conn->query("SELECT s.service_name, COUNT(*) c FROM appointments a LEFT JOIN services s ON a.service_id = s.service_id GROUP BY s.service_name ORDER BY c DESC LIMIT 6")->fetch_all(MYSQLI_ASSOC);
$byProvider = $conn->query("SELECT TRIM(CONCAT(p.first_name,' ',p.last_name)) AS n, COUNT(*) c FROM appointments a LEFT JOIN service_providers p ON a.provider_id = p.provider_id GROUP BY n ORDER BY c DESC LIMIT 6")->fetch_all(MYSQLI_ASSOC);
$months = [];
for ($i = 5; $i >= 0; $i--) {
    $m = date("Y-m", strtotime("-$i month"));
    $months[$m] = db_count($conn, "SELECT COUNT(*) FROM appointments WHERE DATE_FORMAT(appointment_date, '%Y-%m') = '$m'");
}
$maxMonth = max(1, max($months));
$recent = appointment_rows($conn, "", "a.appointment_date DESC, a.appointment_time DESC", 10);
$palette = ["Booked" => "var(--wait)", "Rescheduled" => "#a16207", "Confirmed" => "var(--primary)", "In Progress" => "var(--info)", "Completed" => "var(--good)", "Cancelled" => "var(--bad)"];

layout_start("Reports", "reports.php");
?>
<div class="page-head">
    <div><div class="eyebrow">Analytics</div><h1>Reports &amp; Analytics</h1><p>Appointment statistics and business activity.</p></div>
    <div class="actions"><button class="btn btn-ghost" onclick="window.print()">🖨️ Print report</button></div>
</div>
<div class="stats">
    <div class="stat"><div class="stat-ico">📋</div><div class="stat-label">Total appointments</div><div class="stat-value"><?php echo number_format($total); ?></div></div>
    <div class="stat"><div class="stat-ico">🏁</div><div class="stat-label">Completion rate</div><div class="stat-value"><?php echo $completionRate; ?>%</div><div class="stat-sub"><?php echo $done; ?> completed</div></div>
    <div class="stat"><div class="stat-ico">🚫</div><div class="stat-label">Cancellation rate</div><div class="stat-value"><?php echo $cancelRate; ?>%</div><div class="stat-sub"><?php echo $cancelled; ?> cancelled</div></div>
    <div class="stat"><div class="stat-ico">📆</div><div class="stat-label">This month</div><div class="stat-value"><?php echo number_format($monthCount); ?></div><div class="stat-sub"><?php echo $todayCount; ?> today</div></div>
</div>
<div class="grid grid-2">
    <div class="card"><div class="card-head"><div class="card-title">Appointment status</div></div>
        <div class="card-body grid" style="gap:14px">
            <?php foreach ($palette as $label => $color): $n = (int)($st[$label] ?? 0); $pct = $total ? round($n / $total * 100) : 0; ?>
                <div><div style="display:flex;justify-content:space-between;font-weight:700;margin-bottom:5px"><span><?php echo e($label); ?></span><span><?php echo $n; ?> &middot; <?php echo $pct; ?>%</span></div>
                <div class="bar"><i style="width:<?php echo $pct; ?>%;background:<?php echo $color; ?>"></i></div></div>
            <?php endforeach; ?>
        </div></div>
    <div class="card"><div class="card-head"><div class="card-title">Last 6 months</div></div>
        <div class="card-body" style="display:flex;align-items:flex-end;gap:14px;height:230px">
            <?php foreach ($months as $m => $n): ?>
                <div style="flex:1;text-align:center"><div style="font-weight:800"><?php echo $n; ?></div>
                <div style="height:<?php echo max(6, round($n / $maxMonth * 150)); ?>px;background:var(--accent);border-radius:10px 10px 4px 4px;margin:4px 0"></div>
                <div class="cell-sub"><?php echo e(date("M", strtotime($m . "-01"))); ?></div></div>
            <?php endforeach; ?>
        </div></div>
</div>
<div class="grid grid-2">
    <div class="card"><div class="card-head"><div class="card-title">Most booked services</div></div>
        <?php if (!$byService): ?><div class="empty"><strong>No data yet</strong></div><?php else: foreach ($byService as $r): ?>
            <div class="list-item"><div class="grow cell-title"><?php echo e($r["service_name"] ?: "Unknown"); ?></div><span class="badge b-info"><?php echo (int)$r["c"]; ?></span></div>
        <?php endforeach; endif; ?></div>
    <div class="card"><div class="card-head"><div class="card-title">Busiest providers</div></div>
        <?php if (!$byProvider): ?><div class="empty"><strong>No data yet</strong></div><?php else: foreach ($byProvider as $r): ?>
            <div class="list-item"><div class="grow cell-title"><?php echo e($r["n"] ?: "Unknown"); ?></div><span class="badge b-info"><?php echo (int)$r["c"]; ?></span></div>
        <?php endforeach; endif; ?></div>
</div>
<div class="grid grid-2" style="grid-template-columns:repeat(2,1fr)">
    <div class="stat"><div class="stat-label">Customers</div><div class="stat-value"><?php echo $customers; ?></div></div>
    <div class="stat"><div class="stat-label">Service providers</div><div class="stat-value"><?php echo $providers; ?></div></div>
</div>
<div class="card" style="margin-top:22px">
    <div class="card-head"><div class="card-title">Recent appointments</div><a class="btn btn-sm" href="owner_appointments.php">View all</a></div>
    <?php render_appointment_table($recent, ["id", "customer", "service", "provider", "date", "status"]); ?>
</div>
<?php layout_end(); ?>
