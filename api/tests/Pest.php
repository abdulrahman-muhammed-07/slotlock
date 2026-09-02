<?php

declare(strict_types=1);

use App\Models\Company;
use Tests\TestCase;

pest()->extend(TestCase::class)->in('Feature', 'Unit');

/**
 * Bearer header for a company's API token. Most feature tests drive the API
 * through the real middleware stack rather than faking the tenant.
 *
 * @return array<string, string>
 */
function tokenFor(Company $company): array
{
    return ['Authorization' => 'Bearer '.$company->api_token];
}
