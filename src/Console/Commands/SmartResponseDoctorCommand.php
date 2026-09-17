<?php

declare(strict_types=1);

namespace Quonain\SmartResponse\Console\Commands;

use Illuminate\Console\Command;
use Quonain\SmartResponse\Support\ResponseFormatterRegistry;

final class SmartResponseDoctorCommand extends Command
{
    protected $signature = 'smart-response:doctor';

    protected $description = 'Check SmartResponse configuration and protocol readiness';

    public function handle(ResponseFormatterRegistry $registry): int
    {
        $this->info('SmartResponse readiness');
        $this->line('');

        $this->check('PHP 8.2+', PHP_VERSION_ID >= 80200);
        $this->check('JSON extension', extension_loaded('json'));
        $this->check('XML extension', extension_loaded('xml'));
        $this->check('SOAP extension', extension_loaded('soap'), true);
        $this->check('gRPC extension', extension_loaded('grpc'), true);
        $this->check('Configured formatters', $registry->formats() !== []);

        $this->line('Formats: '.implode(', ', $registry->formats()));
        $this->line('SOAP and gRPC are optional runtime capabilities; the core adapters remain installable without them.');

        return self::SUCCESS;
    }

    private function check(string $label, bool $passed, bool $optional = false): void
    {
        $state = $passed ? '<fg=green>PASS</>' : ($optional ? '<fg=yellow>INFO</>' : '<fg=red>FAIL</>');
        $suffix = $optional ? ' (optional)' : '';
        $this->line("[{$state}] {$label}{$suffix}");
    }
}
