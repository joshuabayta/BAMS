<?php
require_once __DIR__ . "/includes/layout.php";
require_once __DIR__ . "/includes/data.php";
require_role([BAMS_CUSTOMER]);

$user_id = (int)$_SESSION["user_id"];
$appointment_id = (int)($_GET["id"] ?? $_POST["id"] ?? 0);
$message = "";

$stmt = $conn->prepare("SELECT customer_id FROM customers WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$customer = $stmt->get_result()->fetch_assoc();
if (!$customer) {
    header("Location: my_appointments.php");
    exit();
}
$cid = (int)$customer["customer_id"];
$rows = appointment_rows($conn, "a.appointment_id = $appointment_id AND a.customer_id = $cid AND a.status IN ('Booked','Rescheduled')");
if (!$rows) {
    flash_set("error", "That appointment cannot be rescheduled.");
    header("Location: my_appointments.php");
    exit();
}
$appt = $rows[0];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $date = $_POST["appointment_date"] ?? "";
    $time = $_POST["appointment_time"] ?? "";
    if (!csrf_ok()) {
        $message = "Your session expired. Please try again.";
    } elseif ($date == "" || $time == "") {
        $message = "Please select a date and time.";
    } elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || $date < date("Y-m-d")) {
        $message = "Please choose today or a future date.";
    } else {
        $pid = (int)$appt["provider_id"];
        $cf = $conn->prepare("SELECT appointment_id FROM appointments WHERE provider_id = ? AND appointment_date = ? AND appointment_time = ? AND status NOT IN ('Cancelled','Canceled') AND appointment_id <> ?");
        $cf->bind_param("issi", $pid, $date, $time, $appointment_id);
        $cf->execute();
        if ($cf->get_result()->num_rows > 0) {
            $message = "That provider is already booked at that time. Please pick another time.";
        } else {
            $up = $conn->prepare("UPDATE appointments SET appointment_date = ?, appointment_time = ?, status = 'Rescheduled' WHERE appointment_id = ? AND customer_id = ? AND status IN ('Booked','Rescheduled')");
            $up->bind_param("ssii", $date, $time, $appointment_id, $cid);
            if ($up->execute()) {
                $type = "Appointment";
                $n = $conn->prepare("INSERT INTO notifications (user_id, appointment_id, message, notification_type) VALUES (?, ?, ?, ?)");
                $when = date("M j, Y", strtotime($date)) . " at " . date("g:i A", strtotime($time));
                $m = "Your appointment #$appointment_id was rescheduled to $when.";
                $n->bind_param("iiss", $user_id, $appointment_id, $m, $type);
                $n->execute();
                $pr = $conn->query("SELECT user_id FROM service_providers WHERE provider_id = $pid");
                if ($pr && $row = $pr->fetch_assoc()) {
                    $puid = (int)$row["user_id"];
                    $m2 = "Appointment #$appointment_id was moved to $when.";
                    $n->bind_param("iiss", $puid, $appointment_id, $m2, $type);
                    $n->execute();
                }
                flash_set("success", "Your appointment was rescheduled.");
                header("Location: my_appointments.php");
                exit();
            }
            $message = "Failed to reschedule the appointment.";
        }
    }
}
layout_start("Reschedule Appointment", "my_appointments.php");
?>
<div class="page-head">
    <div><div class="eyebrow">Customer portal</div><h1>Reschedule appointment #<?php echo (int)$appt["appointment_id"]; ?></h1><p>Pick a new date and time.</p></div>
</div>
<?php if ($message !== ""): ?><div class="alert alert-error"><?php echo e($message); ?></div><?php endif; ?>
<div class="grid grid-2">
    <div class="card"><div class="card-head"><div class="card-title">Current booking</div></div>
        <div class="card-body"><dl class="kv">
            <dt>Service</dt><dd><?php echo e($appt["service_name"]); ?></dd>
            <dt>Provider</dt><dd><?php echo e($appt["provider_name"]); ?></dd>
            <dt>Date</dt><dd><?php echo e(fmt_date($appt["appointment_date"])); ?></dd>
            <dt>Time</dt><dd><?php echo e(fmt_time($appt["appointment_time"])); ?></dd>
            <dt>Status</dt><dd><?php echo status_badge((string)$appt["status"]); ?></dd>
        </dl></div></div>
    <div class="card"><div class="card-head"><div class="card-title">New date and time</div></div>
        <div class="card-body">
            <form method="POST" class="grid" style="gap:16px">
                <?php echo csrf_field(); ?><input type="hidden" name="id" value="<?php echo (int)$appt["appointment_id"]; ?>">
                <div class="field"><label>New date</label><input type="date" name="appointment_date" min="<?php echo date("Y-m-d"); ?>" value="<?php echo e($appt["appointment_date"]); ?>" required></div>
                <div class="field"><label>New time</label><input type="time" name="appointment_time" value="<?php echo e(substr($appt["appointment_time"], 0, 5)); ?>" required></div>
                <button class="btn" type="submit">Save changes</button>
                <a class="btn btn-ghost" href="my_appointments.php">Back to My Appointments</a>
            </form>
        </div></div>
</div>
<?php layout_end(); ?>
