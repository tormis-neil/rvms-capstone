<?php

namespace Database\Seeders;

use App\Models\InspectionChecklistItem;
use Illuminate\Database\Seeder;

class InspectionChecklistItemSeeder extends Seeder
{
    /**
     * Seed the BLOWBAGETS checklist catalog (FR-09):
     * 12 standard items + Hydraulic System / Fire Pump for BFP only.
     */
    public function run(): void
    {
        $items = [
            // The 12 standard BLOWBAGETS items, in display order.
            ['name' => 'Battery', 'is_bfp_only' => false],
            ['name' => 'Lights', 'is_bfp_only' => false],
            ['name' => 'Oil', 'is_bfp_only' => false],
            ['name' => 'Water', 'is_bfp_only' => false],
            ['name' => 'Brakes', 'is_bfp_only' => false],
            ['name' => 'Air', 'is_bfp_only' => false],
            ['name' => 'Gas', 'is_bfp_only' => false],
            ['name' => 'Engine', 'is_bfp_only' => false],
            ['name' => 'Tires', 'is_bfp_only' => false],
            ['name' => 'Power Steering', 'is_bfp_only' => false],
            ['name' => 'Horn/Siren', 'is_bfp_only' => false],
            ['name' => 'Directional Signals', 'is_bfp_only' => false],
            // BFP-only extras.
            ['name' => 'Hydraulic System', 'is_bfp_only' => true],
            ['name' => 'Fire Pump', 'is_bfp_only' => true],
        ];

        foreach ($items as $index => $item) {
            InspectionChecklistItem::updateOrCreate(
                ['name' => $item['name']],
                $item + ['sort_order' => $index + 1],
            );
        }
    }
}
