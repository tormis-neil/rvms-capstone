{{-- Prototype-style card footer: "Showing X to Y of Z <label>" + pager.
     Usage: @include('partials.table-footer', ['paginator' => $vehicles, 'label' => 'vehicles']) --}}
<div class="card-footer bg-white border-top py-3">
    <div class="d-flex justify-content-between align-items-center">
        <span class="small text-secondary">
            Showing {{ $paginator->firstItem() ?? 0 }} to {{ $paginator->lastItem() ?? 0 }}
            of {{ $paginator->total() }} {{ $label }}
        </span>
        {{ $paginator->onEachSide(1)->links() }}
    </div>
</div>
