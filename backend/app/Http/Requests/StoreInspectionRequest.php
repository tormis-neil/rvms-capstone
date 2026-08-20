<?php

namespace App\Http\Requests;

use App\Models\InspectionChecklistItem;
use App\Models\InspectionItem;
use App\Models\Vehicle;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Driver BLOWBAGETS submission (FR-09). Every applicable checklist item must
 * be present (12 for most agencies, 14 for BFP), each OK or Has Issue, with
 * remarks required on any Has Issue, and the vehicle must belong to the
 * driver's own agency.
 */
class StoreInspectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $agencyId = $this->user()->agency_id;

        return [
            'vehicle_id' => [
                'required',
                Rule::exists('vehicles', 'id')->where('agency_id', $agencyId),
            ],
            'inspection_date' => ['nullable', 'date'],
            'items' => ['required', 'array'],
            'items.*.checklist_item_id' => ['required', 'integer', 'exists:inspection_checklist_items,id'],
            'items.*.status' => ['required', Rule::in([InspectionItem::STATUS_OK, InspectionItem::STATUS_HAS_ISSUE])],
            'items.*.remarks' => ['nullable', 'string'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $this->assertVehicleIsInService($validator);

            $items = $this->input('items', []);

            // Remarks required on any flagged item.
            foreach ($items as $index => $item) {
                if (($item['status'] ?? null) === InspectionItem::STATUS_HAS_ISSUE
                    && trim((string) ($item['remarks'] ?? '')) === '') {
                    $validator->errors()->add("items.$index.remarks", 'Remarks are required for a flagged item.');
                }
            }

            // The submission must cover exactly the driver's applicable checklist —
            // no missing items, no extras, and no BFP-only items for other agencies.
            $expected = InspectionChecklistItem::query()
                ->forAgencyCode($this->user()->agency->code)
                ->pluck('id')
                ->sort()
                ->values()
                ->all();

            $submitted = collect($items)
                ->pluck('checklist_item_id')
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->sort()
                ->values()
                ->all();

            if ($expected !== $submitted) {
                $validator->errors()->add('items', 'The inspection must include every checklist item for your agency, and no others.');
            }
        });
    }

    /**
     * A daily inspection is a pre-trip check, so the vehicle has to be in
     * service (2026-08, adviser-reported).
     *
     * Before this, a driver could file a full BLOWBAGETS checklist against a
     * truck the system was reporting as Not Operational or Under Preventive
     * Maintenance — recording a trip that could not happen, and storing an
     * all-OK result that contradicts the vehicle's own status (FR-09 vs FR-18).
     *
     * The message names the status so the driver knows why, rather than being
     * refused by a form that will not say what is wrong.
     */
    private function assertVehicleIsInService(Validator $validator): void
    {
        // A vehicle_id that already failed 'required' or 'exists' has nothing
        // to look up; a second error would only obscure the first.
        if ($validator->errors()->has('vehicle_id')) {
            return;
        }

        $vehicle = Vehicle::query()->find($this->input('vehicle_id'));

        if ($vehicle && ! $vehicle->isInService()) {
            $validator->errors()->add('vehicle_id', sprintf(
                '%s is currently %s, so it is not in service for a daily inspection. '
                .'Report a new fault as a damage report instead.',
                $vehicle->plate_number,
                $vehicle->status,
            ));
        }
    }
}
