<?php

namespace Realodix\Hippo\Config;

use Nette\Schema\Expect;
use Nette\Schema\Schema as NetteSchema;

final class Schema
{
    public static function define(): NetteSchema
    {
        return Expect::structure([
            'cache_dir' => Expect::string(),

            'fixer' => Expect::structure([
                'paths' => Expect::listOf('string'),
                'ignores' => Expect::listOf('string'),
            ]),

            'builder' => Expect::structure([
                'output_dir' => Expect::string(),
                'filter_list' => Expect::listOf(Expect::structure([
                    'filename' => Expect::string()->required(),
                    'remove_duplicates' => Expect::bool(),
                    'metadata' => Expect::structure([
                        'date_modified' => Expect::bool(),
                        'header' => Expect::string(),
                        'title' => Expect::string(),
                        'version' => Expect::bool(),
                        'custom' => Expect::string(),
                    ]),
                    'source' => Expect::listOf('string')->required(),
                ])),
            ]),
        ]);
    }
}
