<?php
/* Small shared data helpers used by several dashboards. */
require_once __DIR__ . "/bootstrap.php";

function db_count(mysqli $conn, string $sql): int
{
    try {
        $r = $conn->query($sql);
        if ($r) {
            $row = $r->fetch_row();
            return (int)($row[0] ?? 0);
        }
    } catch (Throwable $e) {
    }
    return 0;
}

function status_counts(mysqli $conn, string $where = ""): array
{
    $out = [];
    try {
        $r = $conn->query("SELECT status, COUNT(*) c FROM appointments a $where GROUP BY status");
        while ($r && $row = $r->fetch_assoc()) {
            $out[$row["status"]] = (int)$row["c"];
        }
    } catch (Throwable $e) {
    }
    return $out;
}

/* Appointment rows with names. $where must already be a safe SQL string. */
function appointment_rows(mysqli $conn, string $where = "", string $order = "a.appointment_date DESC, a.appointment_time DESC", int $limit = 0): array
{
    $sql = "SELECT a.appointment_id, a.appointment_date, a.appointment_time, a.status, a.created_at,
                   a.customer_id, a.provider_id, a.service_id,
                   TRIM(CONCAT(IFNULL(c.first_name,''),' ',IFNULL(c.last_name,''))) AS customer_name,
                   cu.username AS customer_user,
                   TRIM(CONCAT(IFNULL(p.first_name,''),' ',IFNULL(p.last_name,''))) AS provider_name,
                   s.service_name, s.duration
            FROM appointments a
            LEFT JOIN customers c ON a.customer_id = c.customer_id
            LEFT JOIN users cu ON c.user_id = cu.user_id
            LEFT JOIN service_providers p ON a.provider_id = p.provider_id
            LEFT JOIN services s ON a.service_id = s.service_id
            " . ($where !== "" ? "WHERE $where" : "") . "
            ORDER BY $order" . ($limit > 0 ? " LIMIT $limit" : "");
    $rows = [];
    try {
        $r = $conn->query($sql);
        while ($r && $row = $r->fetch_assoc()) {
            if ($row["customer_name"] === "") {
                $row["customer_name"] = (string)$row["customer_user"];
            }
            $rows[] = $row;
        }
    } catch (Throwable $e) {
    }
    return $rows;
}

function fmt_date(?string $d): string
{
    return $d ? date("M j, Y", strtotime($d)) : "-";
}

function fmt_time(?string $t): string
{
    return $t ? date("g:i A", strtotime($t)) : "-";
}

/* Renders a table of appointments. $cols picks columns. */
function render_appointment_table(array $rows, array $cols = ["id", "customer", "service", "provider", "date", "time", "status"], string $empty = "No appointments found."): void
{
    if (!$rows) {
        echo '<div class="empty"><div class="big">📭</div><strong>' . e($empty) . '</strong></div>';
        return;
    }
    $names = ["id" => "ID", "customer" => "Customer", "service" => "Service", "provider" => "Provider", "date" => "Date", "time" => "Time", "status" => "Status"];
    echo '<div class="table-wrap"><table><thead><tr>';
    foreach ($cols as $c) {
        echo "<th>" . e($names[$c]) . "</th>";
    }
    echo "</tr></thead><tbody>";
    foreach ($rows as $r) {
        echo "<tr>";
        foreach ($cols as $c) {
            switch ($c) {
                case "id":       echo "<td>#" . (int)$r["appointment_id"] . "</td>"; break;
                case "customer": echo '<td><div class="cell-user"><div class="mini-avatar">' . e(initial($r["customer_name"])) . '</div><div class="cell-title">' . e($r["customer_name"]) . "</div></div></td>"; break;
                case "service":  echo "<td>" . e($r["service_name"]) . "</td>"; break;
                case "provider": echo "<td>" . e($r["provider_name"]) . "</td>"; break;
                case "date":     echo "<td>" . e(fmt_date($r["appointment_date"])) . "</td>"; break;
                case "time":     echo "<td>" . e(fmt_time($r["appointment_time"])) . "</td>"; break;
                case "status":   echo "<td>" . status_badge((string)$r["status"]) . "</td>"; break;
            }
        }
        echo "</tr>";
    }
    echo "</tbody></table></div>";
}
