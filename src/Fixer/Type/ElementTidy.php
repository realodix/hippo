<?php

namespace Realodix\Haiku\Fixer\Type;

use Realodix\Haiku\Helper;

final class ElementTidy
{
    // /** @var array<string, string> */
    // private array $stringLiterals = [];

    /**
     * Normalize an element hiding rule.
     *
     * @param string $line The rule line
     * @param array<string> $m The regex match
     * @return string The normalized rule
     */
    public function handle(string $line, array $m): string
    {
        // https://adguard.com/kb/general/ad-filtering/create-own-filters/#non-basic-rules-modifiers
        if (str_starts_with($line, '[$')) {
            return $line;
        }

        $domain = $m[1]; // Extract domains
        $separator = $m[2]; // Extract separator
        $selector = $m[3]; // Extract selector

        $normalizedDomain = $this->normalizeDomain($domain);

        return $normalizedDomain.$separator.$selector;
    }

    private function normalizeDomain(string $domain): string
    {
        // domain is a regex
        if (str_starts_with($domain, '/') && str_ends_with($domain, '/')) {
            return $domain;
        }

        $domain = collect(explode(',', $domain))
            ->filter(fn($d) => $d !== '')
            ->map(fn($d) => Helper::cleanDomain($d))
            ->unique()
            ->sortBy(fn($d) => ltrim($d, '~'))
            ->implode(',');

        return $domain;
    }
}
