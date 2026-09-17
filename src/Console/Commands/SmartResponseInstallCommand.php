<?php

declare(strict_types=1);

namespace Quonain\SmartResponse\Console\Commands;

use Illuminate\Console\Command;

final class SmartResponseInstallCommand extends Command
{
    protected $signature = 'smart-response:install {--force : Replace the published configuration}';
    protected $description = 'Install SmartResponse configuration and middleware guidance';

    public function handle(): int
    {
        $this->call('vendor:publish', ['--tag' => 'smart-response-config', '--force' => (bool) $this->option('force')]);
        $this->info('SmartResponse installed. Add the smart.response middleware to API routes when protection is enabled.');
        return self::SUCCESS;
    }
}
