<?php
require_once __DIR__ . "/includes/layout.php";
require_once __DIR__ . "/includes/data.php";
require_role([BAMS_CUSTOMER]);

$user_id = (int)$_SESSION["user_id"];
$message = "";
$messageType = "";

$stmt = $conn->prepare("SELECT customer_id FROM customers WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$customer = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$customer) {
    $message = "Please complete your customer profile first.";
    $messageType = "error";
}

$form = ["service_id" => "", "provider_id" => "", "appointment_date" => "", "appointment_time" => ""];

if ($_SERVER["REQUEST_METHOD"] == "POST" && $customer) {
    $provider_id = filter_input(INPUT_POST, "provider_id", FILTER_VALIDATE_INT);
    $service_id = filter_input(INPUT_POST, "service_id", FILTER_VALIDATE_INT);
    $date = $_POST["appointment_date"] ?? "";
    $time = $_POST["appointment_time"] ?? "";
    $form = ["service_id" => $service_id, "provider_id" => $provider_id, "appointment_date" => $date, "appointment_time" => $time];

    if (!csrf_ok()) {
        $message = "Your session expired. Please try again.";
        $messageType = "error";
    } elseif (!$provider_id || !$service_id || $date == "" || $time == "") {
        $message = "Please fill in all fields.";
        $messageType = "error";
    } elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || strtotime($date) === false || $date < date("Y-m-d")) {
        $message = "Please choose today or a future date.";
        $messageType = "error";
    } else {
        $sc = $conn->prepare("SELECT service_id FROM services WHERE service_id = ? AND status = 'Available'");
        $sc->bind_param("i", $service_id);
        $sc->execute();
        $validService = $sc->get_result()->fetch_assoc();
        $pc = $conn->prepare("SELECT provider_id, user_id FROM service_providers WHERE provider_id = ?");
        $pc->bind_param("i", $provider_id);
        $pc->execute();
        $validProvider = $pc->get_result()->fetch_assoc();
        if (!$validService || !$validProvider) {
            $message = "Please select a valid service and provider.";
            $messageType = "error";
        } else {
            $cf = $conn->prepare("SELECT appointment_id FROM appointments WHERE provider_id = ? AND appointment_date = ? AND appointment_time = ? AND status NOT IN ('Cancelled','Canceled')");
            $cf->bind_param("iss", $provider_id, $date, $time);
            $cf->execute();
            if ($cf->get_result()->num_rows > 0) {
                $message = "That provider is already booked at that date and time. Please choose another time.";
                $messageType = "error";
            } else {
                $ins = $conn->prepare("INSERT INTO appointments (customer_id, provider_id, service_id, appointment_date, appointment_time, status) VALUES (?, ?, ?, ?, ?, 'Booked')");
                $ins->bind_param("iiiss", $customer["customer_id"], $provider_id, $service_id, $date, $time);
                if ($ins->execute()) {
                    $appointment_id = $conn->insert_id;
                    $type = "Appointment";
                    $n = $conn->prepare("INSERT INTO notifications (user_id, appointment_id, message, notification_type) VALUES (?, ?, ?, ?)");
                    $m1 = "Your appointment has been booked successfully for " . date("M j, Y", strtotime($date)) . " at " . date("g:i A", strtotime($time)) . ".";
                    $n->bind_param("iiss", $user_id, $appointment_id, $m1, $type);
                    $n->execute();
                    $m2 = "You have a new appointment on " . date("M j, Y", strtotime($date)) . " at " . date("g:i A", strtotime($time)) . ".";
                    $puid = (int)$validProvider["user_id"];
                    $n->bind_param("iiss", $puid, $appointment_id, $m2, $type);
                    $n->execute();
                    $message = "Appointment booked successfully! You can follow it in My Appointments.";
                    $messageType = "success";
                    $form = ["service_id" => "", "provider_id" => "", "appointment_date" => "", "appointment_time" => ""];
                } else {
                    $message = "Failed to book the appointment. Please try again.";
                    $messageType = "error";
                }
            }
        }
    }
}

$services = $conn->query("SELECT service_id, service_name, duration FROM services WHERE status = 'Available' ORDER BY service_name ASC")->fetch_all(MYSQLI_ASSOC);
$providers = $conn->query("SELECT provider_id, first_name, last_name, specialization, availability FROM service_providers ORDER BY first_name ASC")->fetch_all(MYSQLI_ASSOC);

layout_start("Book Appointment", "book_appointment.php");
?>
<div class="page-head">
    <div><div class="eyebrow">Customer portal</div><h1>Book an appointment</h1><p>Choose a service, a provider, a date and a time.</p></div>
    <div class="actions"><a class="btn btn-ghost" href="my_appointments.php">My appointments</a></div>
</div>
<?php if ($message != ""): ?>
    <div class="alert alert-<?php echo e($messageType); ?>"><?php echo e($message); ?>
        <?php if (!$customer): ?> <a href="customer_profile.php">Open my profile &rarr;</a><?php endif; ?></div>
<?php endif; ?>
<div class="split">
    <div class="card">
        <div class="card-head"><div><div class="card-title">Appointment details</div><div class="card-sub">Fill in the form to schedule your visit.</div></div></div>
        <div class="card-body">
        <?php if ($customer): ?>
            <form method="POST" action="book_appointment.php" class="form-grid">
                <?php echo csrf_field(); ?>
                <div class="field full"><label for="service_id">Service</label>
                    <select name="service_id" id="service_id" required>
                        <option value="">-- Choose a service --</option>
                        <?php foreach ($services as $s): ?>
                            <option value="<?php echo (int)$s["service_id"]; ?>" <?php echo (int)$form["service_id"] === (int)$s["service_id"] ? "selected" : ""; ?>><?php echo e($s["service_name"]); ?> - <?php echo (int)$s["duration"]; ?> minutes</option>
                        <?php endforeach; ?>
                    </select></div>
                <div class="field full"><label for="provider_id">Service provider</label>
                    <select name="provider_id" id="provider_id" required>
                        <option value="">-- Choose a provider --</option>
                        <?php foreach ($providers as $p): ?>
                            <option value="<?php echo (int)$p["provider_id"]; ?>" <?php echo (int)$form["provider_id"] === (int)$p["provider_id"] ? "selected" : ""; ?>><?php echo e(trim($p["first_name"] . " " . $p["last_name"])); ?><?php echo $p["specialization"] ? " - " . e($p["specialization"]) : ""; ?></option>
                        <?php endforeach; ?>
                    </select></div>
                <div class="field"><label for="appointment_date">Date</label>
                    <input type="date" name="appointment_date" id="appointment_date" min="<?php echo date("Y-m-d"); ?>" value="<?php echo e($form["appointment_date"]); ?>" required></div>
                <div class="field"><label for="appointment_time">Time</label>
                    <input type="time" name="appointment_time" id="appointment_time" value="<?php echo e($form["appointment_time"]); ?>" required></div>
                <div class="field full"><button class="btn" type="submit">Book appointment</button></div>
            </form>
        <?php else: ?>
            <div class="empty"><div class="big">📝</div><strong>Your profile is not ready</strong><a class="btn" href="customer_profile.php">Complete my profile</a></div>
        <?php endif; ?>
        </div>
    </div>
    <div class="card">
        <div class="card-head"><div class="card-title">Our providers</div></div>
        <?php if (!$providers): ?>
            <div class="empty"><strong>No providers yet</strong></div>
        <?php else: foreach ($providers as $p): $n = trim($p["first_name"] . " " . $p["last_name"]); ?>
            <div class="list-item">
                <div class="mini-avatar"><?php echo e(initial($n)); ?></div>
                <div class="grow"><div class="cell-title"><?php echo e($n); ?></div>
                    <div class="cell-sub"><?php echo e($p["specialization"] ?: "General Service"); ?></div>
                    <div class="cell-sub">Available: <?php echo e($p["availability"] ?: "Ask the provider"); ?></div></div>
            </div>
        <?php endforeach; endif; ?>
    </div>
</div>
<?php layout_end(); ?>
