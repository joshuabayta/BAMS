<?php
require_once __DIR__ . "/includes/layout.php";
require_once __DIR__ . "/includes/data.php";
require_role([BAMS_PROVIDER]);

$uid = (int)$_SESSION["user_id"];
$stmt = $conn->prepare("SELECT provider_id FROM service_providers WHERE user_id = ?");
$stmt->bind_param("i", $uid);
$stmt->execute();
$provider = $stmt->get_result()->fetch_assoc();

$clients = [];
if ($provider) {
    $pid = (int)$provider["provider_id"];
    $sql = "SELECT c.customer_id, c.first_name, c.last_name, c.contact_number, c.email, c.address, u.username,
                   COUNT(a.appointment_id) AS visits,
                   SUM(a.status = 'Completed') AS done,
                   MAX(a.appointment_date) AS last_date
            FROM appointments a
            INNER JOIN customers c ON a.customer_id = c.customer_id
            LEFT JOIN users u ON c.user_id = u.user_id
            WHERE a.provider_id = $pid
            GROUP BY c.customer_id, c.first_name, c.last_name, c.contact_number, c.email, c.address, u.username
            ORDER BY last_date DESC";
    $clients = $conn->query($sql)->fetch_all(MYSQLI_ASSOC);
}
layout_start("My Clients", "client_details.php");
?>
<div class="page-head">
    <div><div class="eyebrow">Service provider</div><h1>My Clients</h1><p>Customers who have booked with you, with their contact details.</p></div>
</div>
<div class="stats" style="grid-template-columns:repeat(3,minmax(0,1fr))">
    <div class="stat"><div class="stat-label">Clients</div><div class="stat-value"><?php echo count($clients); ?></div></div>
    <div class="stat"><div class="stat-label">Total visits</div><div class="stat-value"><?php echo array_sum(array_map(fn($c) => (int)$c["visits"], $clients)); ?></div></div>
    <div class="stat"><div class="stat-label">Completed visits</div><div class="stat-value"><?php echo array_sum(array_map(fn($c) => (int)$c["done"], $clients)); ?></div></div>
</div>
<div class="card">
    <div class="card-head">
        <div class="card-title">Client directory</div>
        <div class="search-bar"><input id="clientSearch" type="search" placeholder="Search name, phone, email..." aria-label="Search clients"></div>
    </div>
    <?php if (!$clients): ?>
        <div class="empty"><div class="big">👥</div><strong>No clients yet</strong>Customers appear here after they book with you.</div>
    <?php else: ?>
    <div class="table-wrap"><table id="clientTable">
        <thead><tr><th>Client</th><th>Phone</th><th>Email</th><th>Address</th><th>Visits</th><th>Last visit</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($clients as $c): $name = trim($c["first_name"] . " " . $c["last_name"]); if ($name === "") $name = (string)$c["username"]; ?>
            <tr data-search="<?php echo e(strtolower($name . " " . $c["contact_number"] . " " . $c["email"] . " " . $c["address"])); ?>">
                <td><div class="cell-user"><div class="mini-avatar"><?php echo e(initial($name)); ?></div><div class="cell-title"><?php echo e($name); ?></div></div></td>
                <td><?php echo e($c["contact_number"] ?: "Not provided"); ?></td>
                <td><?php echo e($c["email"] ?: "Not provided"); ?></td>
                <td><?php echo e($c["address"] ?: "Not provided"); ?></td>
                <td><span class="badge b-info"><?php echo (int)$c["visits"]; ?></span></td>
                <td><?php echo e(fmt_date($c["last_date"])); ?></td>
                <td><?php if ($c["contact_number"]): ?><a class="btn btn-ghost btn-sm" href="tel:<?php echo e($c["contact_number"]); ?>">Call</a><?php endif; ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table></div>
    <?php endif; ?>
</div>
<script>
document.getElementById("clientSearch")?.addEventListener("input", function () {
    var q = this.value.toLowerCase().trim();
    document.querySelectorAll("#clientTable tbody tr").forEach(function (tr) {
        tr.style.display = tr.dataset.search.indexOf(q) === -1 ? "none" : "";
    });
});
</script>
<?php layout_end(); ?>
