<?php
declare(strict_types=1);

namespace Repositories;

use PDO;

final class AuditLogRepository {
    public function __construct(
        private readonly PDO $connection
    ) {}

    public function insert(string $action, string $description, string $entityType, int $entityId,
        int $actorUserId
    ) {
        $query = "INSERT INTO audit_logs (action, description, entity_type, entity_id, actor_user_id)
            VALUES (?,?,?,?,?)";
        $statement = $this->connection->prepare($query);
        $statement->execute([$action, $description, $entityType, $entityId, $actorUserId]);
    }
}
