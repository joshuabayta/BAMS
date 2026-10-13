<?php
require_once __DIR__ . "/includes/layout.php";
require_once __DIR__ . "/includes/data.php";
require_role([BAMS_ADMIN]);

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (!csrf_ok()) {
        flash_set("error", "Your session expired. Please try again.");
    } else {
        $action = $_POST["action"] ?? "";
        try {
            if ($action === "add" || $action === "edit") {
                $name = trim($_POST["service_name"] ?? "");
                $desc = trim($_POST["description"] ?? "");
                $dur = max(5, (int)($_POST["duration"] ?? 60));
                $status = ($_POST["status"] ?? "Available") === "Unavailable" ? "Unavailable" : "Available";
                if ($name === "") {
                    flash_set("error", "Please enter a service name.");
                } elseif ($action === "add") {
                    $st = $conn->prepare("INSERT INTO services (service_name, description, duration, status) VALUES (?, ?, ?, ?)");
                    $st->bind_param("ssis", $name, $desc, $dur, $status);
                    $st->execute();
                    flash_set("success", "Service \"$name\" was added.");
                } else {
                    $id = (int)$_POST["service_id"];
                    $st = $conn->prepare("UPDATE services SET service_name = ?, description = ?, duration = ?, status = ? WHERE service_id = ?");
                    $st->bind_param("ssisi", $name, $desc, $dur, $status, $id);
                    $st->execute();
                    flash_set("success", "The service was updated.");
                }
            } elseif ($action === "delete") {
                $id = (int)$_POST["service_id"];
                $used = db_count($conn, "SELECT COUNT(*) FROM appointments WHERE service_id = $id");
                if ($used > 0) {
                    flash_set("error", "This service has $used appointment(s). Set it to Unavailable instead of deleting it.");
                } else {
                    $st = $conn->prepare("DELETE FROM services WHERE service_id = ?");
                    $st->bind_param("i", $id);
                    $st->execute();
                    flash_set("success", "The service was deleted.");
                }
            }
        } catch (Throwable $ex) {
            flash_set("error", "That action could not be completed.");
        }
    }
    header("Location: manage_services.php");
    exit();
}
$rows = $conn->query("SELECT s.*, (SELECT COUNT(*) FROM appointments a WHERE a.service_id = s.service_id) AS appts FROM services s ORDER BY s.service_id ASC")->fetch_all(MYSQLI_ASSOC);
$edit = null;
if (isset($_GET["edit"])) {
    foreach ($rows as $r) {
        if ((int)$r["service_id"] === (int)$_GET["edit"]) {
            $edit = $r;
        }
    }
}
layout_start("Services", "manage_services.php");
?>
<div class="page-head">
    <div><div class="eyebrow">Administration</div><h1>Services</h1><p>The services customers can book.</p></div>
</div>
<div class="split">
    <div class="card">
        <div class="card-head"><div class="card-title"><?php echo count($rows); ?> service<?php echo count($rows) === 1 ? "" : "s"; ?></div></div>
        <?php if (!$rows): ?>
            <div class="empty"><div class="big">🛠️</div><strong>No services yet</strong>Add your first service on the right.</div>
        <?php else: ?>
        <div class="table-wrap"><table>
            <thead><tr><th>Service</th><th>Duration</th><th>Status</th><th>Booked</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($rows as $r): ?>
                <tr>
                    <td><div class="cell-title"><?php echo e($r["service_name"]); ?></div><div class="cell-sub"><?php echo e($r["description"]); ?></div></td>
                    <td><?php echo (int)$r["duration"]; ?> min</td>
                    <td><?php echo status_badge((string)$r["status"]); ?></td>
                    <td><?php echo (int)$r["appts"]; ?></td>
                    <td><div class="row-actions">
                        <a class="btn btn-ghost btn-sm" href="manage_services.php?edit=<?php echo (int)$r["service_id"]; ?>">Edit</a>
                        <form method="POST" onsubmit="return confirm('Delete this service?');"><?php echo csrf_field(); ?><input type="hidden" name="action" value="delete"><input type="hidden" name="service_id" value="<?php echo (int)$r["service_id"]; ?>"><button class="btn btn-danger btn-sm">Delete</button></form>
                    </div></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table></div>
        <?php endif; ?>
    </div>
    <div class="card">
        <div class="card-head"><div class="card-title"><?php echo $edit ? "Edit service" : "Add a service"; ?></div></div>
        <div class="card-body">
            <form method="POST" class="grid" style="gap:14px">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="action" value="<?php echo $edit ? "edit" : "add"; ?>">
                <?php if ($edit): ?><input type="hidden" name="service_id" value="<?php echo (int)$edit["service_id"]; ?>"><?php endif; ?>
                <div class="field"><label>Service name</label><input name="service_name" required maxlength="100" value="<?php echo e($edit["service_name"] ?? ""); ?>"></div>
                <div class="field"><label>Description</label><textarea name="description"><?php echo e($edit["description"] ?? ""); ?></textarea></div>
                <div class="field"><label>Duration (minutes)</label><input type="number" name="duration" min="5" step="5" value="<?php echo (int)($edit["duration"] ?? 60); ?>"></div>
                <div class="field"><label>Status</label><select name="status"><option>Available</option><option <?php echo ($edit["status"] ?? "") === "Unavailable" ? "selected" : ""; ?>>Unavailable</option></select></div>
                <button class="btn" type="submit"><?php echo $edit ? "Save changes" : "Add service"; ?></button>
                <?php if ($edit): ?><a class="btn btn-ghost" href="manage_services.php">Cancel</a><?php endif; ?>
            </form>
        </div>
    </div>
</div>
<?php layout_end(); ?>
