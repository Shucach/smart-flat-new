<?php

namespace App\Console\Commands;

use App\Media\TorrentDownload;
use Illuminate\Console\Command;

class CheckDownloadsStatus extends Command
{
    protected $signature = 'downloads:check';

    protected $description = 'Check & update download percents';

    public function handle(TorrentDownload $download): void
    {
        $download->checkDownloadsStatus();
    }
}
