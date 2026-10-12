@if(config('activity.enabled') && ($activityPage = request()->attributes->get('activity.page')))
@php
$activityPiece = request()->route('piece');
$activityConfig = [
    'endpoint' => url('/activity/events'),
    'pageToken' => \Illuminate\Support\Facades\Crypt::encryptString($activityPage->id),
    'csrfToken' => csrf_token(),
    'pageId' => $activityPage->id,
    'pieceId' => is_object($activityPiece) && method_exists($activityPiece, 'getKey') ? $activityPiece->getKey() : null,
];
@endphp
<script>
window.pianoActivityConfig = @json($activityConfig);
</script>
<script src="{{ mix('js/components/activity.js') }}"></script>
@endif
