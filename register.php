<?php
require_once __DIR__ . "/includes/bootstrap.php";

if (isset($_SESSION["user_id"])) {
    header("Location: " . role_home(current_role()));
    exit();
}

$message = "";
$messageType = "";
$old = ["username" => "", "role" => ""];

/* Administrator accounts cannot be self-registered. An admin creates them. */
$allowedRoles = [BAMS_CUSTOMER, BAMS_PROVIDER, BAMS_OWNER];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST["username"] ?? "");
    $password = $_POST["password"] ?? "";
    $role = $_POST["role"] ?? "";
    $old = ["username" => $username, "role" => $role];

    if ($username == "" || $password == "" || $role == "") {
        $message = "Please fill in all fields.";
        $messageType = "error";
    } elseif (!in_array($role, $allowedRoles, true)) {
        $message = "Please select a valid role.";
        $messageType = "error";
    } elseif (strlen($password) < 6) {
        $message = "Your password must have at least 6 characters.";
        $messageType = "error";
    } else {
        $check = $conn->prepare("SELECT user_id FROM users WHERE username = ?");
        $check->bind_param("s", $username);
        $check->execute();
        if ($check->get_result()->num_rows > 0) {
            $message = "Username already exists. Please choose another.";
            $messageType = "error";
        } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $username, $hashed, $role);
            if ($stmt->execute()) {
                $newId = $conn->insert_id;
                /* Create the matching profile so the new user can use the system right away. */
                if ($role === BAMS_CUSTOMER) {
                    $p = $conn->prepare("INSERT INTO customers (user_id, first_name, last_name) VALUES (?, ?, '')");
                    $p->bind_param("is", $newId, $username);
                    $p->execute();
                } elseif ($role === BAMS_PROVIDER) {
                    $p = $conn->prepare("INSERT INTO service_providers (user_id, first_name, last_name, specialization, availability) VALUES (?, ?, '', 'General Service', 'Monday-Friday')");
                    $p->bind_param("is", $newId, $username);
                    $p->execute();
                }
                header("Location: login.php?registered=1");
                exit();
            }
            $message = "Registration failed. Please try again.";
            $messageType = "error";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Create Account | BAMS</title>
<link rel="stylesheet" href="assets/bams.css">
</head>
<body class="role-customer">
<div class="auth">
    <section class="auth-side">
        <a class="brand" href="index.php" style="padding-left:0"><span class="brand-mark">B</span><span>BAMS</span></a>
        <h1>Join the smarter way to manage appointments.</h1>
        <p>Create your BAMS account to access a system designed to simplify appointment scheduling and business service management.</p>
        <ul class="auth-points">
            <li><span>📆</span><div><strong>Easy appointment scheduling</strong><br>Book and manage appointments more easily.</div></li>
            <li><span>👥</span><div><strong>Customer and service management</strong><br>Keep customers and providers organized.</div></li>
            <li><span>🔔</span><div><strong>Appointment notifications</strong><br>Receive updates about appointment activity.</div></li>
        </ul>
    </section>
    <section class="auth-main">
        <div class="auth-card">
            <h2>Create account</h2>
            <p class="hint" style="font-size:14.5px;margin-bottom:18px">Register to get started with BAMS.</p>
            <?php if ($message != ""): ?>
                <div class="alert alert-<?php echo e($messageType); ?>"><?php echo e($message); ?></div>
            <?php endif; ?>
            <form method="POST" action="register.php" class="grid" style="gap:16px">
                <div class="field"><label for="username">Username</label>
                    <input id="username" type="text" name="username" placeholder="Choose a username" maxlength="50" autocomplete="username" value="<?php echo e($old["username"]); ?>" required></div>
                <div class="field"><label for="password">Password</label>
                    <input id="password" type="password" name="password" placeholder="At least 6 characters" autocomplete="new-password" required>
                    <span class="hint">Use at least 6 characters.</span></div>
                <div class="field"><label>I am a...</label>
                    <div class="role-pick">
                        <?php foreach ($allowedRoles as $r): ?>
                            <label><input type="radio" name="role" value="<?php echo e($r); ?>" <?php echo $old["role"] === $r ? "checked" : ""; ?> required> <?php echo e($r); ?></label>
                        <?php endforeach; ?>
                    </div>
                    <span class="hint">Administrator accounts are created by an existing administrator.</span>
                </div>
                <button class="btn" type="submit">Create account</button>
            </form>
            <div class="auth-links">Already have an account? <a href="login.php">Login</a></div>
            <div class="auth-links" style="margin-top:8px"><a href="index.php">&larr; Back to Home</a> &nbsp;&middot;&nbsp; <a href="about.php">About</a></div>
        </div>
    </section>
</div>
</body>
</html>
