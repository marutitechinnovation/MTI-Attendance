<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Config\Database;

/**
 * Wipes and refills the demo database. Runs nightly from cron on the demo site.
 * Refuses to run unless app.demoMode = true, so it can never touch a real install.
 */
class DemoReset extends BaseCommand
{
    protected $group       = 'Demo';
    protected $name        = 'demo:reset';
    protected $description = 'Replaces all data with fresh sample data (demo sites only).';

    public function run(array $params)
    {
        if (!filter_var(env('app.demoMode', false), FILTER_VALIDATE_BOOLEAN)) {
            CLI::error('Refusing to run: app.demoMode is not enabled.');
            return EXIT_ERROR;
        }

        Database::seeder()->call('DemoSeeder');
        CLI::write('Demo data reset.', 'green');
        return EXIT_SUCCESS;
    }
}
