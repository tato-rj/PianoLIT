@fa(['icon' => 'headphones', 'if' => $piece->hasAudio()])
@fa(['icon' => 'video', 'if' => $piece->hasVideos()])
@fa(['icon' => 'file-alt', 'if' => $piece->hasScore($publicDomain = true)])
{{-- @fa(['icon' => 'eye', 'color' => 'muted']){{$piece->views_count}} {{ str_plural('view', $piece->views_count) }} --}}