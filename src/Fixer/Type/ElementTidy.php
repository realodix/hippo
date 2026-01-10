<?php

namespace Realodix\Haiku\Fixer\Type;

use Realodix\Haiku\Fixer\Regex;
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

        $domainBlock = $m[1];
        $modifier = $m[2] ?? ''; // AdGuard modifier
        $domain = $m[3];
        $separator = $m[4];
        $selector = $m[5];

        if (str_starts_with($modifier, '[$') && $this->isComplicatedAdgModifier($modifier)) {
            $modifier = $this->extractAdgModifier($domainBlock);

            if (is_null($modifier)) {
                return $line;
            }

            $line = substr($line, strlen($modifier));

            preg_match(Regex::COSMETIC_RULE, $line, $m);
            $domain = $m[3];
            $separator = $m[4];
            $selector = $m[5];
        }

        $domain = Helper::normalizeDomain($domain, ',');

        return $modifier.$domain.$separator.$selector;
    }

    /**
     * Extract AdGuard non-basic modifier using backward scan.
     *
     * https://adguard.com/kb/general/ad-filtering/create-own-filters/#non-basic-rules-modifiers
     */
    private function extractAdgModifier(string $str): ?string
    {
        $len = strlen($str);
        $open = null; // '/'

        for ($i = $len - 1; $i >= 0; $i--) {
            $c = $str[$i];

            // ===== REGEX =====
            if ($c === '/') {
                if ($open === $c) {
                    $open = null;
                } elseif ($open === null) {
                    $open = $c;
                }

                continue;
            }

            // ===== CLOSING BRACKET =====
            if ($open === null && $c === ']') {
                // IPv6 literal? → skip
                $ipv6Start = Helper::isIpv6Literal($str, $i);
                if ($ipv6Start !== null) {
                    $i = $ipv6Start;

                    continue;
                }

                // this is modifier end
                return substr($str, 0, $i + 1);
            }
        }

        return null;
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
