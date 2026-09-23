@fa(['icon' => 'headphones', 'if' => $piece->hasAudio(), 'title' => 'Audio'])
@fa(['icon' => 'video', 'if' => $piece->tutorials_count > 0, 'title' => 'Video'])
@fa(['icon' => 'hands-clapping', 'if' => ($piece->webapp_has_performances ?? $piece->performances()->approved()->exists()) > 0, 'title' => 'Performances'])
@fa(['icon' => 'fire', 'if' => ($piece->webapp_has_synthesia ?? $piece->hasTutorials(['synthesia'])) > 0, 'title' => 'Synthesia'])
@fa(['icon' => 'file-alt', 'if' => $piece->hasScore($publicDomain = true), 'title' => 'Score'])
{{-- @fa(['icon' => 'eye', 'color' => 'muted']){{$piece->views_count}} {{ str_plural('view', $piece->views_count) }} --}}