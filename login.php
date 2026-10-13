<?php
require_once __DIR__ . "/includes/bootstrap.php";

/* Already logged in? Go straight to your own dashboard. */
if (isset($_SESSION["user_id"]) && !isset($_GET["switch"])) {
    header("Location: " . role_home(current_role()));
    exit();
}

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST["username"] ?? "");
    $password = $_POST["password"] ?? "";

    $stmt = $conn->prepare("SELECT user_id, username, password, role FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user["password"])) {
            session_regenerate_id(true);
            $_SESSION["user_id"] = $user["user_id"];
            $_SESSION["username"] = $user["username"];
            $_SESSION["role"] = $user["role"];
            header("Location: " . role_home($user["role"]));
            exit();
        }
        $message = "Incorrect password.";
    } else {
        $message = "Username not found.";
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login | BAMS</title>
<link rel="stylesheet" href="assets/bams.css">
</head>
<body class="role-customer">
<div class="auth">
    <section class="auth-side">
        <a class="brand" href="index.php" style="padding-left:0"><span class="brand-mark">B</span><span>BAMS</span></a>
        <h1>Business Appointment Management System</h1>
        <p>A web-based system designed to simplify the scheduling, tracking and management of business appointments for customers and service providers.</p>
        <ul class="auth-points">
            <li><span>📅</span><div><strong>Appointment scheduling</strong></div></li>
            <li><span>👥</span><div><strong>Customer and service management</strong></div></li>
            <li><span>📊</span><div><strong>Appointment status tracking</strong></div></li>
            <li><span>🗓️</span><div><strong>Calendar and schedule management</strong></div></li>
            <li><span>🔔</span><div><strong>Notifications and reminders</strong></div></li>
        </ul>
    </section>
    <section class="auth-main">
        <div class="auth-card">
            <h2>Welcome back!</h2>
            <p class="hint" style="font-size:14.5px;margin-bottom:18px">Login to your BAMS account. You will be taken to the dashboard for your role.</p>
            <?php if (isset($_GET["registered"])): ?>
                <div class="alert alert-success">Account created. You can log in now.</div>
            <?php endif; ?>
            <?php if (isset($_GET["loggedout"])): ?>
                <div class="alert alert-info">You have been logged out.</div>
            <?php endif; ?>
            <?php if ($message != ""): ?>
                <div class="alert alert-error"><?php echo e($message); ?></div>
            <?php endif; ?>
            <form method="POST" class="grid" style="gap:16px">
                <div class="field"><label for="username">Username</label>
                    <input id="username" type="text" name="username" placeholder="Enter your username" autocomplete="username" required autofocus></div>
                <div class="field"><label for="password">Password</label>
                    <input id="password" type="password" name="password" placeholder="Enter your password" autocomplete="current-password" required></div>
                <button class="btn" type="submit">Login</button>
            </form>
            <div class="auth-links">Don't have an account? <a href="register.php">Register</a></div>
            <div class="auth-links" style="margin-top:8px"><a href="index.php">&larr; Back to Home</a> &nbsp;&middot;&nbsp; <a href="about.php">About</a> &nbsp;&middot;&nbsp; <a href="contact.php">Help</a></div>
        </div>
    </section>
</div>
</body>
</html>
