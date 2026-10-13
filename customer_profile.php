<?php
require_once __DIR__ . "/includes/layout.php";
require_role([BAMS_CUSTOMER]);

$user_id = (int)$_SESSION["user_id"];
$message = "";
$messageType = "";

function load_customer(mysqli $conn, int $uid): ?array
{
    $st = $conn->prepare("SELECT * FROM customers WHERE user_id = ?");
    $st->bind_param("i", $uid);
    $st->execute();
    return $st->get_result()->fetch_assoc() ?: null;
}
$customer = load_customer($conn, $user_id);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $first = trim($_POST["first_name"] ?? "");
    $last = trim($_POST["last_name"] ?? "");
    $contact = trim($_POST["contact_number"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $address = trim($_POST["address"] ?? "");
    if (!csrf_ok()) {
        $message = "Your session expired. Please try again.";
        $messageType = "error";
    } elseif ($first == "" || $last == "") {
        $message = "First name and last name are required.";
        $messageType = "error";
    } elseif ($email !== "" && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Please enter a valid email address.";
        $messageType = "error";
    } else {
        if ($customer) {
            $st = $conn->prepare("UPDATE customers SET first_name = ?, last_name = ?, contact_number = ?, email = ?, address = ? WHERE user_id = ?");
            $st->bind_param("sssssi", $first, $last, $contact, $email, $address, $user_id);
        } else {
            $st = $conn->prepare("INSERT INTO customers (user_id, first_name, last_name, contact_number, email, address) VALUES (?, ?, ?, ?, ?, ?)");
            $st->bind_param("isssss", $user_id, $first, $last, $contact, $email, $address);
        }
        if ($st->execute()) {
            $message = "Your profile was saved.";
            $messageType = "success";
            $customer = load_customer($conn, $user_id);
        } else {
            $message = "Failed to save your profile.";
            $messageType = "error";
        }
    }
}
$c = $customer ?? [];
layout_start("My Profile", "customer_profile.php");
?>
<div class="page-head">
    <div><div class="eyebrow">Customer portal</div><h1>My Profile</h1><p>Keep your details up to date so your appointments are handled correctly.</p></div>
</div>
<?php if ($message != ""): ?><div class="alert alert-<?php echo e($messageType); ?>"><?php echo e($message); ?></div><?php endif; ?>
<div class="split">
    <div class="card">
        <div class="card-head"><div class="card-title">Your information</div></div>
        <div class="card-body">
            <form method="POST" action="customer_profile.php" class="form-grid">
                <?php echo csrf_field(); ?>
                <div class="field"><label for="first_name">First name *</label><input id="first_name" name="first_name" maxlength="50" value="<?php echo e($c["first_name"] ?? ""); ?>" required></div>
                <div class="field"><label for="last_name">Last name *</label><input id="last_name" name="last_name" maxlength="50" value="<?php echo e($c["last_name"] ?? ""); ?>" required></div>
                <div class="field"><label for="contact_number">Contact number</label><input id="contact_number" type="tel" name="contact_number" maxlength="20" value="<?php echo e($c["contact_number"] ?? ""); ?>"></div>
                <div class="field"><label for="email">Email address</label><input id="email" type="email" name="email" maxlength="100" value="<?php echo e($c["email"] ?? ""); ?>"></div>
                <div class="field full"><label for="address">Address</label><textarea id="address" name="address" maxlength="255"><?php echo e($c["address"] ?? ""); ?></textarea></div>
                <div class="field full"><button class="btn" type="submit">Save profile</button></div>
            </form>
        </div>
    </div>
    <div class="card">
        <div class="card-head"><div class="card-title">Account</div></div>
        <div class="card-body">
            <div class="cell-user" style="margin-bottom:16px"><div class="avatar" style="width:54px;height:54px;font-size:22px"><?php echo e(initial(current_username())); ?></div>
                <div><div class="cell-title"><?php echo e(current_username()); ?></div><div class="cell-sub">Customer</div></div></div>
            <p><a class="btn btn-ghost" href="my_appointments.php">My appointments</a></p>
            <p><a class="btn btn-ghost" href="book_appointment.php">Book an appointment</a></p>
        </div>
    </div>
</div>
<?php layout_end(); ?>
