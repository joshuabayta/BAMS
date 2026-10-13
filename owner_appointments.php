<?php
require_once __DIR__ . "/includes/layout.php";
require_once __DIR__ . "/includes/data.php";
require_role([BAMS_ADMIN, BAMS_OWNER]);

$statuses = ["Booked", "Confirmed", "In Progress", "Rescheduled", "Completed", "Cancelled"];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (csrf_ok()) {
        $id = (int)($_POST["id"] ?? 0);
        $new = $_POST["status"] ?? "";
        if (in_array($new, $statuses, true)) {
            $rows = appointment_rows($conn, "a.appointment_id = $id");
            if ($rows) {
                $up = $conn->prepare("UPDATE appointments SET status = ? WHERE appointment_id = ?");
                $up->bind_param("si", $new, $id);
                $up->execute();
                $cu = $conn->query("SELECT user_id FROM customers WHERE customer_id = " . (int)$rows[0]["customer_id"])->fetch_assoc();
                if ($cu) {
                    $cuid = (int)$cu["user_id"];
                    $type = "Status Update";
                    $text = "Your appointment #$id (" . $rows[0]["service_name"] . ") is now $new.";
                    $n = $conn->prepare("INSERT INTO notifications (user_id, appointment_id, message, notification_type) VALUES (?, ?, ?, ?)");
                    $n->bind_param("iiss", $cuid, $id, $text, $type);
                    $n->execute();
                }
                flash_set("success", "Appointment #$id is now $new. The customer was notified.");
            }
        }
    } else {
        flash_set("error", "Your session expired. Please try again.");
    }
    header("Location: owner_appointments.php?" . http_build_query(array_filter(["q" => $_GET["q"] ?? "", "status" => $_GET["status"] ?? ""])));
    exit();
}

$q = trim($_GET["q"] ?? "");
$statusFilter = $_GET["status"] ?? "";
$from = $_GET["from"] ?? "";
$to = $_GET["to"] ?? "";
$all = appointment_rows($conn, "", "a.appointment_date DESC, a.appointment_time DESC");
$counts = [];
foreach ($all as $r) {
    $counts[$r["status"]] = ($counts[$r["status"]] ?? 0) + 1;
}
$shown = array_values(array_filter($all, function ($r) use ($q, $statusFilter, $from, $to) {
    if ($statusFilter !== "" && $r["status"] !== $statusFilter) return false;
    if ($from !== "" && $r["appointment_date"] < $from) return false;
    if ($to !== "" && $r["appointment_date"] > $to) return false;
    if ($q !== "") {
        $hay = strtolower($r["appointment_id"] . " " . $r["customer_name"] . " " . $r["provider_name"] . " " . $r["service_name"] . " " . $r["status"]);
        if (strpos($hay, strtolower($q)) === false) return false;
    }
    return true;
}));

layout_start("Appointments", "owner_appointments.php");
?>
<div class="page-head">
    <div><div class="eyebrow"><?php echo e(current_role()); ?></div><h1>Appointment Management</h1><p>Review every appointment and update its status.</p></div>
    <div class="actions"><a class="btn btn-ghost" href="calendar.php">Calendar view</a><a class="btn btn-ghost" href="reports.php">Reports</a></div>
</div>
<div class="stats">
    <div class="stat"><div class="stat-ico">📋</div><div class="stat-label">Total</div><div class="stat-value"><?php echo count($all); ?></div></div>
    <div class="stat"><div class="stat-ico">⏳</div><div class="stat-label">Booked</div><div class="stat-value"><?php echo (int)($counts["Booked"] ?? 0); ?></div></div>
    <div class="stat"><div class="stat-ico">✅</div><div class="stat-label">Confirmed</div><div class="stat-value"><?php echo (int)($counts["Confirmed"] ?? 0); ?></div></div>
    <div class="stat"><div class="stat-ico">🏁</div><div class="stat-label">Completed</div><div class="stat-value"><?php echo (int)($counts["Completed"] ?? 0); ?></div></div>
</div>
<div class="card">
    <div class="card-head">
        <div class="card-title"><?php echo count($shown); ?> of <?php echo count($all); ?> appointments</div>
        <form class="search-bar" method="GET">
            <input name="q" type="search" placeholder="Search customer, provider, service, ID" value="<?php echo e($q); ?>">
            <select name="status"><option value="">All statuses</option><?php foreach ($statuses as $s): ?><option <?php echo $statusFilter === $s ? "selected" : ""; ?>><?php echo e($s); ?></option><?php endforeach; ?></select>
            <input type="date" name="from" value="<?php echo e($from); ?>" title="From date" style="min-width:150px">
            <input type="date" name="to" value="<?php echo e($to); ?>" title="To date" style="min-width:150px">
            <button class="btn btn-sm">Filter</button>
            <?php if ($q !== "" || $statusFilter !== "" || $from !== "" || $to !== ""): ?><a class="btn btn-ghost btn-sm" href="owner_appointments.php">Clear</a><?php endif; ?>
        </form>
    </div>
    <?php if (!$shown): ?>
        <div class="empty"><div class="big">📭</div><strong>No appointments match</strong></div>
    <?php else: ?>
    <div class="table-wrap"><table>
        <thead><tr><th>ID</th><th>Customer</th><th>Service</th><th>Provider</th><th>Date</th><th>Time</th><th>Status</th><th>Change</th></tr></thead>
        <tbody>
        <?php foreach ($shown as $r): ?>
            <tr>
                <td>#<?php echo (int)$r["appointment_id"]; ?></td>
                <td><div class="cell-user"><div class="mini-avatar"><?php echo e(initial($r["customer_name"])); ?></div><div class="cell-title"><?php echo e($r["customer_name"]); ?></div></div></td>
                <td><?php echo e($r["service_name"]); ?></td>
                <td><?php echo e($r["provider_name"]); ?></td>
                <td><?php echo e(fmt_date($r["appointment_date"])); ?></td>
                <td><?php echo e(fmt_time($r["appointment_time"])); ?></td>
                <td><?php echo status_badge((string)$r["status"]); ?></td>
                <td>
                    <form method="POST" class="search-bar" style="flex-wrap:nowrap">
                        <?php echo csrf_field(); ?><input type="hidden" name="id" value="<?php echo (int)$r["appointment_id"]; ?>">
                        <select name="status" style="min-width:128px"><?php foreach ($statuses as $s): ?><option <?php echo $r["status"] === $s ? "selected" : ""; ?>><?php echo e($s); ?></option><?php endforeach; ?></select>
                        <button class="btn btn-ghost btn-sm">Save</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table></div>
    <?php endif; ?>
</div>
<?php layout_end(); ?>
