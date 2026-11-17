<details id="discover-playing-level" class="mt-2" @if($errors->has('playing_level')) open @endif>
    <summary class="small">Playing level</summary>
    <form class="mt-2" method="POST" action="{{ route('webapp.discover.level') }}">
        @csrf
        @include('components.forms.select', ['id' => 'discover-level-choice', 'name' => 'playing_level', 'label' => 'Preferred level',
            'bag' => 'default', 'required' => '', 'choices' => ['' => 'No preference'] + array_map(fn ($level) => $level['label'], \App\Services\WebApp\MatchTour::LEVELS),
            'value' => $row['playing_level'] ?? null])
        @button(['label' => 'Save preference', 'submit' => true, 'styles' => ['theme' => 'secondary', 'size' => 'sm']])
    </form>
</details>
