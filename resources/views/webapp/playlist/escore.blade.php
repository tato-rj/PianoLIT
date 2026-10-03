@php($scoreCount = $escorePieces->filter(function ($item) { return $item->score_path && $item->is_public_domain && $item->hasWebMediaAccess(auth('web')->user()); })->count())
<div class="text-center playlist-escore-wrap">
    <button class="btn btn-secondary playlist-escore" type="button" data-bs-toggle="modal" data-bs-target="#playlist-escore-modal">@icon('tablet', ['mr' => 0])Create eScore</button>
</div>
@component('components.modal', ['id' => 'playlist-escore-modal', 'header' => 'Create eScore'])
@slot('body')
@auth('web')
    <p>Your eScore will include <strong>{{$scoreCount}}</strong> {{str_plural('piece', $scoreCount)}} with public-domain scores available to your account.</p>
    @if($scoreCount)
    <form target="_blank" method="GET" action="{{$escoreUrl}}">
        <div class="mb-3">
            <label class="form-label" for="escore-title">Cover title</label>
            <input class="form-control" id="escore-title" name="title" value="{{$escoreName}}" maxlength="160" required>
        </div>
        <div class="mb-3">
            <label class="form-label" for="escore-subtitle">Subtitle</label>
            <input class="form-control" id="escore-subtitle" name="subtitle" value="A collection of pieces" maxlength="160" required>
        </div>
        <div class="mb-3">
            <label class="form-label" for="escore-comment">Comment</label>
            <input class="form-control" id="escore-comment" name="comment" value="for piano" maxlength="160" required>
        </div>
        <button class="btn btn-primary" type="submit">Create my eScore</button>
    </form>
    @else
    <p class="text-muted">No eligible scores are available in this collection.</p>
    @endif
@else
    <p>Sign in to create an eScore from this collection.</p>
    <a class="btn btn-primary" href="{{route('login')}}">Sign in</a>
@endauth
@endslot
@endcomponent
