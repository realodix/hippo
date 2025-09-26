<?php

use Realodix\Relax\Config;
use Realodix\Relax\Finder;

$localRules = [
    // ...
];

return Config::create('relax')
    ->setFinder(Finder::base()->in(__DIR__))
    ->setRules($localRules)
    ->setCacheFile(__DIR__.'/.tmp/.php-cs-fixer.cache');
