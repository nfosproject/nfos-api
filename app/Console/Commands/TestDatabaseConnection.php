<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Exception;

class TestDatabaseConnection extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:test';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test database connection and display connection details';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Testing database connection...');
        $this->newLine();

        // Display connection configuration (without password)
        $config = config('database.connections.' . config('database.default'));
        
        $this->line('Connection Configuration:');
        $this->line('  Driver: ' . ($config['driver'] ?? 'N/A'));
        $this->line('  Host: ' . ($config['host'] ?? 'N/A'));
        $this->line('  Port: ' . ($config['port'] ?? 'N/A'));
        $this->line('  Database: ' . ($config['database'] ?? 'N/A'));
        $this->line('  Username: ' . ($config['username'] ?? 'N/A'));
        $this->line('  Password: ' . (isset($config['password']) && $config['password'] ? '***' : 'Not set'));
        $this->newLine();

        try {
            // Attempt to connect
            DB::connection()->getPdo();
            $this->info('✓ Database connection successful!');
            $this->newLine();

            // Try a simple query
            $result = DB::select('SELECT 1 as test');
            $this->info('✓ Database query test successful!');
            $this->newLine();

            // Get database version
            try {
                $version = DB::select('SELECT VERSION() as version')[0]->version ?? 'Unknown';
                $this->line('Database Version: ' . $version);
            } catch (Exception $e) {
                // Ignore version check errors
            }

            return Command::SUCCESS;
        } catch (Exception $e) {
            $this->error('✗ Database connection failed!');
            $this->newLine();
            $this->error('Error: ' . $e->getMessage());
            $this->newLine();

            // Provide helpful suggestions
            $this->warn('Troubleshooting suggestions:');
            $this->line('  1. Verify database credentials in .env file');
            $this->line('  2. Check if your IP address is whitelisted in Laravel Cloud');
            $this->line('  3. Verify database host and port are correct');
            $this->line('  4. Check network connectivity to the database server');
            $this->line('  5. Ensure SSL/TLS settings are correct for cloud databases');
            $this->newLine();

            // If it's an access denied error, provide specific help
            if (str_contains($e->getMessage(), 'Access denied') || str_contains($e->getMessage(), '1045')) {
                $this->warn('This appears to be an access denied error. Common causes:');
                $this->line('  - IP address not whitelisted in Laravel Cloud database settings');
                $this->line('  - Incorrect username or password');
                $this->line('  - User does not have permission to access from this IP');
                $this->newLine();
                $this->line('To whitelist your IP in Laravel Cloud:');
                $this->line('  1. Go to your Laravel Cloud dashboard');
                $this->line('  2. Navigate to your database settings');
                $this->line('  3. Add your deployment server IP to the whitelist');
                $this->line('  4. Common deployment IP ranges: 10.213.0.0/16 or specific IPs');
            }

            return Command::FAILURE;
        }
    }
}


