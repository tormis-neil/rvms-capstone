<?php

namespace Database\Seeders;

use App\Models\InspectionChecklistItem;
use Illuminate\Database\Seeder;

/**
 * The BLOWBAGETS checklist catalog (FR-09): 12 standard items in the
 * prototype's order, plus the 2 BFP-only items (Hydraulic System, Fire Pump).
 */
class InspectionChecklistSeeder extends Seeder
{
    public function run(): void
    {
        $standard = [
            'Battery', 'Lights', 'Oil', 'Water', 'Brakes', 'Air',
            'Gas', 'Engine', 'Tires', 'Power Steering', 'Horn/Siren', 'Directional Signals',
        ];

        foreach ($standard as $index => $name) {
            InspectionChecklistItem::updateOrCreate(
                ['name' => $name],
                ['is_bfp_only' => false, 'sort_order' => $index + 1],
            );
        }

        foreach (['Hydraulic System', 'Fire Pump'] as $index => $name) {
            InspectionChecklistItem::updateOrCreate(
                ['name' => $name],
                ['is_bfp_only' => true, 'sort_order' => count($standard) + $index + 1],
            );
        }
    }
}
