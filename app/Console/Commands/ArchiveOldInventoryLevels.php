<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\InventoryLevel;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ArchiveOldInventoryLevels extends Command
{
    protected $signature = 'inventory:archive-old';
    protected $description = 'Arquiva ou sinaliza registros de estoque não atualizados há mais de 90 dias';

    public function handle()
    {
        $threshold = Carbon::now()->subDays(90);
        $count = InventoryLevel::where('updated_at', '<', $threshold)
            ->where('archived', false)
            ->update(['archived' => true]);
        $this->info("$count registros de estoque sinalizados como arquivados.");
    }
}
