@icon('headphones', ['if' => $piece->hasAudio(), 'title' => 'Audio'])
@icon('video', ['if' => $piece->tutorials_count > 0, 'title' => 'Video'])
@icon('hand-heart', ['if' => ($piece->webapp_has_performances ?? $piece->performances()->approved()->exists()) > 0, 'title' => 'Performances'])
@icon('flame', ['if' => ($piece->webapp_has_synthesia ?? $piece->hasTutorials(['synthesia'])) > 0, 'title' => 'Synthesia'])
@icon('file-text', ['if' => $piece->hasScore($publicDomain = true), 'title' => 'Score'])
{{-- @icon('eye', ['color' => 'muted']){{$piece->views_count}} {{ str_plural('view', $piece->views_count) }} --}}