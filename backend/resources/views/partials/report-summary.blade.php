{{--
    Report summary — the analysis half of a printed report (FR-20, 2026-08).

    Added after the adviser consultation: every report was a table of text, so
    an officer wanting to know "which item fails most often" had to tally rows
    by eye — the manual work the system exists to remove.

    Deliberately NOT a charting library. Reports are printed (FR-20, and the
    manual test is Ctrl+P), and a <canvas> prints unreliably across browsers and
    is blank when scripting has not run — a printed report with an empty white
    box is worse than no chart. These are server-rendered Bootstrap progress
    bars, the same markup the Inspections page already uses for Frequently
    Reported Issues, so the visual language is the prototype's own (Rule 9) and
    nothing is added to the page's dependencies.

    Expects $summary = ['stats' => [...], 'breakdown' => [...]|null].
--}}
@php($stats = $summary['stats'] ?? [])
@php($breakdown = $summary['breakdown'] ?? null)

@if (! empty($stats))
<div class="row g-3 mb-4">
    @foreach ($stats as $stat)
    <div class="col-6 col-lg-3">
        <div class="border rounded-3 p-3 h-100">
            <div class="text-secondary text-uppercase fw-semibold" style="font-size:.7rem; letter-spacing:.06em;">{{ $stat['label'] }}</div>
            <div class="fw-bold fs-5 mt-1">{{ $stat['value'] }}</div>
        </div>
    </div>
    @endforeach
</div>
@endif

@if ($breakdown)
<div class="border rounded-3 p-3 mb-4">
    <div class="fw-semibold mb-3">{{ $breakdown['title'] }}</div>
    @php($max = collect($breakdown['items'])->max('count') ?: 1)
    @foreach ($breakdown['items'] as $index => $item)
    <div class="d-flex align-items-center gap-3 {{ $loop->last ? '' : 'mb-2' }}">
        <span class="text-secondary fw-bold" style="min-width:1.5rem;">#{{ $index + 1 }}</span>
        <div class="flex-grow-1">
            <div class="d-flex justify-content-between small mb-1">
                <span class="fw-medium">{{ $item['label'] }}</span>
                <span class="text-secondary">{{ number_format($item['count']) }}</span>
            </div>
            <div class="progress" style="height:8px;">
                <div class="progress-bar bg-warning" role="progressbar"
                     style="width:{{ (int) round($item['count'] / $max * 100) }}%"
                     aria-valuenow="{{ $item['count'] }}" aria-valuemin="0" aria-valuemax="{{ $max }}"></div>
            </div>
        </div>
    </div>
    @endforeach
</div>
@endif
