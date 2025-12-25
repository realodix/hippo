<?php

namespace Realodix\Haiku\Fixer;

final class Regex
{
    /**
     * Regex to capture the filter body and its options.
     *
     * Example: ||example.com^$script,domain=example.org
     *
     * @var string
     */
    // const NET_OPTION = '/^(.*)\$(~?[\w\-]+(?:=[^,\s]+)?(?:,~?[\w\-]+(?:=[^,\s]+)?)*)$/';
    // const NET_OPTION = '/^(.*)\$(~?[\w\-]+(?:=[^\s]+)?(?:,~?[\w\-]+(?:=[^\s]+)?)*)$/';
    const NET_OPTION = '/^(.*)\$(~?[\w\-]+(?:=.+)?(?:,~?[\w\-]+(?:=.+)?)*)$/';

    /**
     * Regex to find domain-related options in a network filter.
     *
     * @var string
     */
    const NET_OPTION_DOMAIN = '/(?:\$|,)(?:denyallow|domain|from|method|to)\=([^,\s]+)$/';

    /**
     * Regex to identify and capture parts of an element hiding rule.
     *
     * Example: example.com,example.org##.ad
     * Ref: https://regex101.com/r/yY3a26/2
     *
     * @var string
     */
    const COSMETIC_RULE = '/^(?:\[\$[^\]]+\])?([^\/\|\@\"\!]*?|\/.+\/)(#@?[$?]{1,2}#|#@?#[\^\+]?|\$\@?\$)(.*)$/';

    /**
     * Regex to find domains in element-hiding rules.
     *
     * Ref: https://regex101.com/r/2E6nAd
     *
     * @var string
     */
    const COSMETIC_DOMAIN = '/^(?!##?\s)([^\/\|\@\"!]*?)(##|#[@?$%]{1,3}#|\$@?\$)/';

    /**
     * Regex to find AdGuard JS rules.
     *
     * Ref: https://regex101.com/r/NYYNxd
     *
     * @var string
     */
    const AG_JS_RULE = '/^(?:\[\$[^\]]+\])?([^\/\|\@\"\!]*?)(#@?%#[\^\+]?)(.*)$/';
}
