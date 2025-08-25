<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;

final class HelpCommand extends Command
{
    protected $signature = 'sentx:help {cmd? : Specific command to get help for}';

    protected $description = 'SENTINENTX Trading Bot Help System';

    public function handle(): int
    {
        $command = $this->argument('cmd');

        if ($command) {
            $this->showCommandHelp($command);
        } else {
            $this->showGeneralHelp();
        }

        return self::SUCCESS;
    }

    private function showGeneralHelp(): void
    {
        $this->info('🤖 SENTINENTX Trading Bot - Help System');
        $this->line('');
        $this->line('Available Commands:');
        $this->line('');

        $commands = [
            'Trading Commands:' => [
                'sentx:open-now' => 'Open new position using AI consensus',
                'sentx:manage-positions' => 'Manage open positions (scale-in/out, close)',
                'sentx:close' => 'Close specific position',
                'sentx:reconcile-positions' => 'Reconcile exchange vs local positions',
            ],
            'LAB Mode Commands:' => [
                'sentx:lab-scan' => 'Run LAB scan (simulation mode)',
                'sentx:eod-metrics' => 'Calculate end-of-day performance metrics',
                'sentx:lab-report' => 'Generate LAB performance report',
                'sentx:lab-acceptance-mail' => 'Send LAB acceptance status email',
            ],
            'Risk & Health Commands:' => [
                'sentx:risk-gate-check' => 'Check risk gates before trading',
                'sentx:health-check' => 'System health check',
                'sentx:status' => 'System status overview',
                'sentx:performance-monitor' => 'Monitor trading performance',
            ],
            'Utility Commands:' => [
                'sentx:help' => 'Show this help message',
                'sentx:check-bybit-key' => 'Validate Bybit API credentials',
                'sentx:cache-optimize' => 'Optimize application cache',
            ],
        ];

        foreach ($commands as $category => $cmds) {
            $this->info($category);
            foreach ($cmds as $cmd => $desc) {
                $this->line("  {$cmd} - {$desc}");
            }
            $this->line('');
        }

        $this->line('For detailed help on a specific command:');
        $this->line('  php artisan sentx:help <command>');
        $this->line('');
        $this->line('Example: php artisan sentx:help open-now');
    }

    private function showCommandHelp(string $command): void
    {
        $commandMap = [
            'open-now' => [
                'description' => 'Open new position using AI consensus (2-round voting)',
                'usage' => 'php artisan sentx:open-now [symbol] [--symbols=] [--snapshot=] [--dry]',
                'arguments' => [
                    'symbol' => 'Single symbol (e.g., BTCUSDT) - backward compatibility',
                ],
                'options' => [
                    '--symbols=' => 'Trading symbols (e.g., BTC,ETH,SOL,XRP)',
                    '--snapshot=' => 'Path to snapshot JSON file (required)',
                    '--dry' => 'Dry run mode - no actual trades',
                ],
                'examples' => [
                    'php artisan sentx:open-now BTCUSDT --snapshot=/path/to/snapshot.json',
                    'php artisan sentx:open-now --symbols=BTC,ETH,SOL,XRP --snapshot=/path/to/snapshot.json --dry',
                ],
                'notes' => [
                    'Requires valid snapshot JSON with market_data, portfolio, and risk_context',
                    'Uses 2-round AI consensus with deviation veto system',
                    'Supports dry run mode for testing',
                ],
            ],
            'lab-scan' => [
                'description' => 'Run LAB scan in simulation mode',
                'usage' => 'php artisan sentx:lab-scan [--symbol=] [--count=] [--atrK=] [--qty=] [--seed=]',
                'options' => [
                    '--symbol=' => 'Trading symbol (default: BTCUSDT)',
                    '--count=' => 'Number of trades to generate (default: 3)',
                    '--atrK=' => 'ATR multiplier for SL/TP (default: 2.0)',
                    '--qty=' => 'Trade quantity (default: 0.01)',
                    '--seed=' => 'Random seed for deterministic simulation',
                ],
                'examples' => [
                    'php artisan sentx:lab-scan --symbol=ETHUSDT --count=5',
                    'php artisan sentx:lab-scan --atrK=1.5 --seed=12345',
                ],
            ],
            'status' => [
                'description' => 'Show system status overview',
                'usage' => 'php artisan sentx:status [--detailed]',
                'options' => [
                    '--detailed' => 'Show detailed status information',
                ],
                'examples' => [
                    'php artisan sentx:status',
                    'php artisan sentx:status --detailed',
                ],
            ],
            'reconcile-positions' => [
                'description' => 'Reconcile exchange positions with local database',
                'usage' => 'php artisan sentx:reconcile-positions [--exchange-json=]',
                'options' => [
                    '--exchange-json=' => 'Path to exchange positions JSON file',
                ],
                'examples' => [
                    'php artisan sentx:reconcile-positions',
                    'php artisan sentx:reconcile-positions --exchange-json=/path/to/positions.json',
                ],
                'notes' => [
                    'Detects orphaned positions (exchange vs local)',
                    'Automatically creates missing trades',
                    'Closes non-existent positions',
                ],
            ],
        ];

        if (! isset($commandMap[$command])) {
            $this->error("Unknown command: {$command}");
            $this->line('Use "php artisan sentx:help" to see all available commands.');

            return;
        }

        $help = $commandMap[$command];

        $this->info("📖 Help for: sentx:{$command}");
        $this->line('');
        $this->line($help['description']);
        $this->line('');

        $this->info('Usage:');
        $this->line($help['usage']);
        $this->line('');

        if (isset($help['arguments'])) {
            $this->info('Arguments:');
            foreach ($help['arguments'] as $arg => $desc) {
                $this->line("  {$arg} - {$desc}");
            }
            $this->line('');
        }

        if (isset($help['options'])) {
            $this->info('Options:');
            foreach ($help['options'] as $opt => $desc) {
                $this->line("  {$opt} - {$desc}");
            }
            $this->line('');
        }

        if (isset($help['examples'])) {
            $this->info('Examples:');
            foreach ($help['examples'] as $example) {
                $this->line("  {$example}");
            }
            $this->line('');
        }

        if (isset($help['notes'])) {
            $this->info('Notes:');
            foreach ($help['notes'] as $note) {
                $this->line("  • {$note}");
            }
            $this->line('');
        }
    }
}
