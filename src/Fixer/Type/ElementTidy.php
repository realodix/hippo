<?php

namespace Realodix\Haiku\Fixer\Type;

use Realodix\Haiku\Fixer\Regex;
use Realodix\Haiku\Helper;

final class ElementTidy
{
    public function __construct(
        private AdgModifierForElement $adgModifier,
    ) {}

    /**
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

        if (str_starts_with($modifier, '[$') && $this->adgModifier->isComplicated($modifier)) {
            $modifier = $this->adgModifier->extract($domainBlock);

            if (is_null($modifier)) {
                return $line;
            }

            $line = substr($line, strlen($modifier));

            preg_match(Regex::COSMETIC_RULE, $line, $m);
            $domain = $m[3];
            $separator = $m[4];
            $selector = $m[5];
        }

        $modifier = $this->adgModifier->applyFix($modifier);
        $domain = Helper::normalizeDomain($domain, ',');
        $selector = $this->normalizeSpaces($selector);

        return $modifier.$domain.$separator.$selector;
    }

    private function normalizeSpaces(string $text): string
    {
        return preg_replace('/\s\s+/', ' ', $text);
    }
}
