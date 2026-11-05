@component('users.components.empty-library', ['kind' => 'downloads', 'heading' => 'No downloads yet', 'description' => 'Resources you purchase or download will appear here.'])
    <a class="btn btn-primary" href="{{ route('resources.infographs.index') }}">Explore infographics @icon('arrow-right', ['mr' => 0])</a>
@endcomponent
