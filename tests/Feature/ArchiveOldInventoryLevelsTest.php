<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\InventoryLevel;
use Illuminate\Support\Carbon;

class ArchiveOldInventoryLevelsTest extends TestCase
{
    use RefreshDatabase;

    public function test_archives_old_inventory_levels()
    {
        // Cria registros antigos e recentes
        $recent = InventoryLevel::factory()->create([
            'updated_at' => Carbon::now()->subDays(10),
            'archived' => false,
        ]);
        $old = InventoryLevel::factory()->create([
            'updated_at' => Carbon::now()->subDays(100),
            'archived' => false,
        ]);

        // Executa o comando
        $this->artisan('inventory:archive-old')
            ->expectsOutput('1 registros de estoque sinalizados como arquivados.')
            ->assertExitCode(0);

        $this->assertEquals(0, $recent->fresh()->archived);
        $this->assertEquals(1, $old->fresh()->archived);
    }

    public function test_command_does_not_archive_recent_inventory_levels()
    {
        $recent = InventoryLevel::factory()->create([
            'updated_at' => Carbon::now()->subDays(5),
            'archived' => false,
        ]);
        $this->artisan('inventory:archive-old')
            ->expectsOutput('0 registros de estoque sinalizados como arquivados.')
            ->assertExitCode(0);
        $this->assertEquals(0, $recent->fresh()->archived);
    }

    public function test_command_does_not_archive_already_archived_inventory_levels()
    {
        $archived = InventoryLevel::factory()->create([
            'updated_at' => Carbon::now()->subDays(100),
            'archived' => true,
        ]);
        $this->artisan('inventory:archive-old')
            ->expectsOutput('0 registros de estoque sinalizados como arquivados.')
            ->assertExitCode(0);
        $this->assertEquals(1, $archived->fresh()->archived);
    }
}
