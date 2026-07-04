<?php

namespace App\Http\Requests;

use App\Models\InspectionChecklistItem;
use App\Models\InspectionItem;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Driver's daily BLOWBAGETS submission (FR-09): every item of the
 * agency's checklist gets OK or Has Issue, and a flagged item must
 * carry remarks explaining the issue.
 */
class InspectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'vehicle_id' => [
                'required',
                Rule::exists('vehicles', 'id')
                    ->where(fn ($q) => $q->where('agency_id', $this->user()->agency_id)),
            ],
            'inspection_date' => ['required', 'date'],
            'items' => ['required', 'array'],
            'items.*.checklist_item_id' => [
                'required', 'integer', 'distinct',
                Rule::exists('inspection_checklist_items', 'id'),
            ],
            'items.*.status' => [
                'required',
                Rule::in([InspectionItem::STATUS_OK, InspectionItem::STATUS_HAS_ISSUE]),
            ],
            'items.*.remarks' => [
                'required_if:items.*.status,'.InspectionItem::STATUS_HAS_ISSUE,
                'nullable', 'string',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'vehicle_id.exists' => 'The vehicle must belong to your agency.',
            'items.*.remarks.required_if' => 'Remarks are required for every item marked "Has Issue".',
            'items.*.checklist_item_id.distinct' => 'Each checklist item may only appear once.',
        ];
    }

    /**
     * The submission must cover the caller's agency checklist exactly —
     * no missing items, and no BFP-only items from a non-BFP driver.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return; // base rules already failed; don't stack confusing errors
            }

            $expected = InspectionChecklistItem::forAgency($this->user()->agency)
                ->pluck('id')->sort()->values();
            $submitted = collect($this->input('items', []))
                ->pluck('checklist_item_id')->map(fn ($id) => (int) $id)->sort()->values();

            if ($expected->toArray() !== $submitted->toArray()) {
                $validator->errors()->add(
                    'items',
                    'The inspection must include every checklist item for your agency, and no others.',
                );
            }
        });
    }
}
