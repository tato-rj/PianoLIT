<span class="d-inline-flex" data-feedback-tooltip data-bs-toggle="tooltip" data-bs-trigger="{{ $trigger ?? 'hover focus' }}" data-bs-title="{{ $text }}">{{ $slot }}</span>
@once
    @push('scripts')
        <script>
            document.querySelectorAll('[data-feedback-tooltip]').forEach(function (element) {
                window.bootstrap.Tooltip.getOrCreateInstance(element);
            });
        </script>
    @endpush
@endonce
