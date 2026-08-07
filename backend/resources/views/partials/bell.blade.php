{{--
    Topbar notification bell (FR-21).

    BLOCK B: the prototype's markup from web/pages/notifications.html with only
    the hardcoded data replaced by live values.

    The badge and the list must describe the SAME set — the prototype's
    agency.js renderBell derives both from one array, so its count could never
    disagree with what it showed. An earlier version here split them: the badge
    counted every unread notification while the list was filtered to the last
    two days, so an unread alert older than yesterday was COUNTED but never
    RENDERED. The bell then read "3" over an empty dropdown, and the admin had
    to open the full page to find out what it meant (2026-08, lead-reported).

    So the date filter is gone: the dropdown carries the latest ten
    notifications whatever their age, which is also what a modern notification
    bell does. A non-zero badge now always has something behind it, and the
    Today / Yesterday / Earlier grouping still lives on the full page, which is
    where the prototype put it anyway.

    Unread rows carry the blue dot and the tinted background, exactly as the
    notifications page renders them. The prototype's bell has neither, but that
    is because its demo data has no read/unread state at all (agency.js styles
    every row identically) — the same reason the full page already added them.
    Without it the badge said "3 new" over ten identically-styled rows, so the
    one thing the count promised was the one thing the list would not show
    (2026-08, lead-reported).

    Rows post to notifications.open, which marks the notification read and then
    redirects to the module it concerns — Block A's hardcoded .html hrefs 404ed.
--}}
@php
    $bellUnread = \App\Models\Notification::query()
        ->forUser(auth()->id())
        ->unread()
        ->count();

    $bellItems = \App\Models\Notification::query()
        ->forUser(auth()->id())
        ->latest('created_at')
        ->latest('id')
        ->limit(10)
        ->get();
@endphp
<div class="dropdown me-2">
    <div class="position-relative" role="button" data-bs-toggle="dropdown" aria-label="Notifications">
        <i class="bi bi-bell fs-5 text-secondary"></i>
        @if ($bellUnread > 0)
        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger js-bell-count" style="font-size: 0.6rem;">{{ $bellUnread }}</span>
        @endif
    </div>
    <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 p-0 notif-dropdown js-bell-list">
        <li class="d-flex justify-content-between align-items-center px-3 py-2 border-bottom">
            <h6 class="mb-0 fw-bold small">Notifications</h6>
            @if ($bellUnread > 0)
            <span class="badge bg-danger rounded-pill">{{ $bellUnread }} new</span>
            @endif
        </li>
        @forelse ($bellItems as $notification)
        <li>
            {{-- A POST so opening a notification can mark it read; styled exactly
                 like the prototype's <a> row. --}}
            <form method="POST" action="{{ route('notifications.open', $notification) }}">
                @csrf
                <button type="submit" class="dropdown-item notif-item d-flex align-items-start gap-2 py-2 border-bottom text-start w-100 border-0 {{ $notification->is_read ? 'bg-transparent' : 'bg-light' }}">
                    <span class="notif-icon rounded-circle bg-{{ $notification->tone() }} bg-opacity-10 text-{{ $notification->tone() }} d-inline-flex justify-content-center align-items-center"><i class="bi {{ $notification->icon() }}"></i></span>
                    <span>
                        <span class="small fw-bold d-block">@unless ($notification->is_read)<i class="bi bi-circle-fill text-primary me-2" style="font-size: 0.4rem; vertical-align: middle;"></i>@endunless{{ $notification->title }}</span>
                        <span class="small text-secondary d-block">{{ $notification->message }}</span>
                        <span class="text-secondary d-block" style="font-size: 0.7rem;">{{ $notification->group() }}, {{ $notification->timeLabel() }}</span>
                    </span>
                </button>
            </form>
        </li>
        @empty
        <li><span class="dropdown-item-text small text-secondary py-3 text-center">No notifications yet.</span></li>
        @endforelse
        <li><a class="dropdown-item text-center small text-primary fw-semibold py-2" href="{{ route('notifications') }}">View All Notifications</a></li>
    </ul>
</div>
