@video([
    'classes' => 'w-100',
    'id' => 'piece-video-'.$tutorial->id,
    'moments' => $tutorial->listeningMoments(),
    'accessPrompt' => app(\App\Services\WebApp\AccessPolicy::class)->promptTarget(auth('web')->user(), stripos($tutorial->type, 'synthesia') !== false ? 'synthesia' : 'performance', $piece),
            'previewSeconds' => $hasVideoAccess ? null : $videoPreviewSeconds,
    'url' => $tutorial->video_url])
