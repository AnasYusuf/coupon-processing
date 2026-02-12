<?php 
//This removes corrupted or non-expiring keys

namespace App\Console\Commands;

use Illuminate\Console\Command;

class CleanupReservations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:cleanup-reservations';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $keys = Redis::keys('coupon:*:user:*');

        foreach ($keys as $key) {

            $ttl = Redis::ttl($key);

            // If key has no expiry OR corrupted TTL
            if ($ttl === -1) {
                Redis::del($key);
            }

            // If TTL is negative (expired but still exists)
            if ($ttl === -2) {
                Redis::del($key);
            }
        }
    }

}
