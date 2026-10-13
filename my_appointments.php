<?php
require_once __DIR__ . "/includes/layout.php";
require_once __DIR__ . "/includes/data.php";
require_role([BAMS_CUSTOMER]);

$user_id = (int)$_SESSION["user_id"];
$stmt = $conn->prepare("SELECT customer_id FROM customers WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$customer = $stmt->get_result()->fetch_assoc();

$filter = $_GET["show"] ?? "all";
$rows = [];
if ($customer) {
    $cid = (int)$customer["customer_id"];
    $rows = appointment_rows($conn, "a.customer_id = $cid", "a.appointment_date DESC, a.appointment_time DESC");
}
$today = date("Y-m-d");
$shown = array_values(array_filter($rows, function ($r) use ($filter, $today) {
    $cancelled = in_array(strtolower($r["status"]), ["cancelled", "canceled"], true);
    if ($filter === "upcoming") {
        return !$cancelled && $r["status"] !== "Completed" && $r["appointment_date"] >= $today;
    }
    if ($filter === "past") {
        return $r["status"] === "Completed" || (!$cancelled && $r["appointment_date"] < $today);
    }
    if ($filter === "cancelled") {
        return $cancelled;
    }
    return true;
}));

layout_start("My Appointments", "my_appointments.php");
?>
<div class="page-head">
    <div><div class="eyebrow">Customer portal</div><h1>My Appointments</h1><p>Check the status of your bookings and manage upcoming ones.</p></div>
    <div class="actions"><a class="btn" href="book_appointment.php">+ Book an appointment</a></div>
</div>
<?php if (!$customer): ?>
    <div class="card"><div class="empty"><div class="big">👤</div><strong>Customer profile not found</strong>Please complete your profile before booking.<br><br><a class="btn" href="customer_profile.php">Complete my profile</a></div></div>
<?php else: ?>
<div class="filters" style="margin-bottom:16px">
    <?php foreach (["all" => "All", "upcoming" => "Upcoming", "past" => "Past", "cancelled" => "Cancelled"] as $k => $label): ?>
        <a class="chip<?php echo $filter === $k ? " active" : ""; ?>" href="my_appointments.php?show=<?php echo $k; ?>"><?php echo $label; ?></a>
    <?php endforeach; ?>
</div>
<div class="card">
    <div class="card-head"><div class="card-title"><?php echo count($shown); ?> appointment<?php echo count($shown) === 1 ? "" : "s"; ?></div></div>
    <?php if (!$shown): ?>
        <div class="empty"><div class="big">📅</div><strong>No appointments here</strong>Book a service to see it listed.</div>
    <?php else: ?>
    <div class="table-wrap"><table>
        <thead><tr><th>ID</th><th>Service</th><th>Provider</th><th>Date</th><th>Time</th><th>Status</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($shown as $r): $canChange = in_array($r["status"], ["Booked", "Rescheduled"], true) && $r["appointment_date"] >= $today; ?>
            <tr>
                <td>#<?php echo (int)$r["appointment_id"]; ?></td>
                <td><div class="cell-title"><?php echo e($r["service_name"]); ?></div><div class="cell-sub"><?php echo (int)$r["duration"]; ?> min</div></td>
                <td><?php echo e($r["provider_name"]); ?></td>
                <td><?php echo e(fmt_date($r["appointment_date"])); ?></td>
                <td><?php echo e(fmt_time($r["appointment_time"])); ?></td>
                <td><?php echo status_badge((string)$r["status"]); ?></td>
                <td>
                    <?php if ($canChange): ?>
                    <div class="row-actions">
                        <a class="btn btn-ghost btn-sm" href="reschedule.php?id=<?php echo (int)$r["appointment_id"]; ?>">Reschedule</a>
                        <form method="POST" action="cancel_appointment.php" onsubmit="return confirm('Are you sure you want to cancel this appointment?');">
                            <?php echo csrf_field(); ?><input type="hidden" name="id" value="<?php echo (int)$r["appointment_id"]; ?>">
                            <button class="btn btn-danger btn-sm" type="submit">Cancel</button>
                        </form>
                    </div>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table></div>
    <?php endif; ?>
</div>
<?php endif; ?>
<?php layout_end(); ?>
