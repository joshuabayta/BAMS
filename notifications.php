<?php
require_once __DIR__ . "/includes/layout.php";
require_login();

$uid = (int)$_SESSION["user_id"];

if ($_SERVER["REQUEST_METHOD"] === "POST" && csrf_ok()) {
    if (($_POST["action"] ?? "") === "read_all") {
        $conn->query("UPDATE notifications SET is_read = 1 WHERE user_id = $uid");
        flash_set("success", "All notifications were marked as read.");
    } elseif (($_POST["action"] ?? "") === "delete_read") {
        $conn->query("DELETE FROM notifications WHERE user_id = $uid AND is_read = 1");
        flash_set("success", "Read notifications were cleared.");
    }
    header("Location: notifications.php");
    exit();
}

$stmt = $conn->prepare("SELECT notification_id, appointment_id, message, notification_type, is_read, created_at FROM notifications WHERE user_id = ? ORDER BY created_at DESC, notification_id DESC LIMIT 200");
$stmt->bind_param("i", $uid);
$stmt->execute();
$items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$total = count($items);
$unread = count(array_filter($items, fn($n) => !$n["is_read"]));

layout_start("Notifications", "notifications.php");
?>
<div class="page-head">
    <div><div class="eyebrow">Inbox</div><h1>Notifications</h1><p>Updates about your appointments and the system.</p></div>
    <div class="actions">
        <?php if ($unread > 0): ?>
        <form method="POST"><?php echo csrf_field(); ?><input type="hidden" name="action" value="read_all"><button class="btn">Mark all as read</button></form>
        <?php endif; ?>
        <?php if ($total - $unread > 0): ?>
        <form method="POST" onsubmit="return confirm('Clear all read notifications?');"><?php echo csrf_field(); ?><input type="hidden" name="action" value="delete_read"><button class="btn btn-ghost">Clear read</button></form>
        <?php endif; ?>
    </div>
</div>
<div class="stats" style="grid-template-columns:repeat(3,minmax(0,1fr))">
    <div class="stat"><div class="stat-label">Total</div><div class="stat-value"><?php echo $total; ?></div></div>
    <div class="stat"><div class="stat-label">Unread</div><div class="stat-value"><?php echo $unread; ?></div></div>
    <div class="stat"><div class="stat-label">Read</div><div class="stat-value"><?php echo $total - $unread; ?></div></div>
</div>
<div class="card">
    <div class="card-head"><div class="card-title">Your messages</div></div>
    <?php if (!$items): ?>
        <div class="empty"><div class="big">🔔</div><strong>You are all caught up!</strong>New appointment updates will appear here.</div>
    <?php else: foreach ($items as $n): ?>
        <div class="list-item<?php echo !$n["is_read"] ? " unread" : ""; ?>">
            <div class="ico">🔔</div>
            <div class="grow">
                <div class="cell-title"><?php echo e($n["notification_type"]); ?> <?php echo !$n["is_read"] ? '<span class="badge b-info">New</span>' : ""; ?></div>
                <div><?php echo e($n["message"]); ?></div>
            </div>
            <time><?php echo e(date("M j, Y g:i A", strtotime($n["created_at"]))); ?></time>
        </div>
    <?php endforeach; endif; ?>
</div>
<?php
layout_end();
/* Opening the page counts as reading: clear the unread marks for next time. */
if ($unread > 0) {
    $conn->query("UPDATE notifications SET is_read = 1 WHERE user_id = $uid");
}
