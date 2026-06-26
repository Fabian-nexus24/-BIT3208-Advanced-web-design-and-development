<?php
declare(strict_types=1);

/**
 * Audit logging functions.
 */

function audit_log(
    int $actorAdminId,
    string $action,
    string $targetType,
    int $targetId,
    ?array $metadata = null
): void {
    global $pdo;

    $metaJson = $metadata !== null ? json_encode($metadata, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : null;

    $stmt = $pdo->prepare(
        'INSERT INTO audit_logs (actor_admin_id, action, target_type, target_id, metadata, created_at)
         VALUES (?, ?, ?, ?, ?, CURRENT_TIMESTAMP)'
    );
    $stmt->execute([
        $actorAdminId,
        $action,
        $targetType,
        $targetId,
        $metaJson
    ]);
}
