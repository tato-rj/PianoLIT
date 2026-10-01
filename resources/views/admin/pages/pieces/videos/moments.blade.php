@extends('admin.layouts.app')

@section('head')
<link rel="stylesheet" href="https://cdn.plyr.io/3.7.8/plyr.css">
@endsection

@section('content')
<div class="content-wrapper">
    <div class="container-fluid">
        @include('admin.components.page.title', [
            'theme' => 'edit', 'title' => 'Sections · '.$tutorial->type,
            'subtitle' => $piece->name,
            'back' => ['back to piece' => route('admin.pieces.edit', $piece)]
        ])
        <div class="row">
            <div class="col-lg-8 mx-auto">
                <div class="mb-4" data-moment-preview>
                    @video(['id' => 'admin-moment-preview', 'url' => $tutorial->video_url, 'thumbnail' => $tutorial->thumbnail, 'moments' => $tutorial->listeningMoments()])
                    <p class="small text-muted mt-2 mb-0">Save changes to update the sections in this preview.</p>
                </div>

                @if($errors->any())
                <div class="alert alert-danger" role="alert">
                    @foreach($errors->all() as $error)<p class="mb-1">{{$error}}</p>@endforeach
                </div>
                @endif

                <form method="POST" action="{{route('admin.pieces.videos.moments.update', [$piece, $tutorial])}}" class="moment-editor" data-moments-editor novalidate>
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="revision" value="{{old('revision', $revision)}}">
                    <div class="alert alert-danger" role="alert" data-moment-alert hidden></div>
                    <div data-moment-rows>
                        @foreach(old('moments', $moments->toArray()) as $index => $moment)
                            @include('admin.pages.pieces.videos.moment-row')
                        @endforeach
                    </div>
                    <template data-moment-template>
                        @include('admin.pages.pieces.videos.moment-row', ['index' => 0, 'moment' => []])
                    </template>
                    <div class="moment-editor__actions">
                        <button type="button" class="btn btn-secondary" data-moment-action="add">@icon('plus', ['mr' => 1]) Add a section</button>
                        <button type="submit" class="btn btn-primary">Save sections</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="https://cdn.plyr.io/3.7.8/plyr.js"></script>
<script src="{{mix('js/views/video-moments.js')}}"></script>
<script src="{{mix('js/views/video-moments-admin.js')}}"></script>
@endsection
