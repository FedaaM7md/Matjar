<?php

// Matjar — © 2026 Fedaa Mohamed. All rights reserved. Proprietary.

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

/**
 * Resets the demo store to its seeded baseline. Guarded so it only runs while
 * demo mode is enabled, and only in production with --force.
 */
class ResetDemoData extends Command
{
    protected $signature = 'demo:reset {--force : Skip the production guard and confirmation}';

    protected $description = 'Reset the demo store: wipe and reseed demo catalog, orders, users and sessions';

    public function handle(): int
    {
        if (! config('matjar.demo.enabled')) {
            $this->error('Demo mode is disabled (DEMO_MODE=false). Refusing to reset.');

            return self::FAILURE;
        }

        if (app()->environment('production') && ! $this->option('force')) {
            $this->error('Refusing to reset demo data in production without --force.');

            return self::FAILURE;
        }

        if (! $this->option('force') && ! $this->confirm('This wipes the database and reseeds demo data. Continue?')) {
            $this->warn('Cancelled.');

            return self::SUCCESS;
        }

        Artisan::call('migrate:fresh', ['--seed' => true, '--force' => true], $this->getOutput());

        $this->info(__('demo.reset_done'));

        return self::SUCCESS;
    }
}
