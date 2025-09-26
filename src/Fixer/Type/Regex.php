<?php

namespace Realodix\Hippo\Fixer\Type;

final class Regex
{
    /**
     * @var string Regex to capture the filter body and its options.
     *             Example: `||example.com^$script,domain=example.org`
     */
    // const NET_OPTION = '/^(.*)\$(~?[\w\-]+(?:=[^,\s]+)?(?:,~?[\w\-]+(?:=[^,\s]+)?)*)$/';
    // const NET_OPTION = '/^(.*)\$(~?[\w\-]+(?:=[^\s]+)?(?:,~?[\w\-]+(?:=[^\s]+)?)*)$/';
    const NET_OPTION = '/^(.*)\$(~?[\w\-]+(?:=.+)?(?:,~?[\w\-]+(?:=.+)?)*)$/';

    /**
     * @var string Regex to find domain-related options in a network filter.
     */
    const NET_OPTION_HAS_DOMAIN = '/(?:\$|,)(?:denyallow|domain|from|method|to)\=([^,\s]+)$/';

    /**
     * https://regex101.com/r/CdhXQ4
     *
     * @var string Regex to identify and capture parts of an element hiding rule.
     *             Example: `example.com,example.org##.ad`
     */
    const COSMETIC_RULE = '/^(?:\[\$[^\]]+\])?([^\/\|\@\"\!]*?)(\$\@?\$|#@?[$?]{1,2}#|#@?#[\^\+]?)(.*)$/';

    /**
     * https://regex101.com/r/dgL4pf/1
     *
     * @var string Regex to find domains in element-hiding rules.
     */
    const COSMETIC_DOMAIN = '/^([^\/\|\@\"\!]*?)(#|\$)\@?\??\@?(#|\$)/';

    /**
     * @var string Regex to find string literals inside CSS attribute selectors.
     *             Example: `[title="some text"]`
     */
    const ATTRIBUTE_VALUE_PATTERN = '/(\"(?:[^\"\\\\]|\\\\.)*\"|\'(?:[^\'\\\\]|\\\\.)*\')/';

    /**
     * @var string Regex to normalize whitespace around tree selectors (e.g., >, +, ~).
     */
    const SELECTOR_COMBINATOR = "/(\\.|[^\+\>\~\\\ \t])\s*([\+\>\~\ \t])\s*(\D)/";

    /**
     * @var string Regex to match pseudo-classes in selectors (e.g., ::before).
     */
    const PSEUDO_PATTERN = '/(?<!\\\)(\:[a-zA-Z\-]*[A-Z][a-zA-Z\-]*)(?=([\(\:\@\s]))/';

    /**
     * @var string Regex to detect uBlock Origin scriptlet rules (`+js(...)`).
     */
    const UBO_JS_PATTERN = '/^@js\(/i';
}
