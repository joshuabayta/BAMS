<?php
require_once __DIR__ . "/includes/layout.php";
require_once __DIR__ . "/includes/data.php";
require_role([BAMS_PROVIDER]);

$uid = (int)$_SESSION["user_id"];
$appointment_id = (int)($_POST["id"] ?? $_GET["id"] ?? 0);
$message = "";
$messageType = "";

$stmt = $conn->prepare("SELECT provider_id FROM service_providers WHERE user_id = ?");
$stmt->bind_param("i", $uid);
$stmt->execute();
$provider = $stmt->get_result()->fetch_assoc();
if (!$provider) {
    flash_set("error", "Your provider profile was not found. Please contact an administrator.");
    header("Location: dashboard.php");
    exit();
}
$pid = (int)$provider["provider_id"];
$rows = appointment_rows($conn, "a.appointment_id = $appointment_id AND a.provider_id = $pid");
if (!$rows) {
    flash_set("error", "Appointment not found, or it is not assigned to you.");
    header("Location: provider_appointments.php");
    exit();
}
$appt = $rows[0];
$allowed = ["Booked", "Confirmed", "In Progress", "Completed", "Cancelled"];
$locked = in_array($appt["status"], ["Completed", "Cancelled"], true);

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $new = $_POST["status"] ?? "";
    $quick = !empty($_POST["quick"]);
    if (!csrf_ok()) {
        $message = "Your session expired. Please try again.";
        $messageType = "error";
    } elseif (!in_array($new, $allowed, true)) {
        $message = "Please select a valid appointment status.";
        $messageType = "error";
    } elseif ($locked) {
        $message = "Completed or cancelled appointments cannot be changed.";
        $messageType = "error";
    } else {
        $up = $conn->prepare("UPDATE appointments SET status = ? WHERE appointment_id = ? AND provider_id = ? AND status NOT IN ('Completed','Cancelled')");
        $up->bind_param("sii", $new, $appointment_id, $pid);
        $up->execute();
        if ($up->affected_rows > 0) {
            $cu = $conn->query("SELECT user_id FROM customers WHERE customer_id = " . (int)$appt["customer_id"])->fetch_assoc();
            if ($cu) {
                $cuid = (int)$cu["user_id"];
                $type = "Status Update";
                $text = "Your appointment #$appointment_id (" . $appt["service_name"] . ") is now " . $new . ".";
                $n = $conn->prepare("INSERT INTO notifications (user_id, appointment_id, message, notification_type) VALUES (?, ?, ?, ?)");
                $n->bind_param("iiss", $cuid, $appointment_id, $text, $type);
                $n->execute();
            }
            flash_set("success", "Appointment #$appointment_id is now $new. The customer was notified.");
            header("Location: provider_appointments.php");
            exit();
        }
        $message = "The status was not changed. Please check the appointment list.";
        $messageType = "error";
    }
    if ($quick) {
        flash_set("error", $message);
        header("Location: provider_appointments.php");
        exit();
    }
}
layout_start("Update Status", "provider_appointments.php");
?>
<div class="page-head">
    <div><div class="eyebrow">Service provider</div><h1>Update appointment #<?php echo (int)$appointment_id; ?></h1><p>The customer receives a notification after a successful update.</p></div>
    <div class="actions"><a class="btn btn-ghost" href="provider_appointments.php">&larr; My appointments</a></div>
</div>
<?php if ($message !== ""): ?><div class="alert alert-<?php echo e($messageType); ?>"><?php echo e($message); ?></div><?php endif; ?>
<div class="grid grid-2">
    <div class="card"><div class="card-head"><div class="card-title">Appointment information</div></div>
        <div class="card-body"><dl class="kv">
            <dt>Customer</dt><dd><?php echo e($appt["customer_name"]); ?></dd>
            <dt>Service</dt><dd><?php echo e($appt["service_name"]); ?></dd>
            <dt>Date</dt><dd><?php echo e(fmt_date($appt["appointment_date"])); ?></dd>
            <dt>Time</dt><dd><?php echo e(fmt_time($appt["appointment_time"])); ?></dd>
            <dt>Current status</dt><dd><?php echo status_badge((string)$appt["status"]); ?></dd>
        </dl></div></div>
    <div class="card"><div class="card-head"><div class="card-title">Change status</div></div>
        <div class="card-body">
        <?php if ($locked): ?>
            <div class="alert alert-info">This appointment is <b><?php echo e($appt["status"]); ?></b>. Its status cannot be changed.</div>
        <?php else: ?>
            <form method="POST" class="grid" style="gap:16px">
                <?php echo csrf_field(); ?><input type="hidden" name="id" value="<?php echo (int)$appointment_id; ?>">
                <div class="field"><label for="status">New status</label>
                    <select name="status" id="status" required>
                        <option value="">-- Select status --</option>
                        <?php foreach ($allowed as $o): ?><option <?php echo $appt["status"] === $o ? "selected" : ""; ?>><?php echo e($o); ?></option><?php endforeach; ?>
                    </select></div>
                <button class="btn" type="submit">Save status</button>
            </form>
        <?php endif; ?>
        </div></div>
</div>
<?php layout_end(); ?>
