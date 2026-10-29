<?php
// audit_logger.php
// Reusable audit logging for StudentHub.

function writeAuditLog(
    mysqli $conn,
    string $action,
    string $details = ""
): void {
    try {
        $userId = isset($_SESSION["student_id"])
            ? (int) $_SESSION["student_id"]
            : null;

        $userName = trim((string) ($_SESSION["name"] ?? "Unknown"));
        $userRole = trim((string) ($_SESSION["role"] ?? "unknown"));
        $ipAddress = $_SERVER["REMOTE_ADDR"] ?? null;

        $sql = "
            INSERT INTO audit_logs
                (user_id, user_name, user_role, action, details, ip_address)
            VALUES (?, ?, ?, ?, ?, ?)
        ";

        $stmt = $conn->prepare($sql);

        if (!$stmt) {
            error_log("Audit log preparation failed: " . $conn->error);
            return;
        }

        $stmt->bind_param(
            "isssss",
            $userId,
            $userName,
            $userRole,
            $action,
            $details,
            $ipAddress
        );

        if (!$stmt->execute()) {
            error_log("Audit log insert failed: " . $stmt->error);
        }

        $stmt->close();
    } catch (Throwable $e) {
        // A logging failure must not undo a successful event operation.
        error_log("Audit logging error: " . $e->getMessage());
    }
}
?>