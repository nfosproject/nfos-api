<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class GenerateReferralCodes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'referral:generate {--all : Generate codes for all users, even those with codes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate referral codes for users who don\'t have one';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Generating referral codes...');

        $query = User::query();
        
        if (!$this->option('all')) {
            $query->whereNull('referral_code');
        }

        $users = $query->get();
        $count = 0;

        foreach ($users as $user) {
            try {
                $code = $user->generateReferralCode();
                $count++;
                $this->line("Generated code {$code} for {$user->name} ({$user->email})");
            } catch (\Exception $e) {
                $this->error("Failed to generate code for {$user->name}: {$e->getMessage()}");
            }
        }

        $this->info("Successfully generated {$count} referral code(s).");
        
        return Command::SUCCESS;
    }
}
