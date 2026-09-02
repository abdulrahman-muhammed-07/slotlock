<?php

declare(strict_types=1);

namespace App\Tenancy;

use App\Models\Company;
use RuntimeException;

/**
 * Request-scoped holder for the tenant resolved from the auth token.
 *
 * Registered as a singleton in the container. The ResolveCompany middleware
 * sets it; the global CompanyScope and the domain services read it.
 */
final class CurrentCompany
{
    private ?Company $company = null;

    public function set(Company $company): void
    {
        $this->company = $company;
    }

    public function get(): Company
    {
        if ($this->company === null) {
            throw new RuntimeException('No current company. The request did not pass through the tenant middleware.');
        }

        return $this->company;
    }

    public function id(): int
    {
        return $this->get()->getKey();
    }

    public function isResolved(): bool
    {
        return $this->company !== null;
    }
}
