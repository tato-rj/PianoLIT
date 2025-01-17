<div class="border py-4 px-3 h-100">
    <div class="ml-2 mb-3">
        <h4 class="mb-1"><strong><i class="fas fa-birthday-cake mr-2"></i>Upcoming birthdays</strong></h4>
        <p class="text-muted">We found {{$upcomingBirthdays->count()}} {{str_plural('birthday', $upcomingBirthdays->count())}} over the next 30 days.</p>
    </div>
    <div class="mx-2">
        @if($upcomingBirthdays->count() > 0)
            <div class="d-flex justify-content-between">
                <div><small><strong>Name</strong></div><div><strong>Born in</strong></small></div>
            </div>
            @foreach($upcomingBirthdays as $composer)
            <div class="d-flex justify-content-between">
                <div>{{$composer->name}}</div><div>{{$composer->date_of_birth->toFormattedDateString()}}</div>
            </div>
            @endforeach
        @endif
    </div>
</div>