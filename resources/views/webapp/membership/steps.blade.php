<nav class="membership-steps" aria-label="Membership progress">
    <ol>
        @foreach([1 => 'Choose a plan', 2 => 'Secure checkout', 3 => 'Make music'] as $number => $label)
        <li @if($step === $number) aria-current="step" @endif class="{{ $step >= $number ? 'is-active' : '' }}">
            <span class="membership-step-number" aria-hidden="true">@if($step > $number) @icon('check', ['mr' => 0]) @else {{ $number }} @endif</span>
            <span>{{ $label }}</span>
        </li>
        @endforeach
    </ol>
</nav>
