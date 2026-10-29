@include('components.media.link-share', ['id' => 'piece-share-modal', 'url' => route('webapp.pieces.show', $piece), 'title' => $piece->medium_name_with_composer])
