<?php
require_once __DIR__ . "/includes/layout.php";
require_once __DIR__ . "/includes/data.php";
require_role([BAMS_ADMIN]);

$username = current_username();
$totalUsers = db_count($conn, "SELECT COUNT(*) FROM users");
$totalAppointments = db_count($conn, "SELECT COUNT(*) FROM appointments");
$totalCustomers = db_count($conn, "SELECT COUNT(*) FROM customers");
$totalProviders = db_count($conn, "SELECT COUNT(*) FROM service_providers");
$totalServices = db_count($conn, "SELECT COUNT(*) FROM services");
$totalOwners = db_count($conn, "SELECT COUNT(*) FROM users WHERE role = 'Business Owner'");
$st = status_counts($conn);
$recent = appointment_rows($conn, "", "a.appointment_id DESC", 6);

layout_start("Administrator Dashboard", "admin_dashboard.php");
?>
<section class="hero-card">
    <div>
        <div class="eyebrow" style="color:#bcd3ff">Administrator</div>
        <h1>Welcome back, <?php echo e($username); ?>! 👋</h1>
        <p>Here is an overview of the Business Appointment Management System. Manage users, customers, providers, services and appointments.</p>
    </div>
    <div style="text-align:right"><div style="font-size:14px;opacity:.85">Today</div><div style="font-size:22px;font-weight:800"><?php echo date("F j, Y"); ?></div></div>
</section>

<div class="stats">
    <div class="stat"><div class="stat-ico">👤</div><div class="stat-label">Users</div><div class="stat-value"><?php echo number_format($totalUsers); ?></div><div class="stat-sub">Registered accounts</div></div>
    <div class="stat"><div class="stat-ico">📋</div><div class="stat-label">Appointments</div><div class="stat-value"><?php echo number_format($totalAppointments); ?></div><div class="stat-sub">All recorded bookings</div></div>
    <div class="stat"><div class="stat-ico">🧑</div><div class="stat-label">Customers</div><div class="stat-value"><?php echo number_format($totalCustomers); ?></div><div class="stat-sub">Customer records</div></div>
    <div class="stat"><div class="stat-ico">🧑‍💼</div><div class="stat-label">Providers</div><div class="stat-value"><?php echo number_format($totalProviders); ?></div><div class="stat-sub">Service providers</div></div>
</div>
<div class="stats">
    <div class="stat"><div class="stat-ico">⏳</div><div class="stat-label">Booked</div><div class="stat-value"><?php echo (int)($st["Booked"] ?? 0); ?></div><div class="stat-sub">Waiting for confirmation</div></div>
    <div class="stat"><div class="stat-ico">✅</div><div class="stat-label">Confirmed</div><div class="stat-value"><?php echo (int)($st["Confirmed"] ?? 0); ?></div><div class="stat-sub">Ready to happen</div></div>
    <div class="stat"><div class="stat-ico">🏁</div><div class="stat-label">Completed</div><div class="stat-value"><?php echo (int)($st["Completed"] ?? 0); ?></div><div class="stat-sub">Successfully finished</div></div>
    <div class="stat"><div class="stat-ico">🚫</div><div class="stat-label">Cancelled</div><div class="stat-value"><?php echo (int)($st["Cancelled"] ?? 0); ?></div><div class="stat-sub">Cancelled bookings</div></div>
</div>

<div class="page-head"><div><h2 style="margin:0">Manage the system</h2><p>Open a section to continue your work.</p></div></div>
<div class="tiles" style="margin-bottom:24px">
    <a class="tile" href="manage_users.php"><div class="ico">👤</div><h3>All Users</h3><p>Accounts and access roles (<?php echo $totalUsers; ?>).</p></a>
    <a class="tile" href="manage_customers.php"><div class="ico">🧑</div><h3>Customers</h3><p>Registered customer records (<?php echo $totalCustomers; ?>).</p></a>
    <a class="tile" href="manage_providers.php"><div class="ico">🧑‍💼</div><h3>Service Providers</h3><p>Provider records and availability (<?php echo $totalProviders; ?>).</p></a>
    <a class="tile" href="manage_owners.php"><div class="ico">🏢</div><h3>Business Owners</h3><p>Owner accounts (<?php echo $totalOwners; ?>).</p></a>
    <a class="tile" href="manage_services.php"><div class="ico">🛠️</div><h3>Services</h3><p>Services the business offers (<?php echo $totalServices; ?>).</p></a>
    <a class="tile" href="owner_appointments.php"><div class="ico">📋</div><h3>Appointments</h3><p>Review every business appointment.</p></a>
    <a class="tile" href="reports.php"><div class="ico">📈</div><h3>Reports</h3><p>Statistics and business reports.</p></a>
    <a class="tile" href="calendar.php"><div class="ico">📆</div><h3>Calendar</h3><p>All scheduled appointments by month.</p></a>
</div>

<div class="card">
    <div class="card-head">
        <div><div class="card-title">Recent appointments</div><div class="card-sub">The latest bookings in the system</div></div>
        <a class="btn btn-sm" href="owner_appointments.php">View all &rarr;</a>
    </div>
    <?php render_appointment_table($recent, ["id", "customer", "service", "date", "time", "status"]); ?>
</div>
<?php layout_end(); ?>
