@foreach($levels as $level)
<div class="tinderslide" data-length="{{count($statements[$level])}}" id="game-{{$level}}" style="display: none;">
    <ul class="list-flat">
        @foreach($statements[$level] as $statement => $answer)
        <li class="card d-flex p-4 flex-center bg-white" data-answer="{{$answer ? 'correct' : 'wrong'}}" style="opacity: 0; cursor: grab; border: 2.4rem solid {{randval($colors)}}">
            <h5 class="m-0">{!! $statement !!}</h5>
            <span class="position-absolute text-muted" style="bottom: 4px;"><small>{{$loop->remaining + 1}} of {{$loop->count}}</small></span>
        </li>
        @endforeach
    </ul>
</div>
@endforeach