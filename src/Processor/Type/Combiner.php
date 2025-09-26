<?php

namespace Realodix\Hippo\Processor\Type;

use Composer\Pcre\Preg;
use Realodix\Hippo\Helper;

final class Combiner
{
    /**
     * Combines domains for (further) identical rules.
     *
     * @param array<string> $filters Array of uncombined filter rules.
     * @param string $domainPattern The regex pattern to extract the domain part.
     * @param string $separator The character used to separate domains (`,` or `|`).
     * @return array<string> Combined filter rules.
     */
    public function handle(array $filters, string $domainPattern, string $separator): array
    {
        $combined = [];
        $filterCount = count($filters);

        for ($i = 0; $i < $filterCount; $i++) {
            $current = $filters[$i];
            $next = $filters[$i + 1] ?? null;

            [$currentMatch, $currentDomainValue] = $this->extractDomainInfo($current, $domainPattern);

            if ($next === null || $currentMatch === '') {
                $combined[] = $current;

                continue;
            }

            [$nextMatch, $nextDomainValue] = $this->extractDomainInfo($next, $domainPattern);

            if ($this->canCombine(
                $current, $next, $domainPattern,
                $currentMatch, $currentDomainValue,
                $nextMatch, $nextDomainValue, $separator,
            )) {
                $newDomain = $this->combineDomains($currentDomainValue, $nextDomainValue, $separator);

                // Replace the domain in `$current` and insert it into `$next`.
                $combinedDomainMatch = str_replace($currentDomainValue, $newDomain, $currentMatch);
                $filters[$i + 1] = Preg::replace($domainPattern, $combinedDomainMatch, $current);
            } else {
                $combined[] = $current;
            }
        }

        return $combined;
    }

    /**
     * @return array{string, string}
     */
    private function extractDomainInfo(string $filter, string $domainPattern): array
    {
        $matches = [];
        if (Preg::match($domainPattern, $filter, $matches)) {
            return [$matches[0], $matches[1] ?? ''];
        }

        return ['', ''];
    }

    /**
     * Determines if two filter rules can be combined by checking if the domain values contain
     * any included domains (without ~) or only excluded domains (~). This prevents combining rules
     * that are not structurally compatible. (e.g., ~example.com and example.com).
     *
     * @param string $current The current filter rule.
     * @param string $next The next filter rule.
     * @param string $domainPattern The regex pattern to extract the domain part.
     * @param string $currentMatch The matched substring from the current filter rule.
     * @param string $currentDomainValue The domain value from the current filter rule.
     * @param string $nextMatch The matched substring from the next filter rule.
     * @param string $nextDomainValue The domain value from the next filter rule.
     * @param string $separator The character used to separate domains (`,_` or `|`).
     * @return bool True if the rules can be combined, false otherwise.
     */
    private function canCombine(
        string $current,
        string $next,
        string $domainPattern,
        string $currentMatch,
        string $currentDomainValue,
        string $nextMatch,
        string $nextDomainValue,
        string $separator,
    ): bool {
        if ($nextMatch === '' || $currentDomainValue === '' || $nextDomainValue === '') {
            return false;
        }

        // Check domain structure compatibility
        $replaced = str_replace($currentDomainValue, $nextDomainValue, $currentMatch);
        if ($replaced !== $nextMatch) {
            return false;
        }

        // Check if the base filter parts (without domains) are identical
        $currentBaseRule = Preg::replace($domainPattern, '', $current);
        $nextBaseRule = Preg::replace($domainPattern, '', $next);

        if ($currentBaseRule !== $nextBaseRule) {
            return false;
        }

        // Check if the current and next domain values contain any included domains (without ~)
        // or only excluded domains (~). This prevents combining rules that are not structurally
        // compatible. (e.g., ~example.com and example.com).
        return $this->domainCompatibility($currentDomainValue, $nextDomainValue, $separator);
    }

    private function domainCompatibility(string $currentDomainValue, string $nextDomainValue, string $separator): bool
    {
        $currentHasIncludedDomains = $this->hasIncludedDomains($currentDomainValue, $separator);
        $nextHasIncludedDomains = $this->hasIncludedDomains($nextDomainValue, $separator);

        return $currentHasIncludedDomains === $nextHasIncludedDomains;
    }

    private function hasIncludedDomains(string $domainValue, string $separator): bool
    {
        return substr_count($domainValue, '~') < substr_count($domainValue, $separator) + 1;
    }

    /**
     * Combines two domain values into one, removing duplicates and sorting alphabetically while preserving inverse domains.
     *
     * @param string $currentDomain The first domain value to combine.
     * @param string $nextDomain The second domain value to combine.
     * @param string $separator The character used to separate domains (`,``, `_`, or `|`).
     * @return string The combined domain value.
     */
    private function combineDomains(string $currentDomain, string $nextDomain, string $separator): string
    {
        $newDomain = $currentDomain.$separator.$nextDomain;

        return Helper::uniqueSorted(
            explode($separator, $newDomain),
            fn($s) => ltrim((string) $s, '~'),
        )->implode($separator);
    }
}
