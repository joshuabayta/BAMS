<?php
/*
 * Page layouts.
 *   layout_start("Title", "file.php")  ...content...  layout_end()      logged-in pages
 *   public_start("Title", "key") ...  public_end()                      index / about / contact
 *   auth_start / auth_end                                               login / register
 */
require_once __DIR__ . "/bootstrap.php";

function layout_start(string $title, string $active = "", string $subtitle = ""): void
{
    global $conn;
    $role = current_role();
    $user = current_username();
    $unread = unread_count($conn);
    $flash = flash_take();
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo e($title); ?> | BAMS</title>
<link rel="stylesheet" href="assets/bams.css">
</head>
<body class="<?php echo e(role_class($role)); ?>">
<div class="shell">
    <aside class="sidebar" id="sidebar">
        <a class="brand" href="<?php echo e(role_home($role)); ?>"><span class="brand-mark">B</span><span>BAMS</span></a>
        <span class="role-chip"><?php echo e($role); ?></span>
        <?php foreach (role_menu($role) as $group): ?>
            <div class="nav-heading"><?php echo e($group[0]); ?></div>
            <?php foreach ($group[1] as $item): ?>
                <a class="nav-link<?php echo $active === $item[0] ? " active" : ""; ?>" href="<?php echo e($item[0]); ?>">
                    <span class="nav-ico"><?php echo $item[2]; ?></span><span><?php echo e($item[1]); ?></span>
                </a>
            <?php endforeach; ?>
        <?php endforeach; ?>
        <div class="sidebar-bottom">
            <a class="nav-link" href="about.php"><span class="nav-ico">ℹ️</span><span>About BAMS</span></a>
            <a class="nav-link" href="contact.php"><span class="nav-ico">✉️</span><span>Help and Contact</span></a>
            <a class="nav-link" href="logout.php"><span class="nav-ico">🚪</span><span>Logout</span></a>
        </div>
    </aside>
    <div class="main">
        <header class="topbar">
            <button class="menu-btn" type="button" onclick="document.getElementById('sidebar').classList.toggle('open')" aria-label="Menu">☰</button>
            <div class="crumb">BAMS &rsaquo; <b><?php echo e($title); ?></b></div>
            <a class="bell" href="notifications.php" title="Notifications">🔔<?php if ($unread > 0): ?><span class="dot"><?php echo $unread > 99 ? "99+" : $unread; ?></span><?php endif; ?></a>
            <div class="profile">
                <div class="avatar"><?php echo e(initial($user)); ?></div>
                <div><div class="profile-name"><?php echo e($user); ?></div><div class="profile-role"><?php echo e($role); ?></div></div>
            </div>
        </header>
        <main class="content">
        <?php if ($flash): ?>
            <div class="alert alert-<?php echo e($flash["type"]); ?>"><?php echo e($flash["message"]); ?></div>
        <?php endif; ?>
    <?php
}

function layout_end(): void
{
    ?>
        </main>
        <footer class="foot">
            <span>Business Appointment Management System &copy; <?php echo date("Y"); ?></span>
            <span><a href="about.php">About</a> &nbsp;&middot;&nbsp; <a href="contact.php">Help and Contact</a> &nbsp;&middot;&nbsp; <a href="logout.php">Logout</a></span>
        </footer>
    </div>
</div>
<script>
document.addEventListener("click", function (ev) {
    var sb = document.getElementById("sidebar");
    if (sb && sb.classList.contains("open") && !sb.contains(ev.target) && !ev.target.closest(".menu-btn")) { sb.classList.remove("open"); }
});
</script>
</body>
</html>
    <?php
}

/* ---------- public pages ---------- */
function public_start(string $title, string $active = ""): void
{
    $logged = isset($_SESSION["user_id"]);
    $role = current_role();
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo e($title); ?> | BAMS</title>
<link rel="stylesheet" href="assets/bams.css">
</head>
<body class="role-customer">
<nav class="pub-nav">
    <a class="brand" href="index.php"><span class="brand-mark">B</span><span>BAMS</span></a>
    <div class="pub-links">
        <a href="index.php"<?php echo $active === "home" ? ' class="active"' : ""; ?>>Home</a>
        <a href="index.php#features">Features</a>
        <a href="index.php#roles">Who it is for</a>
        <a href="about.php"<?php echo $active === "about" ? ' class="active"' : ""; ?>>About</a>
        <a href="contact.php"<?php echo $active === "contact" ? ' class="active"' : ""; ?>>Contact</a>
        <?php if ($logged): ?>
            <a class="btn" href="<?php echo e(role_home($role)); ?>">My Dashboard</a>
            <a class="btn btn-ghost" href="logout.php" style="color:var(--ink)">Logout</a>
        <?php else: ?>
            <a href="login.php">Login</a>
            <a class="btn" href="register.php">Create Account</a>
        <?php endif; ?>
    </div>
</nav>
    <?php
}

function public_end(): void
{
    ?>
<footer class="pub-foot">
    <div>
        <div class="brand" style="color:#fff;padding:0 0 8px"><span class="brand-mark">B</span><span>BAMS</span></div>
        <div>Business Appointment Management System<br>Book, manage and track appointments in one place.</div>
    </div>
    <div>
        <h4>Explore</h4>
        <a href="index.php">Home</a><br><a href="about.php">About</a><br><a href="contact.php">Contact</a>
    </div>
    <div>
        <h4>Account</h4>
        <a href="login.php">Login</a><br><a href="register.php">Create Account</a>
    </div>
    <div style="align-self:flex-end">&copy; <?php echo date("Y"); ?> BAMS. All rights reserved.</div>
</footer>
</body>
</html>
    <?php
}
