<?php

namespace Database\Factories;

use App\Models\Inspection;
use App\Models\InspectionChecklistItem;
use App\Models\InspectionItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InspectionItem>
 */
class InspectionItemFactory extends Factory
{
    protected $model = InspectionItem::class;

    public function definition(): array
    {
        return [
            'inspection_id' => Inspection::factory(),
            'checklist_item_id' => InspectionChecklistItem::factory(),
            'status' => InspectionItem::STATUS_OK,
            'remarks' => null,
        ];
    }

    public function hasIssue(string $remarks = 'Issue found during inspection.'): static
    {
        return $this->state(fn () => [
            'status' => InspectionItem::STATUS_HAS_ISSUE,
            'remarks' => $remarks,
        ]);
    }
}
