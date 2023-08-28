@fa(['icon' => 'headphones', 'if' => $piece->hasAudio(), 'title' => 'Audio'])
@fa(['icon' => 'video', 'if' => $piece->tutorials()->exists(), 'title' => 'Video'])
@fa(['icon' => 'hands-clapping', 'if' => $piece->performances()->approved()->exists(), 'title' => 'Performances'])
@fa(['icon' => 'fire', 'if' => $piece->hasTutorials(['synthesia']), 'title' => 'Synthesia'])
@fa(['icon' => 'file-alt', 'if' => $piece->hasScore($publicDomain = true), 'title' => 'Score'])
{{-- @fa(['icon' => 'eye', 'color' => 'muted']){{$piece->views_count}} {{ str_plural('view', $piece->views_count) }} --}}