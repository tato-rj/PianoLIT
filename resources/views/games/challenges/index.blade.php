@extends('layouts.app', ['title' => 'PianoLIT Games'])

@push('header')
@include('games.challenges.assets')
@endpush

@section('content')
<div class="container py-4 pb-5">
    @include('components.navigation.breadcrumb', ['items' => [['label' => 'Home', 'url' => route('home')], ['label' => 'Games']]])
    @component('components.layout.hub-header', ['heading' => 'PianoLIT Games', 'preview' => false])
        Interactive games for practicing core music theory skills.
    @endcomponent
    <section class="music-games" data-preserve-icons>
        <div class="row"><div class="col-12">
            @include('games.challenges.components.duel-banner')

            <div class="text-right">
                @if($category = request('category'))
                <div class="d-inline-block">
                    <a href="{{route('games.home')}}" class="btn btn-raw">
                    <div class="border rounded border-3 px-2 py-1 small d-center fw-bold text-muted">
                        @include('games.challenges.components.icon', ['icon' => 'times']){{$category}}
                    </div>
                    </a>
                </div>
                @endif
            </div>
        </div>
    </div>
    <div class="row">
        @foreach($games as $settings)
            @php
                $matchesCategory = ! request('category')
                    || $settings->categories()->contains(request('category'));
            @endphp

            @if($settings->public() && $matchesCategory)
                @include('games.challenges.card')
            @endif
        @endforeach
    </div>
        @include('games.challenges.components.duel')
    </section>
</div>
@endsection

@push('scripts')
<script src="{{ mix('js/games/hub.js') }}"></script>
<script>
$('.mode-menu button').on('click', function () {
  const $btns = $(this).siblings().addBack();
  $btns.removeClass('btn-secondary').addClass('btn-outline-secondary')
       .each((_, b) => $($(b).data('target')).hide());
  $(this).removeClass('btn-outline-secondary').addClass('btn-secondary');
  $($(this).data('target')).show();
});
</script>

<script>
</script>
@endpush
