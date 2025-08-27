<?php

namespace App\Console\Commands\V1;

use App\Models\V1\User;
use Carbon\Carbon;
use Illuminate\Console\Command;

class DeleteUnverifiedUsers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'users:delete-unverified';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete users who haven\'t verified their email after one week';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $cutoffDate = Carbon::now()->subWeek();

        $users = User::whereNull('email_verified_at')
            ->where('created_at', '<=', $cutoffDate)
            ->where('registration_method',  'local')
            ->get();

        $count = $users->count();

        if ($count === 0) {
            $this->info('No unverified users found older than one week.');
            return;
        }
        $users->each->delete();
        $this->info("Successfully deleted {$count} unverified users.");
    }
}
