@if(view()->exists('components.cta.' . $type))
@include('components.cta.' . $type)
@endif