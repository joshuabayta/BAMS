<?php
require_once __DIR__ . "/includes/layout.php";
public_start("About", "about");
?>
<header class="pub-hero" style="padding:70px 5vw 80px">
    <div class="eyebrow" style="color:#9ec1ff">About BAMS</div>
    <h1>Making appointments simple for every business.</h1>
    <p>The Business Appointment Management System automates the scheduling, tracking and management of appointments between businesses, customers and service providers.</p>
</header>

<section class="section">
    <div class="section-head">
        <div class="eyebrow">What the system does</div>
        <h2>Built around real appointment work</h2>
    </div>
    <div class="feature-grid">
        <div class="feature"><div class="ico">📅</div><h3>Scheduling</h3><p>Choose a service, a provider, a date and a time. The system records the booking right away.</p></div>
        <div class="feature"><div class="ico">🔁</div><h3>Reschedule and cancel</h3><p>Customers can change or cancel eligible appointments without calling anyone.</p></div>
        <div class="feature"><div class="ico">✅</div><h3>Status tracking</h3><p>Booked, Confirmed, Rescheduled, Completed or Cancelled: everyone sees the current status.</p></div>
        <div class="feature"><div class="ico">🗓️</div><h3>Calendar</h3><p>A monthly calendar shows scheduled appointments and their statuses.</p></div>
        <div class="feature"><div class="ico">🔔</div><h3>Notifications</h3><p>Each time a booking is made or a status changes, the customer gets a message.</p></div>
        <div class="feature"><div class="ico">📈</div><h3>Reports</h3><p>Statistics help owners and administrators understand business activity.</p></div>
    </div>
</section>

<section class="section alt">
    <div class="section-head">
        <div class="eyebrow">Roles</div>
        <h2>Who uses BAMS and where they go</h2>
        <p>After login every role is sent to its own home page.</p>
    </div>
    <div class="card" style="max-width:1000px;margin:0 auto">
        <div class="table-wrap"><table>
            <thead><tr><th>Role</th><th>Goes to after login</th><th>Can do</th></tr></thead>
            <tbody>
                <tr><td><span class="badge b-info">Customer</span></td><td>Customer Dashboard</td><td>Book, reschedule, cancel, calendar, notifications, profile</td></tr>
                <tr><td><span class="badge b-good">Service Provider</span></td><td>Provider Dashboard</td><td>Assigned appointments, update status, schedule, client details</td></tr>
                <tr><td><span class="badge b-wait">Business Owner</span></td><td>Owner Dashboard</td><td>Business overview, appointments, staff, reports, calendar</td></tr>
                <tr><td><span class="badge b-muted">Administrator</span></td><td>Admin Dashboard</td><td>Users, customers, providers, owners, services, reports</td></tr>
            </tbody>
        </table></div>
    </div>
</section>

<section class="section">
    <div class="section-head">
        <div class="eyebrow">Technology</div>
        <h2>Built with</h2>
        <p>PHP and MySQL (MariaDB) with a responsive HTML and CSS interface. Passwords are stored securely with hashing, and every page checks your role before it opens.</p>
    </div>
    <p style="text-align:center">
        <a class="btn" href="register.php">Create an account</a>
        <a class="btn btn-ghost" href="contact.php">Help and contact</a>
    </p>
</section>
<?php public_end(); ?>
