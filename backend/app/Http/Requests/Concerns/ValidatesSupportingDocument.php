<?php

namespace App\Http\Requests\Concerns;

/**
 * Optional supporting document for a form (PM schedule create, dispatch open —
 * 2026-09, adviser consultation).
 *
 * Unlike the repair/PM-completion receipt (ValidatesRepairReceipt), these
 * documents are never required — a PM schedule may be created without a
 * pre-inspection checklist, and an emergency dispatch rolls out without a travel
 * order — so this trait carries only the "always optional" case. It shares the
 * same file-security decisions as the receipt on purpose (one allow-list, one
 * size cap), because both are files an admin later opens from /storage.
 */
trait ValidatesSupportingDocument
{
    /**
     * Rules for an always-optional supporting-document upload.
     *
     * The explicit MIME allow-list (no SVG) and 5 MB cap match the repair
     * receipt and the damage photo: SVG can carry scripts and would execute in
     * the dashboard's own origin when served back and viewed (stored XSS,
     * security audit R10.2); PDF is here because these documents usually are one.
     *
     * @return list<string>
     */
    protected function supportingDocumentRules(): array
    {
        return [
            'nullable',
            'file',
            'mimes:pdf,jpg,jpeg,png,webp,heic,heif',
            'max:5120', // 5 MB, matching the damage photo and repair receipt
        ];
    }

    /**
     * Store the uploaded document and return its path, or null if none came.
     *
     * On the `public` disk beside the damage photos and repair receipts, so the
     * one `storage:link` that rvms:doctor checks exposes these too.
     */
    public function storeSupportingDocument(string $field, string $folder): ?string
    {
        return $this->hasFile($field)
            ? $this->file($field)->store($folder, 'public')
            : null;
    }

    /**
     * @return array<string, string>
     */
    protected function supportingDocumentMessages(string $field): array
    {
        return [
            "{$field}.mimes" => 'The document must be a PDF or an image (JPG, PNG, WEBP or HEIC).',
            "{$field}.max" => 'The document must not be larger than 5 MB.',
            "{$field}.uploaded" => 'The document did not finish uploading. It may be larger than '
                .'this server accepts — try a smaller file.',
        ];
    }
}
