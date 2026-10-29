@component('users.components.empty-library', ['kind' => 'orders', 'heading' => 'A little piano, delivered', 'description' => 'Find your next favorite in the Shop. Follow its journey here.'])
    <a class="btn btn-primary" href="{{ route('shop.home') }}">Explore the Shop @icon('arrow-right', ['mr' => 0])</a>
@endcomponent
