{{--
    Success + validation feedback for a dashboard page.

    Every page needs BOTH halves. The vehicle-status guard refuses a write while
    the vehicle is on an active dispatch (design decision 9) by throwing a
    validation error naming the mission — and a redirect back to a page with no
    $errors block would swallow that message, leaving the admin with a status
    that silently did not change. Guarded by DashboardAlertsTest.
--}}
@if (session('status'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('status') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif
@if ($errors->any())
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <ul class="mb-0 small">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif
