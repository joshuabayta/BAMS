<?php
require_once __DIR__ . "/includes/bootstrap.php";
require_role([BAMS_CUSTOMER]);

if ($_SERVER["REQUEST_METHOD"] !== "POST" || !csrf_ok()) {
    flash_set("error", "That request could not be verified. Please try again.");
    header("Location: my_appointments.php");
    exit();
}
$user_id = (int)$_SESSION["user_id"];
$appointment_id = (int)($_POST["id"] ?? 0);

$stmt = $conn->prepare("SELECT customer_id FROM customers WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$customer = $stmt->get_result()->fetch_assoc();
if (!$customer) {
    header("Location: my_appointments.php");
    exit();
}
$cid = (int)$customer["customer_id"];
$stmt = $conn->prepare("UPDATE appointments SET status = 'Cancelled' WHERE appointment_id = ? AND customer_id = ? AND status IN ('Booked','Rescheduled')");
$stmt->bind_param("ii", $appointment_id, $cid);
$stmt->execute();
if ($stmt->affected_rows > 0) {
    $type = "Appointment";
    $n = $conn->prepare("INSERT INTO notifications (user_id, appointment_id, message, notification_type) VALUES (?, ?, ?, ?)");
    $m = "You cancelled appointment #$appointment_id.";
    $n->bind_param("iiss", $user_id, $appointment_id, $m, $type);
    $n->execute();
    $pr = $conn->query("SELECT p.user_id FROM appointments a JOIN service_providers p ON a.provider_id = p.provider_id WHERE a.appointment_id = $appointment_id");
    if ($pr && $row = $pr->fetch_assoc()) {
        $puid = (int)$row["user_id"];
        $m2 = "Appointment #$appointment_id was cancelled by the customer.";
        $n->bind_param("iiss", $puid, $appointment_id, $m2, $type);
        $n->execute();
    }
    flash_set("success", "Your appointment was cancelled.");
} else {
    flash_set("error", "That appointment can no longer be cancelled.");
}
header("Location: my_appointments.php");
exit();
