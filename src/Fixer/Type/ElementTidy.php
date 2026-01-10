<?php

namespace Realodix\Haiku\Fixer\Type;

use Realodix\Haiku\Helper;

final class ElementTidy
{
    /**
     * Normalize an element hiding rule.
     *
     * @param string $line The rule line
     * @param array<string> $m The regex match
     * @return string The normalized rule
     */
    public function applyFix(string $line, array $m): string
    {
        if ($m === []) {
            return $line;
        }

        $modifier = $m[1] ?? ''; // AdGuard non-basic modifier
        $domain = $m[2];
        $separator = $m[3];
        $selector = $m[4];

        if (str_starts_with($modifier, '[$') && $this->isComplicatedAdgModifier($modifier)) {
            return $line;
        }

        $domain = Helper::normalizeDomain($domain, ',');

        return $modifier.$domain.$separator.$selector;
    }

    /**
     * Checks if a given AdGuard modifier is complicated. i.e., contains regex string
     * or Regex::COSMETIC_RULE fails to extract.
     */
    private function isComplicatedAdgModifier(string $modifier): bool
    {
        return
            // contains regex
            substr_count($modifier, '/]') > 0
            // bracket count mismatch
            || substr_count($modifier, '[') != substr_count($modifier, ']');
    }
}
