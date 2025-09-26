<?php

namespace Realodix\Hippo\Console;

use Illuminate\Container\Container;
use Realodix\Hippo\App;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;

class Kernel
{
    protected Container $app;

    /**
     * List of service providers used by the application
     *
     * @var array<class-string>
     */
    protected array $providers = [
        App::class,
    ];

    /**
     * List of available commands
     *
     * @var array<class-string>
     */
    protected array $commands = [
        \Realodix\Hippo\Console\BuildCommand::class,
        \Realodix\Hippo\Console\FixCommand::class,
        \Realodix\Hippo\Console\SetupCommand::class,
    ];

    public function __construct()
    {
        $this->app = Container::getInstance();
    }

    /**
     * Bootstrap the application + run the console
     */
    public function handle(): int
    {
        $this->bootstrap();
        $output = new ConsoleOutput;
        $this->app->instance(OutputInterface::class, $output);

        $console = new Application(App::NAME, App::VERSION);
        $this->registerCommands($console);

        return $console->run(new ArgvInput, $output);
    }

    /**
     * Run service provider
     */
    public function bootstrap(): void
    {
        foreach ($this->providers as $provider) {
            $this->app->make($provider)->register($this->app);
        }
    }

    /**
     * Register all commands to the console
     */
    protected function registerCommands(Application $console): void
    {
        foreach ($this->commands as $command) {
            $console->add($this->app->make($command));
        }
    }
}
