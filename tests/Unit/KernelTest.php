<?php

namespace Realodix\Fenyx\Test\Unit;

use Realodix\Hippo\App;
use Realodix\Hippo\Console\FixCommand;
use Realodix\Hippo\Console\Kernel;
use Realodix\Hippo\Test\TestCase;
use Symfony\Component\Console\Application;

class KernelTest extends TestCase
{
    public function testBootstrap()
    {
        $providerMock = $this->createMock(App::class);
        $providerMock->expects($this->once())
            ->method('register');
        $kernel = new Kernel;
        $container = $this->getPrivateProperty($kernel, 'app');
        $container->instance(App::class, $providerMock);
        $kernel->bootstrap();

        // App class binds FixCommand to the container.
        // We can check if the container can resolve it.
        $command = $this->getPrivateProperty($kernel, 'app')->make(FixCommand::class);
        $this->assertInstanceOf(FixCommand::class, $command);
    }

    public function testRegisterCommands()
    {
        $kernel = new Kernel;
        $commands = $this->getPrivateProperty($kernel, 'commands');

        $applicationMock = $this->createMock(Application::class);
        $applicationMock->expects($this->exactly(count($commands)))
            ->method('add')
            ->with($this->isInstanceOf(\Symfony\Component\Console\Command\Command::class));

        $this->callPrivateMethod($kernel, 'registerCommands', [$applicationMock]);
    }
}
