<?php

namespace App\Console\Commands;

use App\Services\UnifiSiteManager;
use Illuminate\Console\Command;

class SyncUnifiSites extends Command
{
    protected $signature = 'unifi:sync-sites';
    protected $description = 'Sync sites from UniFi Site Manager and raise incidents for offline/degraded sites';

    public function handle(UnifiSiteManager $svc): int
    {
        if (! $svc->hasApiKey()) {
            $this->warn('UNIFI_SITE_MANAGER_API_KEY is not configured.');
            return self::FAILURE;
        }

        $summary = $svc->syncIntoDatabase();
        $this->info(sprintf(
            'UniFi sync complete. Sites: %d, created: %d, updated: %d, incidents raised: %d.',
            $summary['total'], $summary['created'], $summary['updated'], $summary['incidents']
        ));
        return self::SUCCESS;
    }
}

