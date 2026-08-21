<?php

namespace App\Http\Requests\Concerns;

use App\Models\RepairLog;
use Illuminate\Validation\Rule;

/**
 * Supporting document for a repair (FR-13, FR-14 — adviser consultation,
 * 2026-08; widened after lead review).
 *
 * Repair Logs and PM completion are the two places that record who did the
 * work, from the same three sources — so they must demand the same evidence in
 * the same words. Shared here rather than duplicated, because the file rules
 * carry security decisions (below) and two copies would eventually disagree.
 *
 * WHY THE REQUIREMENT IS NOT UNIFORM ACROSS THE THREE SOURCES. Any source may
 * carry a document; only an EXTERNAL repair shop must. The GSO Motorpool is
 * another City office rather than a private shop — the vehicle leaves and work
 * is done by people the agency does not manage, so a document is worth keeping,
 * but no money leaves the LGU and what it issues is a job order rather than an
 * official receipt. The requirements interviews established that the Motorpool
 * does the work; they did not establish that it always issues paperwork the
 * agency keeps. Requiring it on that assumption would mean an administrator
 * without one cannot log the repair at all, leaving NO record instead of an
 * imperfect one — and the log is the point.
 *
 * So the rule sits where public money actually leaves the LGU, and the
 * attachment is offered everywhere else. If the Motorpool later confirms a job
 * order is always issued, adding it is one entry in RepairLog::REQUIRED_DOCUMENT_SOURCES.
 */
trait ValidatesRepairReceipt
{
    // The list and the hints live on RepairLog, beside SOURCES: they describe
    // repair sources, and a trait constant cannot be read from Blade anyway.

    /**
     * Rules for the supporting-document field.
     *
     * Accepted from any source; required only from those in
     * RepairLog::REQUIRED_DOCUMENT_SOURCES,
     * and then only when nothing is on file yet. That second half means editing
     * a record that already has a document does not demand it be uploaded
     * again — otherwise correcting a typo in a shop name would mean hunting
     * down the original paperwork — and records created before this rule
     * existed can still be edited.
     *
     * @param  string  $sourceField  The repair-source input this document belongs to.
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
                fn () => in_array($this->input($sourceField), RepairLog::REQUIRED_DOCUMENT_SOURCES, true) && $existing === null
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
            "{$field}.mimes" => 'The document must be a PDF or an image (JPG, PNG, WEBP or HEIC).',
            "{$field}.max" => 'The document must not be larger than 5 MB.',
            "{$field}.uploaded" => 'The document did not finish uploading. It may be larger than '
                .'this server accepts — try a smaller file.',
        ];
    }
}
