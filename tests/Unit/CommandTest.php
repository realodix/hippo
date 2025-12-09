<?php

namespace Realodix\Haiku\Test\Unit;

use PHPUnit\Framework\Attributes as PHPUnit;
use Realodix\Haiku\Test\TestCase;

class CommandTest extends TestCase
{
    #[PHPUnit\Test]
    public function fixer_custom_config(): void
    {
        $this->expectException(\Realodix\Haiku\Config\InvalidConfigurationException::class);
        $this->expectExceptionMessage('The configuration file does not exist.');

        $this->runFixCommand('', options: ['--config' => 'notfound.yml']);
    }
}
