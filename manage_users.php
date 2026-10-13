<?php
require_once __DIR__ . "/includes/layout.php";
require_once __DIR__ . "/includes/data.php";
require_role([BAMS_ADMIN]);

$roles = [BAMS_ADMIN, BAMS_OWNER, BAMS_PROVIDER, BAMS_CUSTOMER];
$me = (int)$_SESSION["user_id"];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (!csrf_ok()) {
        flash_set("error", "Your session expired. Please try again.");
        header("Location: manage_users.php");
        exit();
    }
    $action = $_POST["action"] ?? "";
    try {
        if ($action === "add") {
            $u = trim($_POST["username"] ?? "");
            $pw = $_POST["password"] ?? "";
            $role = $_POST["role"] ?? "";
            if ($u === "" || strlen($pw) < 6 || !in_array($role, $roles, true)) {
                flash_set("error", "Enter a username, a role, and a password of at least 6 characters.");
            } else {
                $st = $conn->prepare("SELECT user_id FROM users WHERE username = ?");
                $st->bind_param("s", $u);
                $st->execute();
                if ($st->get_result()->num_rows > 0) {
                    flash_set("error", "That username is already taken.");
                } else {
                    $hash = password_hash($pw, PASSWORD_DEFAULT);
                    $st = $conn->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
                    $st->bind_param("sss", $u, $hash, $role);
                    $st->execute();
                    $newId = $conn->insert_id;
                    if ($role === BAMS_CUSTOMER) {
                        $p = $conn->prepare("INSERT INTO customers (user_id, first_name, last_name) VALUES (?, ?, '')");
                        $p->bind_param("is", $newId, $u);
                        $p->execute();
                    } elseif ($role === BAMS_PROVIDER) {
                        $p = $conn->prepare("INSERT INTO service_providers (user_id, first_name, last_name, specialization, availability) VALUES (?, ?, '', 'General Service', 'Monday-Friday')");
                        $p->bind_param("is", $newId, $u);
                        $p->execute();
                    }
                    flash_set("success", "Account \"$u\" was created as $role.");
                }
            }
        } elseif ($action === "role") {
            $id = (int)($_POST["user_id"] ?? 0);
            $role = $_POST["role"] ?? "";
            if ($id === $me) {
                flash_set("error", "You cannot change your own role.");
            } elseif (in_array($role, $roles, true)) {
                $st = $conn->prepare("UPDATE users SET role = ? WHERE user_id = ?");
                $st->bind_param("si", $role, $id);
                $st->execute();
                flash_set("success", "The role was updated.");
            }
        } elseif ($action === "password") {
            $id = (int)($_POST["user_id"] ?? 0);
            $pw = $_POST["password"] ?? "";
            if (strlen($pw) < 6) {
                flash_set("error", "The new password must have at least 6 characters.");
            } else {
                $hash = password_hash($pw, PASSWORD_DEFAULT);
                $st = $conn->prepare("UPDATE users SET password = ? WHERE user_id = ?");
                $st->bind_param("si", $hash, $id);
                $st->execute();
                flash_set("success", "The password was reset.");
            }
        } elseif ($action === "delete") {
            $id = (int)($_POST["user_id"] ?? 0);
            if ($id === $me) {
                flash_set("error", "You cannot delete your own account.");
            } else {
                $busy = db_count($conn, "SELECT COUNT(*) FROM appointments a
                    LEFT JOIN customers c ON a.customer_id = c.customer_id
                    LEFT JOIN service_providers p ON a.provider_id = p.provider_id
                    WHERE c.user_id = $id OR p.user_id = $id");
                if ($busy > 0) {
                    flash_set("error", "This account has $busy appointment(s), so it cannot be deleted.");
                } else {
                    $conn->query("DELETE FROM notifications WHERE user_id = $id");
                    $conn->query("DELETE FROM customers WHERE user_id = $id");
                    $conn->query("DELETE FROM service_providers WHERE user_id = $id");
                    $st = $conn->prepare("DELETE FROM users WHERE user_id = ?");
                    $st->bind_param("i", $id);
                    $st->execute();
                    flash_set("success", "The account was deleted.");
                }
            }
        }
    } catch (Throwable $ex) {
        flash_set("error", "That action could not be completed. This account may still have appointments or records linked to it.");
    }
    header("Location: manage_users.php");
    exit();
}

$q = trim($_GET["q"] ?? "");
$roleFilter = $_GET["role"] ?? "";
$sql = "SELECT user_id, username, role, created_at FROM users WHERE 1=1";
$types = "";
$params = [];
if ($q !== "") {
    $sql .= " AND username LIKE ?";
    $types .= "s";
    $params[] = "%$q%";
}
if (in_array($roleFilter, $roles, true)) {
    $sql .= " AND role = ?";
    $types .= "s";
    $params[] = $roleFilter;
}
$sql .= " ORDER BY user_id ASC";
$st = $conn->prepare($sql);
if ($params) {
    $st->bind_param($types, ...$params);
}
$st->execute();
$users = $st->get_result()->fetch_all(MYSQLI_ASSOC);
$counts = [];
$r = $conn->query("SELECT role, COUNT(*) c FROM users GROUP BY role");
while ($r && $row = $r->fetch_assoc()) {
    $counts[$row["role"]] = (int)$row["c"];
}
function role_badge(string $role): string
{
    $map = [BAMS_ADMIN => "b-info", BAMS_OWNER => "b-wait", BAMS_PROVIDER => "b-good", BAMS_CUSTOMER => "b-muted"];
    return '<span class="badge ' . ($map[$role] ?? "b-muted") . '">' . e($role) . "</span>";
}

layout_start("All Users", "manage_users.php");
?>
<div class="page-head">
    <div><div class="eyebrow">Administration</div><h1>All Users</h1><p>Create accounts, change roles and reset passwords.</p></div>
</div>
<div class="stats">
    <?php foreach ($roles as $rl): ?>
        <a class="stat" href="manage_users.php?role=<?php echo urlencode($rl); ?>" style="color:inherit;text-decoration:none">
            <div class="stat-label"><?php echo e($rl); ?>s</div><div class="stat-value"><?php echo (int)($counts[$rl] ?? 0); ?></div>
        </a>
    <?php endforeach; ?>
</div>

<div class="card">
    <div class="card-head"><div class="card-title">Create a new account</div></div>
    <div class="card-body">
        <form method="POST" class="form-grid" style="grid-template-columns:repeat(4,minmax(0,1fr));align-items:end">
            <?php echo csrf_field(); ?><input type="hidden" name="action" value="add">
            <div class="field"><label>Username</label><input name="username" required maxlength="50"></div>
            <div class="field"><label>Password</label><input type="password" name="password" required minlength="6"></div>
            <div class="field"><label>Role</label>
                <select name="role"><?php foreach ($roles as $rl): ?><option><?php echo e($rl); ?></option><?php endforeach; ?></select></div>
            <button class="btn" type="submit">Create account</button>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-head">
        <div class="card-title"><?php echo count($users); ?> account<?php echo count($users) === 1 ? "" : "s"; ?></div>
        <form class="search-bar" method="GET">
            <input name="q" placeholder="Search username" value="<?php echo e($q); ?>">
            <select name="role"><option value="">All roles</option><?php foreach ($roles as $rl): ?><option <?php echo $roleFilter === $rl ? "selected" : ""; ?>><?php echo e($rl); ?></option><?php endforeach; ?></select>
            <button class="btn btn-sm" type="submit">Search</button>
            <?php if ($q !== "" || $roleFilter !== ""): ?><a class="btn btn-ghost btn-sm" href="manage_users.php">Clear</a><?php endif; ?>
        </form>
    </div>
    <?php if (!$users): ?>
        <div class="empty"><div class="big">🔎</div><strong>No users found</strong></div>
    <?php else: ?>
    <div class="table-wrap"><table>
        <thead><tr><th>User</th><th>Role</th><th>Created</th><th>Change role</th><th>Reset password</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($users as $u): $isMe = (int)$u["user_id"] === $me; ?>
            <tr>
                <td><div class="cell-user"><div class="mini-avatar"><?php echo e(initial($u["username"])); ?></div>
                    <div><div class="cell-title"><?php echo e($u["username"]); ?><?php echo $isMe ? " (you)" : ""; ?></div><div class="cell-sub">ID <?php echo (int)$u["user_id"]; ?></div></div></div></td>
                <td><?php echo role_badge($u["role"]); ?></td>
                <td style="white-space:nowrap"><?php echo e(fmt_date_safe($u["created_at"])); ?></td>
                <td>
                    <?php if (!$isMe): ?>
                    <form method="POST" class="search-bar" style="flex-wrap:nowrap"><?php echo csrf_field(); ?><input type="hidden" name="action" value="role"><input type="hidden" name="user_id" value="<?php echo (int)$u["user_id"]; ?>">
                        <select name="role" style="min-width:120px;width:150px"><?php foreach ($roles as $rl): ?><option <?php echo $u["role"] === $rl ? "selected" : ""; ?>><?php echo e($rl); ?></option><?php endforeach; ?></select>
                        <button class="btn btn-ghost btn-sm">Save</button></form>
                    <?php else: ?><span class="cell-sub">-</span><?php endif; ?>
                </td>
                <td>
                    <form method="POST" class="search-bar" style="flex-wrap:nowrap"><?php echo csrf_field(); ?><input type="hidden" name="action" value="password"><input type="hidden" name="user_id" value="<?php echo (int)$u["user_id"]; ?>">
                        <input type="password" name="password" placeholder="New password" minlength="6" style="min-width:100px;width:130px" required>
                        <button class="btn btn-ghost btn-sm">Reset</button></form>
                </td>
                <td>
                    <?php if (!$isMe): ?>
                    <form method="POST" onsubmit="return confirm('Delete this account?');"><?php echo csrf_field(); ?><input type="hidden" name="action" value="delete"><input type="hidden" name="user_id" value="<?php echo (int)$u["user_id"]; ?>">
                        <button class="btn btn-danger btn-sm">Delete</button></form>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table></div>
    <?php endif; ?>
</div>
<?php
function fmt_date_safe($d) { return $d ? date("M j, Y", strtotime($d)) : "-"; }
layout_end();
