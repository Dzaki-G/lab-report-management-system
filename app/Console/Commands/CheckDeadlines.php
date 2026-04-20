<?php

namespace App\Console\Commands;

use App\Services\NotificationService;
use Illuminate\Console\Command;

class CheckDeadlines extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notifications:check-deadlines';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check for upcoming deadlines (3 days and 1 day) and send notifications';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Checking for upcoming deadlines...');
        
        $service = new NotificationService();
        $count = $service->checkDeadlines();
        
        $this->info("Created {$count} deadline notifications.");
        
        return Command::SUCCESS;
    }
}
