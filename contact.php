<?php
require_once __DIR__ . "/includes/layout.php";
$logged = isset($_SESSION["user_id"]);
if ($logged) {
    layout_start("Help and Contact", "");
} else {
    public_start("Help and Contact", "contact");
    echo '<div class="section" style="padding-top:40px"><div style="max-width:1000px;margin:0 auto">';
}
?>
<div class="page-head">
    <div><div class="eyebrow">Support</div><h1>Help and Contact</h1><p>Answers to common questions and who to ask when you need more help.</p></div>
</div>
<div class="grid grid-2">
    <div class="card">
        <div class="card-head"><div class="card-title">Frequently asked questions</div></div>
        <div class="card-body">
            <h4>How do I book an appointment?</h4>
            <p class="hint" style="font-size:14px">Log in as a Customer, open <b>Book Appointment</b>, choose a service, a provider, the date and the time.</p>
            <h4>Can I change or cancel a booking?</h4>
            <p class="hint" style="font-size:14px">Yes. Open <b>My Appointments</b> and use Reschedule or Cancel on eligible appointments.</p>
            <h4>Who confirms my appointment?</h4>
            <p class="hint" style="font-size:14px">The service provider you booked. You will get a notification when the status changes.</p>
            <h4>I cannot open a page.</h4>
            <p class="hint" style="font-size:14px">Each role has its own pages. If a page is not in your menu, your role does not use it.</p>
            <h4>I forgot my password.</h4>
            <p class="hint" style="font-size:14px">Ask an Administrator to help you with your account.</p>
        </div>
    </div>
    <div class="card">
        <div class="card-head"><div class="card-title">Who to contact</div></div>
        <div class="card-body">
            <dl class="kv">
                <dt>Account problems</dt><dd>Your system Administrator</dd>
                <dt>Appointment questions</dt><dd>Your service provider</dd>
                <dt>Business reports</dt><dd>The Business Owner</dd>
                <dt>System</dt><dd>Business Appointment Management System (BAMS)</dd>
            </dl>
            <p style="margin-top:22px">
                <?php if ($logged): ?>
                    <a class="btn" href="<?php echo e(role_home(current_role())); ?>">Back to my dashboard</a>
                <?php else: ?>
                    <a class="btn" href="login.php">Login</a> <a class="btn btn-ghost" href="register.php">Create account</a>
                <?php endif; ?>
            </p>
        </div>
    </div>
</div>
<?php
if ($logged) {
    layout_end();
} else {
    echo '</div></div>';
    public_end();
}
