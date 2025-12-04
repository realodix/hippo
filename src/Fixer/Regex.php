<?php

namespace Realodix\Haiku\Fixer;

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
    const NET_OPTION_DOMAIN = '/(?:\$|,)(?:denyallow|domain|from|method|to)\=([^,\s]+)$/';

    /**
     * https://regex101.com/r/yY3a26/2
     *
     * @var string Regex to identify and capture parts of an element hiding rule.
     *             Example: `example.com,example.org##.ad`
     */
    const COSMETIC_RULE = '/^(?:\[\$[^\]]+\])?([^\/\|\@\"\!]*?|\/.+\/)(#@?[$?]{1,2}#|#@?#[\^\+]?|\$\@?\$)(.*)$/';

    /**
     * https://regex101.com/r/2E6nAd
     *
     * @var string Regex to find domains in element-hiding rules.
     */
    const COSMETIC_DOMAIN = '/^(?!##?\s)([^\/\|\@\"!]*?)(##|#[@?$%]{1,3}#|\$@?\$)/';

    /**
     * https://regex101.com/r/NYYNxd
     *
     * @var string Regex to find AdGuard JS rules.
     */
    const AG_JS_RULE = '/^(?:\[\$[^\]]+\])?([^\/\|\@\"\!]*?)(#@?%#[\^\+]?)(.*)$/';
}
