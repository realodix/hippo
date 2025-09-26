<?php

use Realodix\Hippo\Console\Kernel;

require_once __DIR__.'/../vendor/autoload.php';

$kernel = new Kernel;
$kernel->bootstrap();

return $kernel;
