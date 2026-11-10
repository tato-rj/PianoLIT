@component('components.dialogs.modal', ['id' => 'blog-audio-modal', 'header' => 'Manage audio'])
@slot('body')
    <form method="POST" action="{{ route('admin.posts.audio.store', $returnFilters) }}" enctype="multipart/form-data" class="mb-3">
        @csrf
        @input(['bag' => 'blogAudioCreate', 'id' => 'blog-audio-new', 'name' => 'file', 'type' => 'file', 'accept' => '.mp3,.wav,.m4a,.ogg,.oga', 'label' => 'New audio', 'classes' => 'form-control-sm', 'grid' => 'mb-1', 'useOldInput' => false, 'describedBy' => 'blog-audio-hint'])
        <div class="d-flex align-items-center justify-content-between gap-2">
            <small id="blog-audio-hint" class="text-muted">MP3, WAV, M4A or OGG · up to 2 MB</small>
            <button type="submit" class="btn btn-primary btn-sm">@icon('upload')Upload</button>
        </div>
    </form>
    @forelse($audio as $path)
        @php($audioKey = app(\App\Services\Admin\BlogAudio::class)->key($path))
        <div class="border-top py-2">
            <div class="d-flex align-items-center gap-2 mb-1">
                <span class="small text-break flex-grow-1">{{ basename($path) }}</span>
                <form method="POST" action="{{ route('admin.posts.audio.destroy', $returnFilters) }}" class="m-0" onsubmit="return window.confirm('Delete this audio file? Article links to it will stop working.')">
                    @csrf @method('DELETE')
                    <input type="hidden" name="path" value="{{ $path }}">
                    @include('admin.components.table-action', ['tableAction' => ['kind' => 'delete', 'type' => 'submit', 'size' => 'xs', 'label' => 'Delete '.basename($path)]])
                </form>
            </div>
            <audio controls preload="none" class="w-100" aria-label="{{ basename($path) }}" src="{{ asset('storage/'.$path) }}"></audio>
            <details @if($errors->getBag($audioKey)->any()) open @endif>
                <summary class="small">URL and replace file</summary>
                <div class="mt-2">
                    @input(['bag' => $audioKey, 'id' => $audioKey.'-url', 'name' => 'audio_url', 'label' => 'Audio URL', 'value' => asset('storage/'.$path), 'readonly' => true, 'required' => false, 'useOldInput' => false, 'classes' => 'form-control-sm', 'grid' => 'mb-2'])
                    <form method="POST" action="{{ route('admin.posts.audio.update', $returnFilters) }}" enctype="multipart/form-data">
                        @csrf @method('PATCH')
                        <input type="hidden" name="path" value="{{ $path }}">
                        @input(['bag' => $audioKey, 'id' => $audioKey.'-file', 'name' => 'file', 'type' => 'file', 'accept' => '.'.pathinfo($path, PATHINFO_EXTENSION), 'label' => 'Replacement audio', 'classes' => 'form-control-sm', 'grid' => 'mb-1', 'useOldInput' => false])
                        <div class="d-flex align-items-center justify-content-between gap-2">
                            <small class="text-muted">Same format, up to 2 MB. Keeps URL.</small>
                            <button type="submit" class="btn btn-dark btn-sm text-nowrap">Replace</button>
                        </div>
                    </form>
                </div>
            </details>
        </div>
    @empty
        <p class="text-muted mb-0">No audio files yet.</p>
    @endforelse
@endslot
@endcomponent
