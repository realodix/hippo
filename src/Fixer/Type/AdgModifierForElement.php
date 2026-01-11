<?php

namespace Realodix\Haiku\Fixer\Type;

/**
 * https://adguard.com/kb/general/ad-filtering/create-own-filters/#non-basic-rules-modifiers
 */
final class AdgModifierForElement
{
    /**
     * Extract AdGuard modifier using backward scan.
     */
    public function extract(string $str): ?string
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
                // IPv6 literal? -> skip
                $ipv6Start = $this->isIpv6Literal($str, $i);
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
    public function isComplicated(string $modifier): bool
    {
        return
            // contains regex
            substr_count($modifier, '/]') > 0
            // bracket count mismatch
            || substr_count($modifier, '[') != substr_count($modifier, ']');
    }

    public function isIpv6Literal(string $str, int $end): ?int
    {
        for ($i = $end - 1; $i >= 0; $i--) {
            $c = $str[$i];

            if ($c === '[') {
                $inside = substr($str, $i + 1, $end - $i - 1);

                // only hex digits and colon
                if ($inside !== '' && preg_match('/^[0-9a-fA-F:]+$/', $inside)) {
                    return $i; // opening bracket position
                }

                return null;
            }

            if (!ctype_xdigit($c) && $c !== ':') {
                return null;
            }
        }

        return null;
    }
}
