<?php
declare(strict_types=1);

namespace Services;

use Helpers\Helper;
use InvalidArgumentException;
use Repositories\AuditLogRepository;

final class AuditLogService {
    public function __construct(
        private readonly AuditLogRepository $repository,
        private readonly Helper $helper
    ) {}

    public function record(string $action, string $description, string $entityType, int $entityId,
        int $actorUserId,
    ): void {
        $action = trim($action);

        if ($action === '') {
            throw new InvalidArgumentException('Action cannot be empty.');
        }

        $description = trim($description);

        if ($description === '') {
            throw new InvalidArgumentException('Description cannot be empty.');
        }

        $entityType = trim($entityType);

        if ($entityType === '') {
            throw new InvalidArgumentException('Entity Type cannot be empty.');
        }

        if ($this->helper->filterInt($entityId, 1) === false) {
            throw new InvalidArgumentException('Entity ID must be at least 1.');
        }

        if ($this->helper->filterInt($actorUserId, 1) === false) {
            throw new InvalidArgumentException('Actor User ID must be at least 1.');
        }

        $this->repository->insert(
            action: $action,
            description: $description,
            entityType: $entityType,
            entityId: $entityId,
            actorUserId: $actorUserId
        );
    }
}
