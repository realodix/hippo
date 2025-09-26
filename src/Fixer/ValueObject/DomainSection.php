<?php

namespace Realodix\Haiku\Fixer\ValueObject;

/**
 * Represents the domain section of a filter rule.
 */
final readonly class DomainSection
{
    /**
     * @param string $fullMatch The full matched domain part (e.g., $domains=example.com|~example.net)
     * @param string $domainList The domain list (e.g., example.com|~example.net)
     * @param string $baseRule The filter rule without the domain part. (e.g., ||example.com^)
     */
    public function __construct(
        public string $fullMatch,
        public string $domainList,
        public string $baseRule,
    ) {}
}
