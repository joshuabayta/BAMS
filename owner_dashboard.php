<?php
require_once __DIR__ . "/includes/layout.php";
require_once __DIR__ . "/includes/data.php";
require_role([BAMS_OWNER]);

$username = current_username();
$st = status_counts($conn);
$total = array_sum($st);
$customers = db_count($conn, "SELECT COUNT(*) FROM customers");
$providers = db_count($conn, "SELECT COUNT(*) FROM service_providers");
$services = db_count($conn, "SELECT COUNT(*) FROM services");
$today = appointment_rows($conn, "a.appointment_date = CURDATE() AND a.status NOT IN ('Cancelled','Canceled')", "a.appointment_time ASC");
$recent = appointment_rows($conn, "", "a.appointment_id DESC", 6);
$done = (int)($st["Completed"] ?? 0);
$rate = $total > 0 ? round($done / $total * 100) : 0;

layout_start("Business Owner Dashboard", "owner_dashboard.php");
?>
<section class="hero-card">
    <div>
        <div class="eyebrow" style="color:#ffd9bd">Business overview</div>
        <h1>Welcome back, <?php echo e($username); ?>!</h1>
        <p>Monitor appointment activity, review your team and follow your business performance from one place.</p>
    </div>
    <a class="btn" href="reports.php">Open reports &rarr;</a>
</section>

<div class="stats">
    <div class="stat"><div class="stat-ico">📋</div><div class="stat-label">Total appointments</div><div class="stat-value"><?php echo number_format($total); ?></div><div class="stat-sub"><?php echo count($today); ?> scheduled for today</div></div>
    <div class="stat"><div class="stat-ico">⏳</div><div class="stat-label">Booked</div><div class="stat-value"><?php echo (int)($st["Booked"] ?? 0); ?></div><div class="stat-sub">Waiting for confirmation</div></div>
    <div class="stat"><div class="stat-ico">✅</div><div class="stat-label">Confirmed</div><div class="stat-value"><?php echo (int)($st["Confirmed"] ?? 0); ?></div><div class="stat-sub">Ready to happen</div></div>
    <div class="stat"><div class="stat-ico">🏁</div><div class="stat-label">Completed</div><div class="stat-value"><?php echo $done; ?></div><div class="stat-sub"><?php echo $rate; ?>% of all bookings</div></div>
    <div class="stat"><div class="stat-ico">🧑</div><div class="stat-label">Customers</div><div class="stat-value"><?php echo number_format($customers); ?></div><div class="stat-sub">Registered customers</div></div>
    <div class="stat"><div class="stat-ico">🧑‍💼</div><div class="stat-label">Service providers</div><div class="stat-value"><?php echo number_format($providers); ?></div><div class="stat-sub"><?php echo $services; ?> services offered</div></div>
</div>

<div class="tiles" style="margin-bottom:24px">
    <a class="tile" href="owner_appointments.php"><div class="ico">📋</div><h3>Appointments</h3><p>Review customer bookings and statuses.</p></a>
    <a class="tile" href="staff.php"><div class="ico">👥</div><h3>Staff</h3><p>See your service providers and availability.</p></a>
    <a class="tile" href="calendar.php"><div class="ico">📆</div><h3>Calendar</h3><p>Open the appointment calendar.</p></a>
    <a class="tile" href="reports.php"><div class="ico">📈</div><h3>Reports</h3><p>Business statistics and summaries.</p></a>
</div>

<div class="split">
    <div class="card">
        <div class="card-head"><div><div class="card-title">Recent appointments</div><div class="card-sub">Newest bookings</div></div><a class="btn btn-sm" href="owner_appointments.php">View all</a></div>
        <?php render_appointment_table($recent, ["customer", "service", "provider", "date", "status"]); ?>
    </div>
    <div class="card">
        <div class="card-head"><div class="card-title">Today</div><span class="badge b-info"><?php echo date("M j"); ?></span></div>
        <?php if (!$today): ?>
            <div class="empty"><div class="big">☀️</div><strong>Nothing scheduled today</strong>Enjoy a quiet day.</div>
        <?php else: foreach ($today as $t): ?>
            <div class="list-item">
                <div class="ico">🕒</div>
                <div class="grow"><div class="cell-title"><?php echo e(fmt_time($t["appointment_time"])); ?> &middot; <?php echo e($t["service_name"]); ?></div>
                <div class="cell-sub"><?php echo e($t["customer_name"]); ?> with <?php echo e($t["provider_name"]); ?></div></div>
                <?php echo status_badge((string)$t["status"]); ?>
            </div>
        <?php endforeach; endif; ?>
    </div>
</div>
<?php layout_end(); ?>
