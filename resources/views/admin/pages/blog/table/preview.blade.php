@include('admin.components.table-action', ['tableAction' => [
    'kind' => 'other', 'label' => 'Preview '.$previewPost->title, 'icon' => 'eye', 'iconOnly' => true,
    'url' => $previewPost->published_at ? route('posts.show', $previewPost) : null,
    'attributes' => [
        'target' => $previewPost->published_at ? '_blank' : null,
        'rel' => $previewPost->published_at ? 'noopener noreferrer' : null,
        'disabled' => !$previewPost->published_at,
        'aria-disabled' => $previewPost->published_at ? null : 'true',
        'title' => $previewPost->published_at ? 'Preview '.$previewPost->title : 'Publish this post to view it.',
    ],
]])
