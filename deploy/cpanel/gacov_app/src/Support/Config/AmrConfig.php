<?php

declare(strict_types=1);

namespace App\Support\Config;

use App\Domain\Shared\CompanyProfile;

final class AmrConfig
{
    public function companyProfile(): CompanyProfile
    {
        $company = config('amr.company', []);

        return new CompanyProfile(
            name: (string) ($company['name'] ?? config('app.name', 'GACOV Inventarios')),
            developer: (string) ($company['developer'] ?? 'AMR Tech'),
            domains: array_values($company['domains'] ?? []),
            hosting: (string) ($company['hosting'] ?? 'Hostinger Shared Hosting'),
        );
    }
}
