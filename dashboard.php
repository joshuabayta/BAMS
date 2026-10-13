<?php
require_once __DIR__ . "/includes/layout.php";
require_once __DIR__ . "/includes/data.php";
require_login();

/* Admins and owners have their own dashboards. */
$role = current_role();
if ($role !== BAMS_CUSTOMER && $role !== BAMS_PROVIDER) {
    header("Location: " . role_home($role));
    exit();
}
$uid = (int)$_SESSION["user_id"];
$username = current_username();
$today = date("Y-m-d");

if ($role === BAMS_CUSTOMER) {
    $c = $conn->query("SELECT customer_id, first_name FROM customers WHERE user_id = $uid")->fetch_assoc();
    $cid = $c ? (int)$c["customer_id"] : 0;
    $name = $c && trim($c["first_name"]) !== "" ? $c["first_name"] : $username;
    $all = $cid ? appointment_rows($conn, "a.customer_id = $cid", "a.appointment_date ASC, a.appointment_time ASC") : [];
    $upcoming = array_values(array_filter($all, fn($r) => in_array($r["status"], ["Booked", "Confirmed", "Rescheduled"], true) && $r["appointment_date"] >= $today));
    $total = count($all);
    $done = count(array_filter($all, fn($r) => $r["status"] === "Completed"));
    $pending = count(array_filter($all, fn($r) => in_array($r["status"], ["Booked", "Rescheduled"], true)));
    layout_start("Dashboard", "dashboard.php");
    ?>
    <section class="hero-card">
        <div><div class="eyebrow" style="color:#bcd3ff">Customer</div><h1>Hello, <?php echo e($name); ?>! 👋</h1>
        <p>Book a service, follow your appointments and stay updated with notifications.</p></div>
        <a class="btn" href="book_appointment.php">+ Book an appointment</a>
    </section>
    <?php if (!$c || trim($c["first_name"]) === "" || $c["first_name"] === $username): ?>
        <div class="alert alert-info">Tip: add your full name and contact details in <a href="customer_profile.php"><b>My Profile</b></a> so providers know who you are.</div>
    <?php endif; ?>
    <div class="stats">
        <div class="stat"><div class="stat-ico">📋</div><div class="stat-label">All appointments</div><div class="stat-value"><?php echo $total; ?></div></div>
        <div class="stat"><div class="stat-ico">⏳</div><div class="stat-label">Waiting for confirmation</div><div class="stat-value"><?php echo $pending; ?></div></div>
        <div class="stat"><div class="stat-ico">📆</div><div class="stat-label">Upcoming</div><div class="stat-value"><?php echo count($upcoming); ?></div></div>
        <div class="stat"><div class="stat-ico">🏁</div><div class="stat-label">Completed</div><div class="stat-value"><?php echo $done; ?></div></div>
    </div>
    <div class="tiles" style="margin-bottom:24px">
        <a class="tile" href="book_appointment.php"><div class="ico">➕</div><h3>Book Appointment</h3><p>Choose a service, provider, date and time.</p></a>
        <a class="tile" href="my_appointments.php"><div class="ico">📋</div><h3>My Appointments</h3><p>Check status, reschedule or cancel.</p></a>
        <a class="tile" href="calendar.php"><div class="ico">📆</div><h3>Calendar</h3><p>See your bookings by month.</p></a>
        <a class="tile" href="customer_profile.php"><div class="ico">👤</div><h3>My Profile</h3><p>Update your personal details.</p></a>
    </div>
    <div class="card">
        <div class="card-head"><div><div class="card-title">Upcoming appointments</div><div class="card-sub">Your next visits</div></div><a class="btn btn-sm" href="my_appointments.php">View all</a></div>
        <?php render_appointment_table(array_slice($upcoming, 0, 5), ["service", "provider", "date", "time", "status"], "No upcoming appointments. Book one now!"); ?>
    </div>
    <?php
    layout_end();
    exit();
}

/* Service provider */
$p = $conn->query("SELECT provider_id, first_name, specialization FROM service_providers WHERE user_id = $uid")->fetch_assoc();
$pid = $p ? (int)$p["provider_id"] : 0;
$name = $p && trim($p["first_name"]) !== "" ? $p["first_name"] : $username;
$all = $pid ? appointment_rows($conn, "a.provider_id = $pid", "a.appointment_date ASC, a.appointment_time ASC") : [];
$todayList = array_values(array_filter($all, fn($r) => $r["appointment_date"] === $today && !in_array($r["status"], ["Cancelled", "Canceled"], true)));
$toConfirm = array_values(array_filter($all, fn($r) => in_array($r["status"], ["Booked", "Rescheduled"], true)));
$upcoming = array_values(array_filter($all, fn($r) => in_array($r["status"], ["Booked", "Confirmed", "Rescheduled"], true) && $r["appointment_date"] >= $today));
$done = count(array_filter($all, fn($r) => $r["status"] === "Completed"));
layout_start("Dashboard", "dashboard.php");
?>
<section class="hero-card">
    <div><div class="eyebrow" style="color:#bff3ec">Service provider</div><h1>Good day, <?php echo e($name); ?>!</h1>
    <p><?php echo e($p["specialization"] ?? "Manage your appointments, schedule and clients."); ?></p></div>
    <a class="btn" href="provider_appointments.php">Open my appointments</a>
</section>
<?php if (!$p): ?><div class="alert alert-warn">Your provider profile was not found. Please contact an administrator.</div><?php endif; ?>
<div class="stats">
    <div class="stat"><div class="stat-ico">🕒</div><div class="stat-label">Today</div><div class="stat-value"><?php echo count($todayList); ?></div><div class="stat-sub">Appointments today</div></div>
    <div class="stat"><div class="stat-ico">⏳</div><div class="stat-label">To confirm</div><div class="stat-value"><?php echo count($toConfirm); ?></div><div class="stat-sub">Need your response</div></div>
    <div class="stat"><div class="stat-ico">📆</div><div class="stat-label">Upcoming</div><div class="stat-value"><?php echo count($upcoming); ?></div></div>
    <div class="stat"><div class="stat-ico">🏁</div><div class="stat-label">Completed</div><div class="stat-value"><?php echo $done; ?></div></div>
</div>
<div class="tiles" style="margin-bottom:24px">
    <a class="tile" href="provider_appointments.php"><div class="ico">📋</div><h3>My Appointments</h3><p>Confirm, complete or cancel bookings.</p></a>
    <a class="tile" href="my_schedule.php"><div class="ico">🗓️</div><h3>My Schedule</h3><p>See your day-by-day schedule.</p></a>
    <a class="tile" href="client_details.php"><div class="ico">👥</div><h3>My Clients</h3><p>Customer details for your appointments.</p></a>
    <a class="tile" href="calendar.php"><div class="ico">📆</div><h3>Calendar</h3><p>Monthly view of your appointments.</p></a>
</div>
<div class="split">
    <div class="card">
        <div class="card-head"><div class="card-title">Waiting for your confirmation</div><a class="btn btn-sm" href="provider_appointments.php">Manage</a></div>
        <?php render_appointment_table(array_slice($toConfirm, 0, 6), ["customer", "service", "date", "time", "status"], "Nothing to confirm right now."); ?>
    </div>
    <div class="card">
        <div class="card-head"><div class="card-title">Today</div><span class="badge b-info"><?php echo date("M j"); ?></span></div>
        <?php if (!$todayList): ?>
            <div class="empty"><div class="big">☀️</div><strong>No appointments today</strong></div>
        <?php else: foreach ($todayList as $t): ?>
            <div class="list-item"><div class="ico">🕒</div>
                <div class="grow"><div class="cell-title"><?php echo e(fmt_time($t["appointment_time"])); ?> &middot; <?php echo e($t["service_name"]); ?></div><div class="cell-sub"><?php echo e($t["customer_name"]); ?></div></div>
                <?php echo status_badge((string)$t["status"]); ?></div>
        <?php endforeach; endif; ?>
    </div>
</div>
<?php layout_end(); ?>
