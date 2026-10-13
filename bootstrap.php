<?php
/*
 * BAMS bootstrap: session, database, role helpers.
 * Every page starts with:  require_once __DIR__ . "/includes/bootstrap.php";
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . "/../db.php";

const BAMS_ADMIN    = "Administrator";
const BAMS_OWNER    = "Business Owner";
const BAMS_PROVIDER = "Service Provider";
const BAMS_CUSTOMER = "Customer";

function e($value): string
{
    return htmlspecialchars((string)($value ?? ""), ENT_QUOTES, "UTF-8");
}

/* Where each role lands after login (and when they open the wrong page). */
function role_home(string $role): string
{
    switch ($role) {
        case BAMS_ADMIN:    return "admin_dashboard.php";
        case BAMS_OWNER:    return "owner_dashboard.php";
        case BAMS_PROVIDER: return "dashboard.php";
        case BAMS_CUSTOMER: return "dashboard.php";
    }
    return "login.php";
}

function role_class(string $role): string
{
    switch ($role) {
        case BAMS_ADMIN:    return "role-admin";
        case BAMS_OWNER:    return "role-owner";
        case BAMS_PROVIDER: return "role-provider";
    }
    return "role-customer";
}

function flash_set(string $type, string $message): void
{
    $_SESSION["flash"] = ["type" => $type, "message" => $message];
}

function flash_take(): ?array
{
    if (!empty($_SESSION["flash"])) {
        $f = $_SESSION["flash"];
        unset($_SESSION["flash"]);
        return $f;
    }
    return null;
}

function require_login(): void
{
    if (!isset($_SESSION["user_id"])) {
        header("Location: login.php");
        exit();
    }
}

/* Allow only the given roles. Anyone else is sent to their own home page. */
function require_role(array $roles): void
{
    require_login();
    $role = $_SESSION["role"] ?? "";
    if (!in_array($role, $roles, true)) {
        flash_set("error", "You do not have access to that page, so we brought you back to your dashboard.");
        header("Location: " . role_home($role));
        exit();
    }
}

function current_role(): string
{
    return $_SESSION["role"] ?? "";
}

function current_username(): string
{
    return $_SESSION["username"] ?? "";
}

/* Sidebar menu for each role: [file, label, icon] */
function role_menu(string $role): array
{
    switch ($role) {
        case BAMS_ADMIN:
            return [
                ["Overview", [["admin_dashboard.php", "Dashboard", "🏠"], ["owner_appointments.php", "Appointments", "📋"], ["calendar.php", "Calendar", "📆"], ["reports.php", "Reports", "📈"]]],
                ["People", [["manage_users.php", "All Users", "👤"], ["manage_customers.php", "Customers", "🧑"], ["manage_providers.php", "Service Providers", "🧑‍💼"], ["manage_owners.php", "Business Owners", "🏢"], ["staff.php", "Staff", "👥"]]],
                ["Business", [["manage_services.php", "Services", "🛠️"], ["notifications.php", "Notifications", "🔔"]]],
            ];
        case BAMS_OWNER:
            return [
                ["Business", [["owner_dashboard.php", "Dashboard", "🏠"], ["owner_appointments.php", "Appointments", "📋"], ["calendar.php", "Calendar", "📆"]]],
                ["Team and reports", [["staff.php", "Staff", "👥"], ["reports.php", "Reports", "📈"], ["notifications.php", "Notifications", "🔔"]]],
            ];
        case BAMS_PROVIDER:
            return [
                ["My work", [["dashboard.php", "Dashboard", "🏠"], ["provider_appointments.php", "My Appointments", "📋"], ["my_schedule.php", "My Schedule", "🗓️"], ["client_details.php", "My Clients", "👥"], ["calendar.php", "Calendar", "📆"]]],
                ["Account", [["notifications.php", "Notifications", "🔔"]]],
            ];
    }
    return [
        ["Appointments", [["dashboard.php", "Dashboard", "🏠"], ["book_appointment.php", "Book Appointment", "➕"], ["my_appointments.php", "My Appointments", "📋"], ["calendar.php", "Calendar", "📆"]]],
        ["Account", [["customer_profile.php", "My Profile", "👤"], ["notifications.php", "Notifications", "🔔"]]],
    ];
}

/* Status text -> badge colour class */
function status_badge(string $status): string
{
    $s = strtolower(trim($status));
    $cls = "b-info";
    if (in_array($s, ["confirmed", "completed", "available", "active"], true)) {
        $cls = "b-good";
    } elseif (in_array($s, ["booked", "rescheduled", "pending"], true)) {
        $cls = "b-wait";
    } elseif (in_array($s, ["cancelled", "canceled", "rejected", "unavailable", "inactive"], true)) {
        $cls = "b-bad";
    }
    return '<span class="badge ' . $cls . '">' . e($status === "" ? "Unknown" : $status) . "</span>";
}

function initial(string $name): string
{
    $name = trim($name);
    if ($name === "") {
        return "?";
    }
    $first = function_exists("mb_substr") ? mb_substr($name, 0, 1) : substr($name, 0, 1);
    return strtoupper($first);
}

function unread_count(mysqli $conn): int
{
    if (!isset($_SESSION["user_id"])) {
        return 0;
    }
    $uid = (int)$_SESSION["user_id"];
    $r = $conn->query("SELECT COUNT(*) c FROM notifications WHERE user_id = $uid AND is_read = 0");
    return $r ? (int)$r->fetch_assoc()["c"] : 0;
}

/* Simple CSRF protection for POST forms */
function csrf_token(): string
{
    if (empty($_SESSION["csrf"])) {
        $_SESSION["csrf"] = bin2hex(random_bytes(16));
    }
    return $_SESSION["csrf"];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

function csrf_ok(): bool
{
    return isset($_POST["csrf"], $_SESSION["csrf"]) && hash_equals($_SESSION["csrf"], (string)$_POST["csrf"]);
}
