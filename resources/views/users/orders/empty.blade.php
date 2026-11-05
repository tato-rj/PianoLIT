@component('users.components.empty-library', ['kind' => 'orders', 'heading' => 'No orders yet', 'description' => 'Your merchandise orders will appear here.'])
    <a class="btn btn-primary" href="{{ route('shop.home') }}">Explore the Shop @icon('arrow-right', ['mr' => 0])</a>
@endcomponent
