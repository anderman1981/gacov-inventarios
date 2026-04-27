<?php

declare(strict_types=1);

namespace App\Domain\Shared;

final readonly class CompanyProfile
{
    /**
     * @param  array<int, string>  $domains
     */
    public function __construct(
        public string $name,
        public string $developer,
        public array $domains,
        public string $hosting,
    ) {}
}
