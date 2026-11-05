<a href="{{ $url }}" class="btn-raw link-primary d-inline-flex align-items-center justify-content-center align-self-stretch flex-shrink-0 gap-2 text-nowrap px-3" aria-label="View all: {{ $context }}" @if($preview ?? false) data-ds-preview @endif>
    View all @icon('arrow-right', ['mr' => 0])
</a>
