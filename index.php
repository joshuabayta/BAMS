<?php
require_once __DIR__ . "/includes/layout.php";
public_start("Business Appointment Management System", "home");
?>
<header class="pub-hero">
    <div class="eyebrow" style="color:#9ec1ff">Smart scheduling &bull; Better business</div>
    <h1>Book, manage and track business appointments in one place.</h1>
    <p>BAMS is a web-based system that simplifies the scheduling, tracking and management of appointments for customers, service providers, business owners and administrators.</p>
    <p style="margin-top:26px;display:flex;gap:12px;flex-wrap:wrap">
        <a class="btn" href="register.php">Get started &rarr;</a>
        <a class="btn btn-ghost" href="login.php">Login</a>
        <a class="btn btn-ghost" href="about.php">Learn more</a>
    </p>
</header>

<section class="section" id="features">
    <div class="section-head">
        <div class="eyebrow">Key features</div>
        <h2>Everything you need to run your appointments</h2>
        <p>One system for booking, scheduling, tracking, notifying and reporting.</p>
    </div>
    <div class="feature-grid">
        <div class="feature"><div class="ico">📅</div><h3>Appointment Scheduling</h3><p>Customers book a service with their chosen provider in a few clicks.</p></div>
        <div class="feature"><div class="ico">👥</div><h3>Customer and Service Management</h3><p>Keep customer details, providers and services organized.</p></div>
        <div class="feature"><div class="ico">📊</div><h3>Status Tracking</h3><p>Follow every appointment from Booked to Confirmed to Completed.</p></div>
        <div class="feature"><div class="ico">🗓️</div><h3>Calendar Management</h3><p>See all schedules in a clear monthly calendar.</p></div>
        <div class="feature"><div class="ico">🔔</div><h3>Notifications</h3><p>Customers and staff are told when something changes.</p></div>
        <div class="feature"><div class="ico">📈</div><h3>Reports</h3><p>Owners and administrators review statistics and business reports.</p></div>
    </div>
</section>

<section class="section alt" id="roles">
    <div class="section-head">
        <div class="eyebrow">Who it is for</div>
        <h2>A dashboard for every role</h2>
        <p>After you log in, BAMS takes you straight to the right place for your role.</p>
    </div>
    <div class="role-grid">
        <div class="role-card r-customer"><h3>🧑 Customer</h3><ul><li>Book appointments</li><li>Reschedule or cancel</li><li>Read notifications</li></ul></div>
        <div class="role-card r-provider"><h3>🧑‍💼 Service Provider</h3><ul><li>See assigned appointments</li><li>Update statuses</li><li>View your schedule and clients</li></ul></div>
        <div class="role-card r-owner"><h3>🏢 Business Owner</h3><ul><li>Business overview</li><li>Staff and appointments</li><li>Reports</li></ul></div>
        <div class="role-card r-admin"><h3>🛡️ Administrator</h3><ul><li>Manage users and records</li><li>Manage services</li><li>Reports and oversight</li></ul></div>
    </div>
</section>

<section class="section">
    <div class="section-head">
        <div class="eyebrow">How it works</div>
        <h2>Simple in four steps</h2>
    </div>
    <div class="steps">
        <div class="step"><h3>Create an account</h3><p>Register and choose your role.</p></div>
        <div class="step"><h3>Log in</h3><p>You land on the dashboard made for you.</p></div>
        <div class="step"><h3>Book or manage</h3><p>Customers book; providers confirm and complete.</p></div>
        <div class="step"><h3>Stay informed</h3><p>Notifications and the calendar keep everyone on time.</p></div>
    </div>
</section>

<section class="section alt" style="text-align:center">
    <div class="section-head" style="margin-bottom:20px">
        <h2>Ready to get started?</h2>
        <p>Join now and experience a simpler way to manage your business appointments.</p>
    </div>
    <a class="btn" href="register.php">Register now &rarr;</a>
    <a class="btn btn-ghost" href="login.php">Login</a>
</section>
<?php public_end(); ?>
