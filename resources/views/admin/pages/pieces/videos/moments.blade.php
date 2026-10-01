@extends('admin.layouts.app')

@section('content')
<div class="content-wrapper">
    <div class="container-fluid">
        @include('admin.components.page.title', [
            'theme' => 'edit', 'title' => 'Moments · '.$tutorial->type,
            'subtitle' => $piece->name,
            'back' => ['back to piece' => route('admin.pieces.edit', $piece)]
        ])
        <div class="row">
            <div class="col-lg-8 mx-auto">
                <p class="text-muted">Enter seconds (83.5), M:SS (1:23.5) or H:MM:SS (1:02:03). Without an end time, the info control appears for {{config('webapp.moment_display_seconds')}} seconds. For overlapping moments, the latest start time takes priority.</p>
                <p><a href="{{$tutorial->video_url}}" target="_blank" rel="noopener">Open this video</a></p>
                @if($errors->any())
                <div class="alert alert-danger" role="alert">
                    @foreach($errors->all() as $error)<p class="mb-1">{{$error}}</p>@endforeach
                </div>
                @endif
                <form method="POST" action="{{route('admin.pieces.videos.moments.update', [$piece, $tutorial])}}" data-moments-editor>
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="revision" value="{{old('revision', $revision)}}">
                    <div data-moment-rows>
                        @foreach(old('moments', $moments->toArray()) as $index => $moment)
                            @include('admin.pages.pieces.videos.moment-row')
                        @endforeach
                    </div>
                    <template data-moment-template>
                        @include('admin.pages.pieces.videos.moment-row', ['index' => 0, 'moment' => []])
                    </template>
                    <button type="button" class="btn btn-outline-secondary mb-3" data-moment-action="add">Add a moment</button>
                    <div class="text-end mb-4"><button type="submit" class="btn btn-default">Save moments</button></div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="{{mix('js/views/video-moments-admin.js')}}"></script>
@endsection
