{{--
    Topbar notification bell (FR-21).

    BLOCK B: the prototype's markup from web/pages/notifications.html with only
    the hardcoded data replaced by live values. Two behaviours are the
    prototype's own, kept deliberately:
      - the dropdown lists TODAY and YESTERDAY only (agency.js renderBell filters
        to those two groups); everything else lives on the full page;
      - the count badge and the "N new" pill both show the UNREAD count.

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
        ->whereDate('created_at', '>=', now()->subDay()->startOfDay())
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
                <button type="submit" class="dropdown-item notif-item d-flex align-items-start gap-2 py-2 border-bottom text-start w-100 border-0 bg-transparent">
                    <span class="notif-icon rounded-circle bg-{{ $notification->tone() }} bg-opacity-10 text-{{ $notification->tone() }} d-inline-flex justify-content-center align-items-center"><i class="bi {{ $notification->icon() }}"></i></span>
                    <span>
                        <span class="small fw-bold d-block">{{ $notification->title }}</span>
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
