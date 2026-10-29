@component('users.components.empty-library', ['kind' => 'downloads', 'heading' => 'Your library starts here', 'description' => 'A little inspiration for your next time at the piano.'])
    <a class="btn btn-primary" href="{{ route('resources.infographs.index') }}">Explore infographics @icon('arrow-right', ['mr' => 0])</a>
@endcomponent
