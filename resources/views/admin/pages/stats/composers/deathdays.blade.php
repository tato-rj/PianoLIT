<div class="border py-4 px-3 h-100">
    <div class="ml-2 mb-4">
        <h4 class="mb-1"><strong><i class="fas fa-skull mr-2"></i>Upcoming deathdays</strong></h4>
        <p class="text-muted">We found {{$upcomingDeathdays->count()}} {{str_plural('deathday', $upcomingDeathdays->count())}} over the next 10 days.</p>
    </div>
    <div class="mx-2">
        @if($upcomingDeathdays->count() > 0)
            <div class="d-flex justify-content-between">
                <div><strong>Name</strong></div><div><strong>Died in</strong></div>
            </div>
            @foreach($upcomingDeathdays as $composer)
            <div class="d-flex justify-content-between">
                <div>{{$composer->name}}</div><div>{{$composer->date_of_death->toFormattedDateString()}}</div>
            </div>
            @endforeach
        @endif
    </div>
</div>