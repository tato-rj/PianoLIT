@isset($count)
    <span class="d-inline-flex align-items-center text-nowrap">
        @icon('eye', ['mr' => 1]){{ shortNumber($count) }} {{ $count === 1 ? 'view' : 'views' }}
    </span>
@endisset
