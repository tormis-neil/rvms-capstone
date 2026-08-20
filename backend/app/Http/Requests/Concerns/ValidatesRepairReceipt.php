<?php

namespace App\Http\Requests\Concerns;

use App\Models\RepairLog;
use Illuminate\Validation\Rule;

/**
 * Proof of an external repair (FR-13, FR-14 — adviser consultation, 2026-08).
 *
 * Repair Logs and PM completion are the two places that can name an external
 * repair shop, and they record the same fact from the same three sources — so
 * they must demand the same evidence in the same words. Shared here rather than
 * duplicated, because the file rules carry security decisions (below) and two
 * copies would eventually disagree about them.
 */
trait ValidatesRepairReceipt
{
    /**
     * Rules for a receipt field.
     *
     * Required only when the source is an EXTERNAL repair shop and nothing is
     * on file yet. Work done by the Internal Office or the GSO Motorpool has no
     * third-party receipt to attach, and the "nothing on file yet" half means
     * editing a record that already has one does not demand it be re-uploaded,
     * and records created before this rule existed can still be corrected.
     *
     * @param  string  $sourceField  The repair-source input this receipt belongs to.
     * @param  string|null  $existing  A path already stored on the record, if any.
     * @return list<mixed>
     */
    protected function receiptRules(string $sourceField, ?string $existing = null): array
    {
        return [
            'nullable',
            'file',
            // An explicit allow-list rather than a broad rule, for the reason
            // recorded on the damage photo: SVG is a document that can carry
            // scripts, and served back from /storage it would execute in the
            // dashboard's own origin when an admin clicks View (stored XSS,
            // security audit R10.2). PDF is added because a receipt usually is
            // one; heic/heif because that is what a phone camera produces.
            'mimes:pdf,jpg,jpeg,png,webp,heic,heif',
            'max:5120', // 5 MB, matching the damage photo
            Rule::requiredIf(
                fn () => $this->input($sourceField) === RepairLog::SOURCE_EXTERNAL && $existing === null
            ),
        ];
    }

    /**
     * Store the uploaded receipt and return its path, or null if none came.
     *
     * On the `public` disk beside the damage photos, so one `storage:link`
     * exposes both — and `rvms:doctor` already fails the deployment check when
     * that link is missing, which is what stops every attachment 404ing on a
     * machine where it was never run.
     */
    public function storeReceipt(string $field = 'receipt'): ?string
    {
        return $this->hasFile($field)
            ? $this->file($field)->store('repair-receipts', 'public')
            : null;
    }

    /** @return array<string, string> */
    protected function receiptMessages(string $field): array
    {
        return [
            "{$field}.required" => 'Attach the receipt or service document when the work was done by an external repair shop.',
            "{$field}.mimes" => 'The receipt must be a PDF or an image (JPG, PNG, WEBP or HEIC).',
            "{$field}.max" => 'The receipt must not be larger than 5 MB.',
            "{$field}.uploaded" => 'The receipt did not finish uploading. It may be larger than '
                .'this server accepts — try a smaller file.',
        ];
    }
}
