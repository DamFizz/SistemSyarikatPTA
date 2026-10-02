<?php

namespace App\Console\Commands;

use Illuminate\Foundation\Console\ServeCommand as BaseServeCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * `php artisan serve` with several PHP workers in production.
 *
 * Railway starts the app with plain `php artisan serve`, which runs PHP's single-threaded
 * built-in server: page loads, prefetches, avatars and status polls queued behind each
 * other. In production this runs 4 workers (PHP_CLI_SERVER_WORKERS overrides the count).
 */
class ServeCommand extends BaseServeCommand
{
    public const PRODUCTION_WORKERS = 4;

    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        parent::initialize($input, $output);

        // Worker processes are Linux/macOS only; locally the normal single server is fine.
        if ($this->phpServerWorkers === false && $this->laravel->isProduction() && ! windows_os()) {
            $this->phpServerWorkers = max(2, (int) env('PHP_CLI_SERVER_WORKERS', self::PRODUCTION_WORKERS));
        }
    }
}
