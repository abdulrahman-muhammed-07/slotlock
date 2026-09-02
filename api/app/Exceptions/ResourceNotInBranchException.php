<?php

declare(strict_types=1);

namespace App\Exceptions;

/**
 * The caller asked to book a resource that does not belong to the branch
 * named in the request. A tenant-scoped caller can still hold references to
 * its own resources across branches, so this is a real business rule, not
 * just a tenancy check.
 */
final class ResourceNotInBranchException extends DomainException
{
    public static function make(int $resourceId, int $branchId): self
    {
        return new self("Resource {$resourceId} does not belong to branch {$branchId}.");
    }

    public function status(): int
    {
        return 422;
    }
}
